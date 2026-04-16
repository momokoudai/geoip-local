<?php

namespace momokoudai\GeoipLocal\Api\Serializer;

use Flarum\Api\Serializer\PostSerializer;
use momokoudai\GeoipLocal\Support\GeoipResolver;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class AddGeoipLocalToPost
{
    public function __construct(
        private GeoipResolver $resolver,
        private CacheRepository $cache
    ) {
    }

    /**
     * @param PostSerializer $serializer
     * @param \Flarum\Post\Post $post
     */
    public function __invoke(PostSerializer $serializer, $post): array
    {
        $ip = $post->ip_address ?? null;
        if (!is_string($ip) || $ip === '') {
            return [
                'geoipLocal' => null,
            ];
        }

        // Do not expose IP; only expose derived location codes.
        $cacheKey = "momokoudai-geoip-local.post.ip.$ip";

        $data = $this->cache->remember($cacheKey, 60 * 60 * 6, function () use ($ip) {
            return $this->resolver->resolve($ip)->toArray();
        });

        return [
            'geoipLocal' => $data,
        ];
    }
}

