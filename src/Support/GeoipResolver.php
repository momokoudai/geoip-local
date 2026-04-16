<?php

namespace momokoudai\GeoipLocal\Support;

use GeoIp2\Database\Reader as GeoIp2Reader;
use IP2Location\Database as IP2LocationDb;
use Psr\Log\LoggerInterface;

// Import IP2Location constants
if (!defined('IP2LOCATION_FILE_IO')) {
    define('IP2LOCATION_FILE_IO', 1);
}
if (!defined('IP2LOCATION_COUNTRY_SHORT')) {
    define('IP2LOCATION_COUNTRY_SHORT', 1);
}
if (!defined('IP2LOCATION_REGION')) {
    define('IP2LOCATION_REGION', 8);

}

class GeoipResolver
{
    public function __construct(
        private GeoipLocalSettings $settings,
        private LoggerInterface $log
    ) {
    }

    public function resolve(string $ip): GeoipLocalResult
    {
        $driver = $this->settings->databaseDriver();

        try {
            return match ($driver) {
                'maxmind-mmdb', 'dbip-mmdb' => $this->resolveMmdb($ip),
                'ip2location-bin' => $this->resolveIp2LocationBin($ip),
                default => new GeoipLocalResult(null, null, null, null),
            };
        } catch (\Throwable $e) {
            $this->log->error('[geoip-local] Resolve failed', [
                'driver' => $driver,
                'ip' => $ip,
                'exception' => $e->getMessage(),
            ]);

            return new GeoipLocalResult(null, null, null, null);
        }
    }

    private function resolveMmdb(string $ip): GeoipLocalResult
    {
        $path = $this->settings->databasePath();
        if (!$path || !is_file($path)) {
            $this->log->warning('[geoip-local] Database file not found', ['path' => $path]);
            return new GeoipLocalResult(null, null, null, null);
        }

        $reader = new GeoIp2Reader($path);

        // We prefer city() because it provides subdivision info. If the DB
        // doesn't support it, we gracefully fall back to country().
        try {
            $record = $reader->city($ip);
            $countryIso = $record->country->isoCode ?: null;
            
            // $this->log->info('[geoip-local] MMDB city() result', [
            //     'ip' => $ip,
            //     'country' => $countryIso,
            //     'subdivisions_count' => count($record->subdivisions ?? []),
            //     'subdivisions' => array_map(function($sub) {
            //         return [
            //             'isoCode' => $sub->isoCode ?? null,
            //             'name' => $sub->name ?? null,
            //         ];
            //     }, $record->subdivisions ?? []),
            //     'city' => $record->city->name ?? null,
            //     'database_path' => $path,
            //     'database_size' => filesize($path),
            // ]);

            // If the country is CN, try to read province (first subdivision).
            $subdivisionIso = null;
            if ($countryIso === 'CN' && !empty($record->subdivisions) && isset($record->subdivisions[0])) {
                $iso = $record->subdivisions[0]->isoCode ?: null;
                if ($iso) {
                    $subdivisionIso = 'CN-'.$iso;
                    // $this->log->info('[geoip-local] Found subdivision', ['subdivision' => $subdivisionIso]);
                }
            }

            // HK/MO/TW special: show as China Hong Kong/Macau/Taiwan and use CN flag.
            $special = $this->specialCnRegionFromCountry($countryIso);
            if ($special !== null) {
                return new GeoipLocalResult($countryIso, 'CN', null, $special);
            }

            return new GeoipLocalResult($countryIso, $countryIso, $subdivisionIso, null);
        } catch (\Throwable $e) {
            $this->log->warning('[geoip-local] city() failed, fallback to country()', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            
            $record = $reader->country($ip);
            $countryIso = $record->country->isoCode ?: null;

            $special = $this->specialCnRegionFromCountry($countryIso);
            if ($special !== null) {
                return new GeoipLocalResult($countryIso, 'CN', null, $special);
            }

            return new GeoipLocalResult($countryIso, $countryIso, null, null);
        }
    }

    private function resolveIp2LocationBin(string $ip): GeoipLocalResult
    {
        $path = $this->settings->databasePath();
        if (!$path || !is_file($path)) {
            return new GeoipLocalResult(null, null, null, null);
        }

        $db = new IP2LocationDb($path, IP2LOCATION_FILE_IO);
        $rec = $db->lookup($ip, IP2LOCATION_COUNTRY_SHORT | IP2LOCATION_REGION);

        $countryIso = $rec['countryCode'] ?? $rec['country_code'] ?? null;
        if (is_string($countryIso)) {
            $countryIso = strtoupper($countryIso);
        } else {
            $countryIso = null;
        }

        $special = $this->specialCnRegionFromCountry($countryIso);
        if ($special !== null) {
            return new GeoipLocalResult($countryIso, 'CN', null, $special);
        }

        $subdivisionIso = null;
        // IP2Location "regionName" is not ISO. We can't reliably map all provinces without a table.
        // For now, only attempt a best-effort mapping for CN by region name (Chinese names often match).
        if ($countryIso === 'CN') {
            $regionName = $rec['regionName'] ?? $rec['region_name'] ?? null;
            if (is_string($regionName)) {
                $subdivisionIso = $this->guessChinaSubdivisionFromName($regionName);
            }
        }

        return new GeoipLocalResult($countryIso, $countryIso, $subdivisionIso, null);
    }

    private function specialCnRegionFromCountry(?string $countryIso): ?string
    {
        return match ($countryIso) {
            'HK' => 'hong_kong',
            'MO' => 'macau',
            'TW' => 'taiwan',
            default => null,
        };
    }

    private function guessChinaSubdivisionFromName(string $regionName): ?string
    {
        $regionName = trim($regionName);
        if ($regionName === '') {
            return null;
        }

        // Normalize common suffixes.
        $regionName = str_replace(['省', '市', '自治区', '特别行政区'], '', $regionName);

        foreach (ChinaProvinceMap::MAP as $code => [$zh, $en]) {
            if ($regionName === $zh) {
                return $code;
            }
        }

        return null;
    }
}

