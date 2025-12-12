<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\UserService;
use App\Services\StoryService;
use DomainException;
use Throwable;
use App\Security\Csrf;

class UserController extends BaseController
{
    private $storyService;
    private $userService;

    public function __construct() {
        $this->storyService = new StoryService();
        $this->userService = new UserService();
    }

    public function profileByPublicId(array $params): void
    {
        $publicId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicId)) {
            $this->notFound(
                "User not found"
            );
        }

        $user = $this->userService->findByPublicId($publicId);
        if (!$user) { 
            $this->notFound(
                "User not found"
            );
        }

        $title = "StoryLine — " . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
        
        $this->render('user', [
            'user' => $user,
            'title' => $title,
        ]);
    }

    public function profileData(array $params): void
    {
        $publicId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicId)) {
            $this->json(['error' => 'not_found'], 404);
        }

        $user = $this->userService->findByPublicId($publicId);
        if (!$user) {
            $this->json(['error' => 'not_found'], 404);
        }

        try {
            $data = $this->storyService->getProfileDataForUser($user->id);

            $this->json([
                'user'   => [
                    'username'  => $user->username,
                    'public_id' => $user->public_id,
                    'created_at'=> $user->createdAt->format('c'),
                ],
                'data'  => $data,
            ]);
        } catch (Throwable $e) {
            $this->json(['error' => 'internal_error'], 500);
        }
    }

    public function profileStories(array $params): void
    {
        $publicId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicId)) {
            $this->json(['error' => 'not_found'], 404);
        }

        $userPrivateID = $this->userService->findPrivateIdByPublicId($publicId);
        if (!$userPrivateID) {
            $this->json(['error' => 'not_found'], 404);
        }

        try {
            $page  = (int)($_GET['page']  ?? 1);
            $limit = (int)($_GET['limit'] ?? 8);

            $page  = max(1, $page);
            $limit = max(1, min(8, $limit));

            // how many stories to skip
            $offset = ($page - 1) * $limit;

            $storiesPayload = $this->storyService->getStoresForUser($userPrivateID, $limit, $offset);

            $this->json([
                'page' => $page,
                'limit' => $limit,
                'stories' => $storiesPayload,
            ]);
        } catch (Throwable $e) {
            $this->json(['error' => 'internal_error'], 500);
        }
    }

    public function updateFavoriteQuote(): void
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

        try {
            $this->userService->setFavoriteQuote((int)$currentUser['id'], $sentence, $book, $author);
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
    
    public function updateUsername(): void
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

        try {
            $this->userService->changeUsername((int)$currentUser['id'], $username);
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

    public function updatePassword(): void
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

        try {
            $this->userService->changePassword((int)$currentUser['id'], $password);
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

    public function updateAvatar(): void
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
        $this->userService->changeAvatar((int)$currentUser['id'], $relativePath);

        http_response_code(200);
        echo json_encode([
            'status' => 'ok',
            'avatar_url' => $relativePath,
        ], JSON_UNESCAPED_UNICODE);
    }

}
