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

    // HELPERS FOR ADMIN PANEL
    public function countTotal(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM flowers")->fetchColumn();
    }

    public function timeSeries(int $months=12, string $bucket='month'): array {
        $bucket = $bucket === 'day' ? 'day' : 'month';
        $sql = "SELECT date_trunc('$bucket', created_at)::date AS bucket, COUNT(*)::int AS cnt
                FROM flowers
                WHERE created_at >= (CURRENT_DATE - INTERVAL :months)
                GROUP BY 1 ORDER BY 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':months' => $months.' months']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
