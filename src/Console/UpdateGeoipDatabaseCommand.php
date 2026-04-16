<?php

namespace momokoudai\GeoipLocal\Console;

use Illuminate\Console\Command;
use momokoudai\GeoipLocal\Support\GeoipDatabaseUpdater;

class UpdateGeoipDatabaseCommand extends Command
{
    protected $signature = 'geoip-local:update-db {--force : Force update even if recently updated}';
    protected $description = 'Download/update local GeoIP database (GeoIP Local)';

    public function __construct(
        private GeoipDatabaseUpdater $updater
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $force = (bool) $this->option('force');
            $result = $this->updater->update($force);
            $this->info('Updated: ' . $result['path']);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Update failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
