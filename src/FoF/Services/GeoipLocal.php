<?php

namespace momokoudai\GeoipLocal\FoF\Services;

use Flarum\Http\UrlGenerator;
use FoF\GeoIP\Api\ServiceResponse;
use FoF\GeoIP\Concerns\ServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use momokoudai\GeoipLocal\Support\GeoipResolver;

class GeoipLocal implements ServiceInterface
{
    /**
     * The host/endpoint URL for the service.
     * For local database services, we use a placeholder to prevent cURL errors.
     */
    public string $host = 'http://127.0.0.1';

    /**
     * HTTP client instance.
     * For local database services, this is not used but required by fof/geoip.
     */
    public Client $client;

    public function __construct(
        private GeoipResolver $resolver,
        private UrlGenerator $url
    ) {
        // Initialize client to satisfy fof/geoip requirements
        $this->client = new Client();
    }

    /**
     * Get geolocation data for a single IP address.
     */
    public function get(string $ip): ?ServiceResponse
    {
        $r = $this->resolver->resolve($ip);

        $resp = new ServiceResponse('geoip-local');
        $resp->setIP($ip);

        // For FoF storage/flags, we only set country_code (and keep special HK/MO/TW as CN flag via flagCountryCode).
        $resp->setCountryCode($r->flagCountryCode ?? $r->countryCode);
        $resp->setDataProvider('geoip-local');

        return $resp;
    }

    /**
     * Get geolocation data for multiple IP addresses.
     */
    public function getBatch(array $ips): array
    {
        $out = [];

        foreach ($ips as $ip) {
            if (!is_string($ip) || $ip === '') {
                continue;
            }

            $resp = $this->get($ip);
            if ($resp) {
                $out[] = $resp;
            }
        }

        return $out;
    }

    /**
     * Whether batch processing is supported.
     */
    public function batchSupported(): bool
    {
        return true;
    }

    /**
     * Build the API request URL.
     * Returns the local API endpoint for fof/geoip to query.
     * Uses the forum's configured URL to ensure correct web server access.
     */
    public function buildUrl(string $ip): string
    {
        // Get the forum URL from Flarum's configuration
        // This returns the web-accessible URL (Nginx/OpenResty), not PHP-FPM address
        $forumUrl = $this->url->to('forum')->base();
        
        // Remove trailing slash if present to avoid double slashes
        $forumUrl = rtrim($forumUrl, '/');
        
        return $forumUrl . '/api/geoip-local/query/' . urlencode($ip);
    }

    /**
     * Get HTTP request options (not used for local database, but required by fof/geoip).
     * Returns an empty array since we don't make HTTP requests.
     */
    public function getRequestOptions(): array
    {
        // Local database doesn't use HTTP requests
        return [];
    }

    /**
     * Get the service configuration/credentials (required by fof/geoip).
     * Returns an array of setting keys that this service uses.
     */
    public function getConfigKeys(): array
    {
        return [
            'momokoudai-geoip-local.driver',
            'momokoudai-geoip-local.db_path',
            'momokoudai-geoip-local.maxmind.account_id',
            'momokoudai-geoip-local.maxmind.license_key',
        ];
    }
}
