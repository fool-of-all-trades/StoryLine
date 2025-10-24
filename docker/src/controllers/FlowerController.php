<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\FlowerService;
use DomainException;
use Throwable;

final class FlowerController
{
    private static function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /api/story/flower?id=123 – toggle */
    public static function toggle(): void {
        $flowerService = new FlowerService();

        // for now only logged-in users can flower
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            self::json(['error'=>'unauthorized'], 401);
        }

        $storyId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($storyId <= 0) self::json(['error'=>'bad_request'], 400);

        try {
            $result = $flowerService->toggleFlower($storyId, $userId);
            self::json($result, 200);
        } catch (DomainException $e) {
            $code = $e->getMessage()==='story_not_found' ? 404 : 400;
            self::json(['error'=>$e->getMessage()], $code);
        } catch (Throwable $e) {
            self::json(['error'=>'internal_error'], 500);
        }
    }


    /** GET /api/story/flowers?id=123 – liczba kwiatków */
    public static function count(): void {
        $flowerService = new FlowerService();
        $storyId = (int)($_GET['id'] ?? 0);
        if ($storyId <= 0) {
            self::json(['error'=>'bad_request'], 400);
        }

        try {
            $flowerCountForStory = $flowerService->countForStory($storyId);
            self::json(['count'=>$flowerCountForStory], 200);
        } catch (Throwable $e) {
            self::json(['error'=>'internal_error'], 500);
        }
    }
}
