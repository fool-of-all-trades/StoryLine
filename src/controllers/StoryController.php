<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\StoryService;
use App\Services\QuoteService;
use DomainException;
use Throwable;
use App\Security\Csrf;

class StoryController extends BaseController
{
    private StoryService $storyService;

    public function __construct() {
        $this->storyService = new StoryService();
    }

    public function storiesPage(): void
    {
        try {
            (new QuoteService())->getOrEnsureForDate((string)($_GET['date'] ?? 'today'));
        } catch (Throwable $e) {
            error_log('[StoryController] quote_ensure_failed');
        }

        $this->render('stories');
    }

    public function storiesTodayRedirect(): void
    {
        $this->redirect('/stories?date=today&sort=new');
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
        } catch (DomainException $e) {
            if ($e->getMessage() === 'invalid_date_format') {
                $this->json(['error' => 'invalid_date_format'], 400);
            }

            if ($e->getMessage() === 'date_out_of_range') {
                $this->json(['error' => 'date_out_of_range'], 400);
            }

            error_log('[StoryController] story_list_domain_error: ' . $e->getMessage());
            $this->json(['error' => 'internal_error'], 500);
        } catch (Throwable $e) {
            error_log('[StoryController] story_list_failed: ' . $e->getMessage());
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
            $this->notFound('Story not found');
        }

        $story = $this->storyService->getStoryByPublicId($uuid);
        if (!$story) {
            $this->notFound('Story not found');
        }

        $title = $story->title ?? '(Untitled)';
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
        
        $currentUser = current_user();
        $userId = isset($currentUser['id']) ? (int)$currentUser['id'] : null;
        if (!$userId) {
            $this->json([
                'error' => 'authentication_required',
                'message' => 'Please log in to publish your story.',
            ], 401);
        }

        $title = $_POST['title']   ?? null;
        $content = trim($_POST['content'] ?? '');
        $anonymous = !empty($_POST['anonymous']);

        if ($content === '') {
            $this->json(['error'=>'empty_content'], 400);
        }



        try {
            $publicId = $this->storyService->addTodayStory($userId, $title, $content, $anonymous);
            $this->json(['public_id'=>$publicId], 201);
        } catch (DomainException $e) {
            $code = match ($e->getMessage()) {
                'authentication_required' => 401,
                'no_prompt_today' => 400,
                'already_submitted_today'=> 409,
                'quote_missing' => 400,
                'too_many_words' => 400,
                'prompt_missing_in_content' => 400,
                default => 500,
            };
            $safeError = match ($e->getMessage()) {
                'authentication_required',
                'no_prompt_today',
                'already_submitted_today',
                'quote_missing',
                'too_many_words',
                'prompt_missing_in_content' => $e->getMessage(),
                default => 'internal_error',
            };

            if ($safeError === 'internal_error') {
                error_log('[StoryController] story_create_domain_error: ' . $e->getMessage());
            }

            $this->json(['error'=>$safeError], $code);
        } catch (Throwable $e) {
            error_log('[StoryController] story_create_failed: ' . $e->getMessage());
            $this->json(['error'=>'internal_error'], 500);
        }
    }
}
