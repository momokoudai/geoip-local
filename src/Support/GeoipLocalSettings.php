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

            // 如果是绝对路径且文件存在，直接使用
            if (str_starts_with($path, '/') && is_file($path)) {
                return $path;
            }

            // 相对路径统一基于 storage 目录解析
            if (!str_starts_with($path, '/') && !preg_match('/^[a-zA-Z]:/', $path)) {
                return $this->paths->storage . '/' . $path;
            }

            // 其他情况直接返回（可能是错误的绝对路径）
            return $path;
        }

        // Fallback to default path in storage/geoip/
        $driver = $this->databaseDriver();
        $ext = $driver === 'ip2location-bin' ? 'bin' : 'mmdb';
        
        // 优先检查 custom.mmdb（自动更新默认文件名）
        $customPath = $this->paths->storage . "/geoip/custom.{$ext}";
        if (is_file($customPath)) {
            return $customPath;
        }
        
        // 其次检查 geoip-local.{ext}（旧版本或手动上传）
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
