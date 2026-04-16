<?php

namespace momokoudai\GeoipLocal\Jobs;

use Flarum\Queue\AbstractJob;
use momokoudai\GeoipLocal\Support\GeoipDatabaseUpdater;
use Psr\Log\LoggerInterface;

class UpdateGeoipDatabaseJob extends AbstractJob
{
    public function handle(GeoipDatabaseUpdater $updater, LoggerInterface $log): void
    {
        $log->info('[geoip-local] Job started');
        try {
            $result = $updater->update(true); // 手动触发，强制更新
            $log->info('[geoip-local] Job completed successfully', ['path' => $result['path']]);
        } catch (\Throwable $e) {
            $log->error('[geoip-local] Job failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}