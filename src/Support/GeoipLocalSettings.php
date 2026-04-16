<?php

namespace momokoudai\GeoipLocal\Support;

use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;

class GeoipLocalSettings
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private Paths $paths
    ) {
    }

    public function databaseDriver(): string
    {
        // maxmind-mmdb | dbip-mmdb | ip2location-bin
        return (string) ($this->settings->get('momokoudai-geoip-local.driver') ?? 'maxmind-mmdb');
    }

    public function databasePath(): ?string
    {
        $path = $this->settings->get('momokoudai-geoip-local.db_path');
        if ($path) {
            $path = trim((string) $path);
            if ($path === '') return null;

            // 如果是相对路径，则相对于 Flarum 根目录（即 Paths::base）解析
            if (!str_starts_with($path, '/') && !preg_match('/^[a-zA-Z]:/', $path)) {
                $path = $this->paths->base . '/' . $path;
            }
            
            return $path;
        }

        // Fallback to default path in storage/geoip/
        $driver = $this->databaseDriver();
        $ext = $driver === 'ip2location-bin' ? 'bin' : 'mmdb';
        $defaultPath = $this->paths->storage . "/geoip/geoip-local.{$ext}";

        return is_file($defaultPath) ? $defaultPath : null;
    }

    public function maxmindAccountId(): ?string
    {
        $v = $this->settings->get('momokoudai-geoip-local.maxmind.account_id');
        return $v ? trim((string) $v) : null;
    }

    public function maxmindLicenseKey(): ?string
    {
        $v = $this->settings->get('momokoudai-geoip-local.maxmind.license_key');
        return $v ? trim((string) $v) : null;
    }

    public function autoUpdateEnabled(): bool
    {
        return (bool) ($this->settings->get('momokoudai-geoip-local.auto_update.enabled') ?? false);
    }

    public function autoUpdateFrequencyHours(): int
    {
        $hours = (int) ($this->settings->get('momokoudai-geoip-local.auto_update.frequency_hours') ?? 24);
        return max(1, min(24 * 14, $hours));
    }

    public function customDownloadUrl(): ?string
    {
        $url = $this->settings->get('momokoudai-geoip-local.auto_update.custom_url');
        if (!$url) {
            return null;
        }

        $url = trim((string) $url);

        return $url === '' ? null : $url;
    }
}

