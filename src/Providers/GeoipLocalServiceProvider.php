<?php

namespace momokoudai\GeoipLocal\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;
use FoF\GeoIP\Api\GeoIP as FoFGeoIP;
use momokoudai\GeoipLocal\FoF\Services\GeoipLocal;
use momokoudai\GeoipLocal\Support\GeoipLocalSettings;
use momokoudai\GeoipLocal\Support\GeoipResolver;
use Psr\Log\LoggerInterface;

class GeoipLocalServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(GeoipLocalSettings::class, function () {
            /** @var \Flarum\Settings\SettingsRepositoryInterface $settings */
            $settings = $this->container->make('flarum.settings');
            /** @var \Flarum\Foundation\Paths $paths */
            $paths = $this->container->make(Paths::class);

            return new GeoipLocalSettings($settings, $paths);
        });

        $this->container->singleton(GeoipResolver::class, function () {
            return new GeoipResolver(
                $this->container->make(GeoipLocalSettings::class),
                $this->container->make(LoggerInterface::class)
            );
        });

        $this->container->singleton(\momokoudai\GeoipLocal\Support\GeoipDatabaseUpdater::class, function () {
            return new \momokoudai\GeoipLocal\Support\GeoipDatabaseUpdater(
                $this->container->make(GeoipLocalSettings::class),
                $this->container->make('flarum.settings'),
                $this->container->make(Paths::class),
                $this->container->make(LoggerInterface::class)
            );
        });

        // Register GeoipLocal service with container dependency injection
        $this->container->bind(GeoipLocal::class, function () {
            return new GeoipLocal(
                $this->container->make(GeoipResolver::class),
                $this->container->make(UrlGenerator::class)
            );
        });

        // Expose our provider in FoF GeoIP data source selector.
        FoFGeoIP::$services['geoip-local'] = GeoipLocal::class;
    }
}
