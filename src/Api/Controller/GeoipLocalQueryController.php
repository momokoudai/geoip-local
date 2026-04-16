<?php

namespace momokoudai\GeoipLocal\Api\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use momokoudai\GeoipLocal\Support\GeoipResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GeoipLocalQueryController implements RequestHandlerInterface
{
    public function __construct(private GeoipResolver $resolver)
    {
    }

    /**
     * Handle IP geolocation query request.
     * This endpoint is used by fof/geoip to test the local service.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the IP parameter from the route
        $ip = $request->getAttribute('ip');
        
        // Fallback: try to get from query parameters or URI path
        if (!$ip) {
            $uri = $request->getUri();
            $path = $uri->getPath();
            // Extract IP from path like /api/geoip-local/query/8.8.8.8
            if (preg_match('#/query/([^/]+)$#', $path, $matches)) {
                $ip = urldecode($matches[1]);
            }
        }

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return new JsonResponse([
                'error' => 'Invalid IP address',
                'received' => $ip,
                'path' => $request->getUri()->getPath(),
            ], 400);
        }

        try {
            $result = $this->resolver->resolve($ip);

            if (!$result) {
                return new JsonResponse([
                    'error' => 'Unable to resolve IP',
                    'ip' => $ip,
                ], 404);
            }

            return new JsonResponse([
                'ip' => $ip,
                'country_code' => $result->countryCode,
                'flag_country_code' => $result->flagCountryCode,
                'country_name' => $result->countryName ?? null,
                'region' => $result->region ?? null,
                'city' => $result->city ?? null,
                'latitude' => $result->latitude ?? null,
                'longitude' => $result->longitude ?? null,
                'data_provider' => 'geoip-local',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Resolution failed: ' . $e->getMessage(),
                'ip' => $ip,
            ], 500);
        }
    }
}
