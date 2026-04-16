<?php

namespace momokoudai\GeoipLocal\Support;

use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class GeoipDatabaseUpdater
{
    public function __construct(
        private GeoipLocalSettings $geoSettings,
        private SettingsRepositoryInterface $settings,
        private Paths $paths,
        private LoggerInterface $log
    ) {
    }

    public function update(bool $force = false): array
    {
        $this->log->info('GeoIP Database Update: Starting check.');

        if (!$this->geoSettings->autoUpdateEnabled()) {
            $this->log->warning('GeoIP Database Update: Aborted because auto-update is disabled.');
            throw new \RuntimeException('Auto-update is disabled.');
        }

        // 手动触发时（force=true）跳过频率检查
        if (!$force) {
            $last = (int) ($this->settings->get('momokoudai-geoip-local.auto_update.last_run') ?? 0);
            $freqHours = $this->geoSettings->autoUpdateFrequencyHours();
            $minInterval = $freqHours * 3600;

            if ($last > 0 && (time() - $last) < $minInterval) {
                $this->log->info('GeoIP Database Update: Skipped because it was updated recently.', [
                    'last_run' => $last,
                    'frequency_hours' => $freqHours
                ]);
                throw new \RuntimeException('Skip: updated recently.');
            }
        }

        $driver = $this->geoSettings->databaseDriver();
        $customUrl = $this->geoSettings->customDownloadUrl();

        $this->log->info('GeoIP Database Update: Configuration check.', [
            'driver' => $driver,
            'has_custom_url' => !empty($customUrl)
        ]);

        if ($customUrl) {
            return $this->downloadFromCustomUrl($customUrl);
        }

        if ($driver !== 'maxmind-mmdb') {
            $this->log->error('GeoIP Database Update: Unsupported driver.', ['driver' => $driver]);
            throw new \RuntimeException("Auto-update currently supports MaxMind (or provide a custom URL). Current driver: {$driver}.");
        }

        $licenseKey = $this->geoSettings->maxmindLicenseKey();

        if (!$licenseKey) {
            $this->log->error('GeoIP Database Update: Missing MaxMind license key.');
            throw new \RuntimeException('Missing MaxMind license_key in settings.');
        }

        return $this->downloadMaxMind($licenseKey);
    }

    private function downloadMaxMind(string $licenseKey): array
    {
        $target = $this->geoSettings->databasePath();
        if (!$target) {
            $target = $this->paths->storage . '/geoip/GeoLite2-City.mmdb';
        }

        $this->log->info('GeoIP Database Update (MaxMind): Preparing download.', ['target' => $target]);

        $dir = dirname($target);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->log->error('GeoIP Database Update (MaxMind): Unable to create target directory.', ['dir' => $dir]);
            throw new \Exception("Unable to create directory: {$dir}");
        }

        $editionId = 'GeoLite2-City';
        $url = "https://download.maxmind.com/app/geoip_download?edition_id={$editionId}&license_key={$licenseKey}&suffix=tar.gz";

        $client = new Client([
            'timeout' => 120,
            'connect_timeout' => 20,
        ]);

        $tmpTar = $this->paths->storage . '/geoip/tmp_geolite2_city.tar.gz';
        $tmpDir = $this->paths->storage . '/geoip/tmp_extract_' . bin2hex(random_bytes(4));

        try {
            $this->log->info('GeoIP Database Update (MaxMind): Downloading archive.', ['url' => $url]);
            $res = $client->get($url, [
                'sink' => $tmpTar,
                'http_errors' => false,
            ]);

            if ($res->getStatusCode() !== 200) {
                $this->log->error('GeoIP Database Update (MaxMind): Download failed.', ['status' => $res->getStatusCode()]);
                throw new \Exception('Download failed with status ' . $res->getStatusCode());
            }

            $this->log->info('GeoIP Database Update (MaxMind): Download complete. Extracting...');

            if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
                $this->log->error('GeoIP Database Update (MaxMind): Unable to create temp directory.', ['dir' => $tmpDir]);
                throw new \Exception("Unable to create temp directory: {$tmpDir}");
            }

            $pharGz = new \PharData($tmpTar);
            $tarPath = str_replace('.gz', '', $tmpTar);
            if (is_file($tarPath)) {
                @unlink($tarPath);
            }
            $pharGz->decompress();

            $this->log->info('GeoIP Database Update (MaxMind): Decompressed. Extracting TAR...');

            $pharTar = new \PharData($tarPath);
            $pharTar->extractTo($tmpDir, null, true);

            $mmdb = $this->findMmdbInDir($tmpDir);
            if (!$mmdb) {
                $this->log->error('GeoIP Database Update (MaxMind): Could not find .mmdb in extracted archive.', ['dir' => $tmpDir]);
                throw new \Exception('Could not find .mmdb in downloaded archive.');
            }

            $this->log->info('GeoIP Database Update (MaxMind): Found MMDB file. Moving to target.', ['file' => $mmdb]);

            $tmpOut = $target . '.tmp';
            if (!@copy($mmdb, $tmpOut)) {
                $this->log->error('GeoIP Database Update (MaxMind): Failed to copy mmdb to temporary target location.');
                throw new \Exception('Failed to copy mmdb to target.');
            }

            if (!@rename($tmpOut, $target)) {
                $this->log->warning('GeoIP Database Update (MaxMind): First rename attempt failed, retrying after unlink.');
                @unlink($target);
                if (!@rename($tmpOut, $target)) {
                    $this->log->error('GeoIP Database Update (MaxMind): Failed to move mmdb into final place.');
                    throw new \Exception('Failed to move mmdb into place.');
                }
            }

            $this->settings->set('momokoudai-geoip-local.db_path', $target);
            $this->settings->set('momokoudai-geoip-local.auto_update.last_run', time());

            $this->log->info('GeoIP Database Update (MaxMind): Update successful.', ['path' => $target]);

            return ['success' => true, 'path' => $target];
        } catch (\Exception $e) {
            $this->log->error('GeoIP Database Update (MaxMind): Error during update process.', ['exception' => $e->getMessage()]);
            throw $e;
        } finally {
            $this->log->info('GeoIP Database Update (MaxMind): Cleaning up temporary files.');
            if (isset($tmpTar) && is_file($tmpTar)) @unlink($tmpTar);
            if (isset($tmpDir) && is_dir($tmpDir)) $this->deleteDirectory($tmpDir);
            if (isset($tarPath) && is_file($tarPath)) @unlink($tarPath);
        }
    }

    private function downloadFromCustomUrl(string $url): array
    {
        $target = $this->geoSettings->databasePath();
        if (!$target) {
            $target = $this->paths->storage . '/geoip/custom.mmdb';
        }

        $this->log->info('GeoIP Database Update (Custom URL): Preparing download.', ['target' => $target, 'url' => $url]);

        $dir = dirname($target);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->log->error('GeoIP Database Update (Custom URL): Unable to create target directory.', ['dir' => $dir]);
            throw new \Exception("Unable to create directory: {$dir}");
        }

        $client = new Client([
            'timeout' => 120,
            'connect_timeout' => 20,
        ]);

        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $isGz = ($ext === 'gz');
        
        $this->log->info('GeoIP Database Update (Custom URL): Detected file type.', ['is_gz' => $isGz, 'ext' => $ext]);

        $tmpDownload = $this->paths->storage . '/geoip/tmp_custom_download_' . bin2hex(random_bytes(4)) . ($isGz ? '.gz' : '.tmp');
        $tmpOut = $target . '.tmp';

        try {
            $this->log->info('GeoIP Database Update (Custom URL): Downloading file.');
            $res = $client->get($url, [
                'sink' => $tmpDownload,
                'http_errors' => false,
            ]);

            if ($res->getStatusCode() !== 200) {
                $this->log->error('GeoIP Database Update (Custom URL): Download failed.', ['status' => $res->getStatusCode()]);
                throw new \Exception('Download failed with status ' . $res->getStatusCode());
            }

            $this->log->info('GeoIP Database Update (Custom URL): Download complete. Processing file.');

            if ($isGz) {
                $this->log->info('GeoIP Database Update (Custom URL): Decompressing GZ file using gzopen.');
                
                $gzHandler = gzopen($tmpDownload, 'rb');
                if (!$gzHandler) {
                    throw new \Exception('Failed to open .gz file for decompression.');
                }

                $finalHandler = fopen($tmpOut, 'wb');
                if (!$finalHandler) {
                    gzclose($gzHandler);
                    throw new \Exception('Failed to create output file.');
                }

                while (!gzeof($gzHandler)) {
                    fwrite($finalHandler, gzread($gzHandler, 8192));
                }

                gzclose($gzHandler);
                fclose($finalHandler);
                
                // 解压成功后立即删除原始的 .gz 临时文件
                @unlink($tmpDownload);
            } else {
                $this->log->info('GeoIP Database Update (Custom URL): Moving downloaded file to target.');
                if (!@rename($tmpDownload, $tmpOut)) {
                    $this->log->warning('GeoIP Database Update (Custom URL): Rename downloaded file failed, retrying.');
                    @unlink($tmpOut);
                    if (!@rename($tmpDownload, $tmpOut)) {
                        $this->log->error('GeoIP Database Update (Custom URL): Failed to move database into place.');
                        throw new \Exception('Failed to move database into place.');
                    }
                }
            }

            if (!@rename($tmpOut, $target)) {
                $this->log->warning('GeoIP Database Update (Custom URL): Finalize rename failed, retrying.');
                @unlink($target);
                if (!@rename($tmpOut, $target)) {
                    $this->log->error('GeoIP Database Update (Custom URL): Failed to finalize database update.');
                    throw new \Exception('Failed to finalize database update.');
                }
            }

            $this->settings->set('momokoudai-geoip-local.db_path', $target);
            $this->settings->set('momokoudai-geoip-local.auto_update.last_run', time());

            $this->log->info('GeoIP Database Update (Custom URL): Update successful.', ['path' => $target]);

            return ['success' => true, 'path' => $target];
        } catch (\Exception $e) {
            $this->log->error('GeoIP Database Update (Custom URL): Error during update process.', ['exception' => $e->getMessage()]);
            throw $e;
        } finally {
            $this->log->info('GeoIP Database Update (Custom URL): Cleaning up temporary files.');
            @unlink($tmpDownload);
            @unlink($tmpOut);
        }
    }

    private function findMmdbInDir(string $dir): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'mmdb') {
                return $file->getPathname();
            }
        }
        return null;
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}