<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\ProfileRepository;
use DomainException;
use Throwable;

final class AvatarService
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const MAX_DIMENSION = 4000;
    private const DEFAULT_AVATAR = '/uploads/avatars/default-avatar.jpg';

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private ProfileRepository $profiles = new ProfileRepository(),
        private string $uploadDir = __DIR__ . '/../../public/uploads/avatars'
    ) {}

    /**
     * @param array<string,mixed> $file
     * @throws DomainException
     */
    public function updateAvatar(int $userId, string $publicId, array $file): string
    {
        $this->validateUpload($file);

        $tmpPath = (string)($file['tmp_name'] ?? '');
        $mime = $this->detectMime($tmpPath);
        $ext = self::ALLOWED_MIME_TYPES[$mime] ?? null;
        if ($ext === null) {
            throw new DomainException('avatar_invalid_type');
        }

        $this->validateDimensions($tmpPath);

        $fileName = $publicId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $relativePath = '/uploads/avatars/' . $fileName;
        $targetPath = $this->targetPath($fileName);

        $this->ensureUploadDir();

        try {
            $this->reencodeImage($mime, $tmpPath, $targetPath);
        } catch (DomainException $e) {
            $this->deleteAvatarFileIfCustom($relativePath);
            throw $e;
        }

        $oldAvatarPath = $this->profiles->findAvatarPathForUser($userId);

        try {
            $this->profiles->updateAvatar($userId, $relativePath);
        } catch (Throwable $e) {
            $this->deleteAvatarFileIfCustom($relativePath);
            throw $e;
        }

        $this->deleteAvatarFileIfCustom($oldAvatarPath);

        return $relativePath;
    }

    /**
     * @param array<string,mixed> $file
     */
    private function validateUpload(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new DomainException('upload_failed');
        }

        if ((int)($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new DomainException('avatar_too_large');
        }

        if (empty($file['tmp_name']) || !is_file((string)$file['tmp_name'])) {
            throw new DomainException('upload_failed');
        }
    }

    private function detectMime(string $tmpPath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($tmpPath) ?: 'application/octet-stream';
    }

    private function validateDimensions(string $tmpPath): void
    {
        $size = @getimagesize($tmpPath);
        if (!$size) {
            throw new DomainException('avatar_invalid_dimensions');
        }

        [$width, $height] = $size;
        if (!$width || !$height || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw new DomainException('avatar_invalid_dimensions');
        }
    }

    private function ensureUploadDir(): void
    {
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
            throw new DomainException('avatar_processing_failed');
        }
    }

    private function targetPath(string $fileName): string
    {
        return rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
    }

    private function reencodeImage(string $mime, string $tmpPath, string $targetPath): void
    {
        try {
            switch ($mime) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($tmpPath);
                    if (!$img || !imagejpeg($img, $targetPath, 85)) {
                        throw new DomainException('avatar_processing_failed');
                    }
                    imagedestroy($img);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($tmpPath);
                    if (!$img || !imagepng($img, $targetPath, 8)) {
                        throw new DomainException('avatar_processing_failed');
                    }
                    imagedestroy($img);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($tmpPath);
                    if (!$img || !imagegif($img, $targetPath)) {
                        throw new DomainException('avatar_processing_failed');
                    }
                    imagedestroy($img);
                    break;
                case 'image/webp':
                    $img = imagecreatefromwebp($tmpPath);
                    if (!$img || !imagewebp($img, $targetPath, 85)) {
                        throw new DomainException('avatar_processing_failed');
                    }
                    imagedestroy($img);
                    break;
                default:
                    throw new DomainException('avatar_invalid_type');
            }
        } catch (DomainException $e) {
            if (isset($img) && $img) {
                imagedestroy($img);
            }

            throw $e;
        } catch (Throwable $e) {
            if (isset($img) && $img) {
                imagedestroy($img);
            }

            throw new DomainException('avatar_processing_failed');
        }
    }

    public function deleteAvatarFileIfCustom(?string $avatarPath): void
    {
        if (!$avatarPath || $avatarPath === 'default-avatar.jpg' || $avatarPath === self::DEFAULT_AVATAR) {
            return;
        }

        $fullPath = realpath(__DIR__ . '/../../public' . $avatarPath);
        $allowedDir = realpath($this->uploadDir);
        $allowedPrefix = $allowedDir ? rtrim($allowedDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : null;

        if ($fullPath && $allowedPrefix && str_starts_with($fullPath, $allowedPrefix) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}
