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
        $fileName = $this->settings->get('momokoudai-geoip-local.db_path');
        if ($fileName) {
            $fileName = trim((string) $fileName);
            if ($fileName === '') return null;

            // 验证扩展名必须是 mmdb 或 bin
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['mmdb', 'bin'], true)) {
                return null;
            }

            // 只允许安全字符：字母、数字、下划线、连字符、点
            $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($fileName));
            if ($safeName === '' || !str_ends_with($safeName, '.' . $ext)) {
                return null;
            }

            // 只能使用 storage/geoip/ 目录下的文件
            return $this->paths->storage . '/geoip/' . $safeName;
        }

        // Fallback: 根据驱动类型使用默认文件名
        $driver = $this->databaseDriver();
        $ext = $driver === 'ip2location-bin' ? 'bin' : 'mmdb';
        $defaultFileName = "custom.{$ext}";
        $defaultPath = $this->paths->storage . "/geoip/{$defaultFileName}";
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

    public function databaseFileName(): ?string
    {
        $fileName = $this->settings->get('momokoudai-geoip-local.db_path');
        if ($fileName) {
            $fileName = trim((string) $fileName);
            if ($fileName === '') return null;
            
            // 验证扩展名
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['mmdb', 'bin'], true)) {
                return null;
            }
            
            // 只允许安全字符：字母、数字、下划线、连字符、点
            $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($fileName));
            if ($safeName === '' || !str_ends_with($safeName, '.' . $ext)) {
                return null;
            }
            
            return $safeName;
        }
        return null;
    }
}