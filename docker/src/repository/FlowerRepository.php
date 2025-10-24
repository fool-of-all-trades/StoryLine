<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use Throwable;

final class FlowerRepository
{
    public function __construct(private ?PDO $pdo = null) {
        $this->pdo ??= Database::get();
    }

    public function countForStory(int $storyId): int {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM flowers WHERE story_id = :id');
        $st->execute([':id'=>$storyId]);
        return (int)$st->fetchColumn();
    }

    public function hasFlower(int $storyId, int $userId): bool {
        $st = $this->pdo->prepare('SELECT 1 FROM flowers WHERE story_id=:s AND user_id=:u LIMIT 1');
        $st->execute([':s'=>$storyId, ':u'=>$userId]);
        return (bool)$st->fetch();
    }

    /**
     * Toggle in transaction: add if doesn't exist, remove if exists.
     * Returns: ['flowered'=>bool, 'count'=>int]
     */
    public function toggle(int $storyId, int $userId): array
    {
        Database::begin();
        try {
            if ($this->hasFlower($storyId, $userId)) {
                $del = $this->pdo->prepare('DELETE FROM flowers WHERE story_id=:s AND user_id=:u');
                $del->execute([':s'=>$storyId, ':u'=>$userId]);
                $flowered = false;
            } else {
                $ins = $this->pdo->prepare('INSERT INTO flowers (story_id, user_id, value) VALUES (:s,:u,1)');
                $ins->execute([':s'=>$storyId, ':u'=>$userId]);
                $flowered = true;
            }

            $count = $this->countForStory($storyId);
            Database::commit();
            return ['flowered'=>$flowered, 'count'=>$count];
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
