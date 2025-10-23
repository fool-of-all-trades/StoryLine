<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\StoryService;
use DomainException;
use Throwable;

final class StoryController
{
    private static function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** GET /api/stories?date=today|YYYY-MM-DD&sort=top|new&page=1&limit=10 */
    public static function list(): void
    {
        $storyService = new StoryService();
        $date  = $_GET['date'] ?? 'today';
        $sort  = $_GET['sort'] ?? 'new';
        $page  = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);

        try {
            $items = $storyService->listByDate($date, $sort, $page, $limit);
            self::json([
                'items' => array_map(fn($s) => $s->toArray(), $items),
                'page'  => max(1, $page),
                'limit' => $limit,
            ]);
        } catch (Throwable $e) {
            self::json(['error' => 'internal_error'], 500);
        }
    }

    /** GET /api/story?id=123 */
    public static function getStoryById(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            self::json(['error'=>'bad_request'], 400);
        }

        $storyService = new StoryService();
        $story = $storyService->getStoryById($id);

        if (!$story) {
            self::json(['error'=>'not_found'], 404);
        }
        self::json($story->toArray());
    }

    /** POST /api/story (title, content, anonymous=on|1) */
    public static function create(): void
    {
        $storyService = new StoryService();

        $userId    = $_SESSION['user']['id'] ?? null; // null = anonymous
        $title     = $_POST['title']   ?? null;
        $content   = trim($_POST['content'] ?? '');
        $anonymous = !empty($_POST['anonymous']);

        if ($content === '') {
            self::json(['error'=>'empty_content'], 400);
        }

        // device token (cookie) – identification for anonymous users, cuz one story per day is allowed
        $deviceToken = $_COOKIE['device_token'] ?? null;

        // hash IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $salt = getenv('APP_IP_SALT') ?: 'change-me';
        $ipHash = $ip ? hash('sha256', $ip . '|' . $salt) : null;

        try {
            $id = $storyService->addTodayStory($userId, $title, $content, $anonymous, $deviceToken, $ipHash);
            self::json(['id'=>$id], 201);
        } catch (DomainException $e) {
            $code = match ($e->getMessage()) {
                'no_prompt_today'        => 400,
                'already_submitted_today'=> 409,
                'quote_missing'          => 400,
                'too_many_words'         => 400,
                default                  => 500,
            };
            self::json(['error'=>$e->getMessage()], $code);
        } catch (Throwable $e) {
            self::json(['error'=>'internal_error'], 500);
        }
    }
}
