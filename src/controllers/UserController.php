<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use App\Services\StoryService;
use Throwable;

class UserController extends BaseController
{
    private StoryService $storyService;
    private UserService $userService;

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

        $title = "StoryLine — " . $user->username;
        
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
            $currentUser = current_user();
            $isOwnProfile = $currentUser && (int)$currentUser['id'] === $user->id;
            $data = $this->storyService->getProfileDataForUser($user->id, $isOwnProfile);

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

            $currentUser = current_user();
            $isOwnProfile = $currentUser && (int)$currentUser['id'] === $userPrivateID;

            $storiesPayload = $this->storyService->getStoresForUser($userPrivateID, $limit, $offset, $isOwnProfile);

            $this->json([
                'page' => $page,
                'limit' => $limit,
                'stories' => $storiesPayload,
            ]);
        } catch (Throwable $e) {
            $this->json(['error' => 'internal_error'], 500);
        }
    }

}
