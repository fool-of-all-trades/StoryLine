<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\StoryService;
use DomainException;
use Throwable;
use App\Security\Csrf;

class StoryController extends BaseController
{
    private $storyService;

    public function __construct() {
        $this->storyService = new StoryService();
    }

    /** GET /api/stories?date=today|YYYY-MM-DD&sort=top|new&page=1&limit=10 */
    public function list(): void
    {
        $date  = $_GET['date'] ?? 'today';
        $sort  = $_GET['sort'] ?? 'new';
        $page  = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);

        try {
            $items = $this->storyService->listByDate($date, $sort, $page, $limit);
            $totalForDay = $this->storyService->countByDate($date);

            $this->json([
                'items' => array_map(fn($s) => $s->toArray(), $items),
                'page'  => max(1, $page),
                'limit' => $limit,
                'total_for_day' => $totalForDay,
            ]);
        } catch (Throwable $e) {
            $this->json(['error' => 'internal_error'], 500);
        }
    }

    /** GET /api/story?id=123 */
    public function getStoryById(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['error'=>'bad_request'], 400);
        }

        $story = $this->storyService->getStoryById($id);

        if (!$story) {
            $this->json(['error'=>'not_found'], 404);
        }
        $this->json($story->toArray());
    }

    public function viewByPublicId(array $params): void {
        $uuid = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $uuid)) {
            http_response_code(404); echo 'Invalid story ID'; return;
        }

        $story = $this->storyService->getStoryByPublicId($uuid);
        if (!$story) {
            http_response_code(404); echo 'Story not found'; return;
        }

        $title = htmlspecialchars($story->title ?? '(Untitled)', ENT_QUOTES, 'UTF-8');
        // include __DIR__ . '/../../public/views/story.php';

        $this->render('story', [
        'title' => $title,
        'story' => $story,
        ]);
    }


    /** POST /api/story (title, content, anonymous=on|1) */
    public function create(): void
    {
        Csrf::verify();
        
        $userId    = $_SESSION['user']['id'] ?? null; // null = anonymous
        $title     = $_POST['title']   ?? null;
        $content   = trim($_POST['content'] ?? '');
        $anonymous = !empty($_POST['anonymous']);
        $guestName = $_POST['guest_name'] ?? null;

        if ($content === '') {
            $this->json(['error'=>'empty_content'], 400);
        }

        // device token (cookie) – identification for anonymous users, cuz one story per day is allowed
        $deviceToken = $_COOKIE['device_token'] ?? null;

        // hash IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $salt = getenv('APP_IP_SALT') ?: 'change-me';
        $ipHash = $ip ? hash('sha256', $ip . '|' . $salt) : null;

        try {
            $id = $this->storyService->addTodayStory($userId, $title, $content, $anonymous, 
                                                $guestName, $deviceToken, $ipHash);
            $this->json(['id'=>$id], 201);
        } catch (DomainException $e) {
            $code = match ($e->getMessage()) {
                'no_prompt_today' => 400,
                'already_submitted_today'=> 409,
                'quote_missing' => 400,
                'too_many_words' => 400,
                'prompt_missing_in_content' => 400,
                default => 500,
            };
            $this->json(['error'=>$e->getMessage()], $code);
        } catch (Throwable $e) {
            $this->json(['error'=>'internal_error'], 500);
        }
    }
}
