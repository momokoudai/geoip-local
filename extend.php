<?php

/*
 * This file is part of momokoudai/geoip-local.
 *
 * Copyright (c) momokoudai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Extend;
use Illuminate\Console\Scheduling\Event as ScheduleEvent;
use momokoudai\GeoipLocal\Api\Serializer\AddGeoipLocalToPost;
use momokoudai\GeoipLocal\Api\Controller\TestGeoipLocalController;
// use momokoudai\GeoipLocal\Api\Controller\UploadGeoipDatabaseController;
use momokoudai\GeoipLocal\Api\Controller\GeoipLocalQueryController;
use momokoudai\GeoipLocal\Api\Controller\UpdateGeoipDatabaseController;
use momokoudai\GeoipLocal\Console\UpdateGeoipDatabaseCommand;
use momokoudai\GeoipLocal\Providers\GeoipLocalServiceProvider;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\ServiceProvider())
        ->register(GeoipLocalServiceProvider::class),

    // Attach derived (non-IP) location info to posts.
    (new Extend\ApiSerializer(\Flarum\Api\Serializer\PostSerializer::class))
        ->attributes(AddGeoipLocalToPost::class),

    (new Extend\Console())
        ->command(UpdateGeoipDatabaseCommand::class)
        ->schedule('geoip-local:update-db', function (ScheduleEvent $event) {
            // Let the command decide whether to skip based on settings.
            // Use hourly as base, but command will check actual frequency setting
            $event->hourly();
        }),

    (new Extend\Routes('api'))
        // ->post('/geoip-local/upload', 'momokoudai-geoip-local.api.upload', UploadGeoipDatabaseController::class)
        ->get('/geoip-local/test', 'momokoudai-geoip-local.api.test', TestGeoipLocalController::class)
        ->get('/geoip-local/query/{ip}', 'momokoudai-geoip-local.api.query', GeoipLocalQueryController::class)
        ->post('/geoip-local/update-db', 'momokoudai-geoip-local.api.update-db', UpdateGeoipDatabaseController::class),
];

