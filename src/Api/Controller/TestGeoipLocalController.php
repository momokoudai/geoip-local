<?php

namespace momokoudai\GeoipLocal\Api\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use momokoudai\GeoipLocal\Support\GeoipResolver;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TestGeoipLocalController implements RequestHandlerInterface
{
    public function __construct(private GeoipResolver $resolver)
    {
    }

    public function handle(ServerRequestInterface $request): JsonResponse
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $query = $request->getQueryParams();
        $ip = (string) ($query['ip'] ?? '');

        if ($ip === '') {
            return new JsonResponse(['error' => 'Missing ip'], 422);
        }

        $result = $this->resolver->resolve($ip);

        return new JsonResponse([
            'ip' => $ip,
            'result' => $result->toArray(),
        ]);
    }
}

