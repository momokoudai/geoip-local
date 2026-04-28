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
        // 只保存文件名，不带路径
        $fileName = basename($clientName);
        
        // 验证扩展名
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['mmdb', 'bin'], true)) {
            return new JsonResponse(['error' => 'Only .mmdb or .bin is allowed'], 422);
        }

        // 只允许安全字符：字母、数字、下划线、连字符、点
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $fileName);
        if ($safeName === '' || !str_ends_with($safeName, '.' . $ext)) {
            return new JsonResponse(['error' => 'Invalid filename'], 422);
        }

        // 确保目录存在
        $dir = $this->paths->storage.'/geoip';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return new JsonResponse(['error' => 'Cannot create storage directory'], 500);
        }
        
        $target = $dir . '/' . $safeName;
        
        // 如果文件已存在，先删除（覆盖）
        if (file_exists($target)) {
            @unlink($target);
        }
        
        $file->moveTo($target);

        // 保存文件名（不带路径）
        $this->settings->set('momokoudai-geoip-local.db_path', $fileName);

        return new JsonResponse([
                    'ok' => true,
            'path' => $target,
        ]);
    }
}
