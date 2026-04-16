<?php

namespace momokoudai\GeoipLocal\Api\Controller;

use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UploadGeoipDatabaseController implements RequestHandlerInterface
{
    public function __construct(
        private Paths $paths,
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(ServerRequestInterface $request): JsonResponse
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $files = $request->getUploadedFiles();
        $file = $files['file'] ?? null;

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 422);
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return new JsonResponse(['error' => 'Upload failed'], 422);
        }

        $clientName = $file->getClientFilename() ?: 'geoip.db';
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['mmdb', 'bin'], true)) {
            return new JsonResponse(['error' => 'Only .mmdb or .bin is allowed'], 422);
        }

        $dir = $this->paths->storage.'/geoip-local';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return new JsonResponse(['error' => 'Cannot create storage directory'], 500);
        }

        $safeBase = Str::slug(pathinfo($clientName, PATHINFO_FILENAME));
        if ($safeBase === '') {
            $safeBase = 'geoip';
        }

        $target = $dir.'/'.$safeBase.'-'.date('Ymd-His').'.'.$ext;
        $file->moveTo($target);

        $this->settings->set('momokoudai-geoip-local.db_path', $target);

        return new JsonResponse([
            'ok' => true,
            'path' => $target,
        ]);
    }
}

