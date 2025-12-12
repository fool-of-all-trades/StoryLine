<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\FlowerService;
use DomainException;
use Throwable;
use App\Security\Csrf;

class FlowerController extends BaseController
{

    private FlowerService $flowerService;

    public function __construct() {
        $this->flowerService = new FlowerService();
    }

    /** POST /api/story/flower?id=123 – toggle */
    public function toggle(array $params): void {
        Csrf::verify();

        // for now only logged-in users can flower
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            $this->json(['error'=>'unauthorized'], 401);
        }

        $publicStoryId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicStoryId)) {
            $this->json(['error' => 'bad_request'], 400);
        }

        try {
            $result = $this->flowerService->toggleFlower($publicStoryId, $userId);
            $this->json($result, 200);
        } catch (DomainException $e) {
            $code = $e->getMessage()==='story_not_found' ? 404 : 400;
            $this->json(['error'=>$e->getMessage()], $code);
        } catch (Throwable $e) {
            $this->json(['error'=>'internal_error'], 500);
        }
    }

    /** GET /api/story/flowers?id=123 – liczba kwiatków */
    public function count(array $params): void {
        $publicStoryId = (string)($params['public_id'] ?? '');
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $publicStoryId)) {
            $this->json(['error' => 'bad_request'], 400);
        }

        try {
            $flowerCountForStory = $this->flowerService->countForStory($publicStoryId);
            $this->json(['count'=>$flowerCountForStory], 200);
        } catch (Throwable $e) {
            $this->json(['error'=>'internal_error'], 500);
        }
    }
}
