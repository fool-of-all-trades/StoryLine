<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use App\Services\StoryService;
use DomainException;
use Throwable;
use App\Security\Csrf;

final class UserController
{
    private static function userService(): UserService
    {
        return new UserService();
    }

    private static function storyService(): StoryService
    {
        return new StoryService();
    }

    private static function json(mixed $data, int $code=200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function profile(array $params): void
    {
        $id = (int)($params['user_id'] ?? 0);
        if ($id <= 0) { 
            http_response_code(404); 
            echo 'User not found'; 
            return; 
        }

        $userService  = self::userService();
        $user = $userService->findById($id);
        if (!$user) { 
            http_response_code(404); 
            echo 'User not found'; 
            return; 
        }

        $title = "StoryLine — " . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
        include __DIR__ . '/../../public/views/user.php';
    }

    public static function profileByPublicId(array $params): void
    {
        $publicId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicId)) {
            http_response_code(404); 
            echo 'User not found'; 
            return;
        }

        $userService  = self::userService();
        $user = $userService->findByPublicId($publicId);
        if (!$user) { 
            http_response_code(404); 
            echo 'User not found'; 
            return; 
        }

        $title = "StoryLine — " . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
        include __DIR__ . '/../../public/views/user.php';
    }

    public static function profileData(array $params): void
    {
        $publicId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicId)) {
            self::json(['error' => 'not_found'], 404);
        }

        $userService = self::userService();
        $user = $userService->findByPublicId($publicId);
        if (!$user) {
            self::json(['error' => 'not_found'], 404);
        }

        $storyService = self::storyService();
        try {
            $data = $storyService->getProfileDataForUser($user->id);

            self::json([
                'user'   => [
                    'username'  => $user->username,
                    'public_id' => $user->public_id,
                    'created_at'=> $user->createdAt->format('c'),
                ],
                'data'  => $data,
            ]);
        } catch (Throwable $e) {
            self::json(['error' => 'internal_error'], 500);
        }
    }

    public static function profileStories(array $params): void
    {
        $publicId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicId)) {
            self::json(['error' => 'not_found'], 404);
        }

        $userService = self::userService();
        $userPrivateID = $userService->findPrivateIdByPublicId($publicId);
        if (!$userPrivateID) {
            self::json(['error' => 'not_found'], 404);
        }

        $storyService = self::storyService();
        try {
            $page  = (int)($_GET['page']  ?? 1);
            $limit = (int)($_GET['limit'] ?? 8);

            $page  = max(1, $page);
            $limit = max(1, min(8, $limit));

            // how many stories to skip
            $offset = ($page - 1) * $limit;

            $storiesPayload = $storyService->getStoresForUser($userPrivateID, $limit, $offset);

            self::json([
                'page' => $page,
                'limit' => $limit,
                'stories' => $storiesPayload,
            ]);
        } catch (Throwable $e) {
            self::json(['error' => 'internal_error'], 500);
        }
    }

    public static function updateFavoriteQuote(): void
    {
        Csrf::verify();

        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sentence = $_POST['favorite_quote_sentence'] ?? '';
        $book = $_POST['favorite_quote_book'] ?? '';
        $author = $_POST['favorite_quote_author'] ?? '';

        $userService = self::userService();

        try {
            $userService->setFavoriteQuote((int)$currentUser['id'], $sentence, $book, $author);
            http_response_code(200);
            echo json_encode([
                'status' => 'ok',
                'favorite_quote' => [
                    'sentence' => $sentence,
                    'book'     => $book,
                    'author'   => $author,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            http_response_code(422);
            echo json_encode(['error' => $code], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
        }
    }
    public static function updateUsername(): void
    {
        Csrf::verify();

        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $username = $_POST['username'] ?? '';

        $userService = self::userService();

        try {
            $userService->changeUsername((int)$currentUser['id'], $username);
            http_response_code(200);
            echo json_encode([
                'status' => 'ok',
                'username' => $username,
            ], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            http_response_code(422);
            echo json_encode(['error' => $code], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
        }
    }

    public static function updatePassword(): void
    {
        Csrf::verify();

        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $password = $_POST['password'] ?? '';

        $userService = self::userService();

        try {
            $userService->changePassword((int)$currentUser['id'], $password);
            http_response_code(200);
            echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
        } catch (DomainException $e) {
            $code = $e->getMessage();
            http_response_code(422);
            echo json_encode(['error' => $code], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'internal_error'], JSON_UNESCAPED_UNICODE);
        }
    }

    public static function updateAvatar(): void
    {
        Csrf::verify();
        header('Content-Type: application/json; charset=utf-8');

        $currentUser = current_user();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'upload_failed'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $file = $_FILES['avatar'];

        // It's just a profile picture, so limit size to 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            http_response_code(422);
            echo json_encode(['error' => 'avatar_too_large'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validation, cuz I don't trust the client
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            http_response_code(422);
            echo json_encode(['error' => 'avatar_invalid_type'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validate dimensions, no dimensions? that's not a photo
        list($width, $height) = @getimagesize($file['tmp_name']);
        if (!$width || !$height || $width > 4000 || $height > 4000) {
            http_response_code(422);
            echo json_encode(['error' => 'avatar_invalid_dimensions'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $ext = $allowed[$mime];

        $publicId = $currentUser['public_id']; // make sure it's the current user
        $fileName = $publicId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        $relativePath = '/uploads/avatars/' . $fileName;
        $targetDir    = __DIR__ . '/../../public/uploads/avatars';
        $targetPath   = $targetDir . '/' . $fileName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        // Re-encode image to strip malicious content
        try {
            switch ($mime) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($file['tmp_name']);
                    if (!$img) throw new \Exception('Invalid JPEG');
                    imagejpeg($img, $targetPath, 85);
                    imagedestroy($img);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($file['tmp_name']);
                    if (!$img) throw new \Exception('Invalid PNG');
                    imagepng($img, $targetPath, 8);
                    imagedestroy($img);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($file['tmp_name']);
                    if (!$img) throw new \Exception('Invalid GIF');
                    imagegif($img, $targetPath);
                    imagedestroy($img);
                    break;
                case 'image/webp':
                    $img = imagecreatefromwebp($file['tmp_name']);
                    if (!$img) throw new \Exception('Invalid WebP');
                    imagewebp($img, $targetPath, 85);
                    imagedestroy($img);
                    break;
            }
        } catch (\Exception $e) {
            http_response_code(422);
            echo json_encode(['error' => 'avatar_processing_failed'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Save path to DB
        $userService = self::userService();
        $userService->changeAvatar((int)$currentUser['id'], $relativePath);

        http_response_code(200);
        echo json_encode([
            'status' => 'ok',
            'avatar_url' => $relativePath,
        ], JSON_UNESCAPED_UNICODE);
    }

}
