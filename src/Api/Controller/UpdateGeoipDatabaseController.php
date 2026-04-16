<?php

namespace momokoudai\GeoipLocal\Api\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use momokoudai\GeoipLocal\Support\GeoipDatabaseUpdater;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateGeoipDatabaseController implements RequestHandlerInterface
{
    public function __construct(
        private GeoipDatabaseUpdater $updater
    ) {
    }

    public function handle(ServerRequestInterface $request): JsonResponse
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        try {
            // 增加执行时间限制，防止大文件下载超时
            @set_time_limit(300);
            
            $result = $this->updater->update(true);
            return new JsonResponse([
                'ok' => true,
                'message' => 'Updated: ' . $result['path'],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}