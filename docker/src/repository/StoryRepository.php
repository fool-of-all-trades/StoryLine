<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\Story;
use App\Repository\Database;
use PDO;
use RuntimeException;
use Throwable;
use PDOException;

final class StoryRepository
{
    public function __construct(private ?PDO $pdo = null) { 
        $this->pdo ??= Database::get(); 
    }


    public function getById(int $id): ?Story {
        $st = $this->pdo->prepare('SELECT * FROM stories WHERE id = :id');
        $st->execute(['id'=>$id]);
        $row = $st->fetch();
        return $row ? Story::fromArray($row) : null;
    }

    /**
     * Creates the story in a transaction.
     * Limit of 1 story per prompt per (user/device/ip).
     * Plus (optionally) validation of word_count <= 500 (triggers in DB will enforce this anyway).
     */
    public function create(Story $s): int{
        try {
            Database::begin();

            $sql = 'INSERT INTO stories (prompt_id, user_id, device_token, ip_hash, title, content, is_anonymous)
                    VALUES (:p,:u,:dt,:ip,:t,:c,:anon)
                    RETURNING id';
            $st = $this->pdo->prepare($sql);
            $st->execute([
                ':p'   => $s->quoteId,
                ':u'   => $s->userId,
                ':dt'  => $s->deviceToken,
                ':ip'  => $s->ipHash,
                ':t'   => $s->title,
                ':c'   => $s->content,
                ':anon'=> $s->isAnonymous ? 't' : 'f',
            ]);

            $id = (int)$st->fetchColumn();
            Database::commit();
            return $id;

        } catch (PDOException $e) {
            Database::rollBack();
            // 23505 = unique_violation -> user already submitted today
            if ($e->getCode() === '23505') {
                throw new RuntimeException('Only one story per prompt for this identity.');
            }
            throw $e;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /** List of stories for a given date (YYYY-MM-DD) with sorting and pagination */
    public function listByDate(string $dateYmd, string $sort='new', int $limit=20, int $offset=0): array
    {
        $order = match ($sort) {
            'top' => 'flower_count DESC, s.created_at DESC',
            default => 's.created_at DESC'
        };

        $sql = <<<SQL
            SELECT s.*, COALESCE(f.cnt,0) AS flower_count
            FROM stories s
            JOIN daily_prompt dp ON dp.id = s.prompt_id AND dp."date" = :d
            LEFT JOIN (
                SELECT story_id, COUNT(*) AS cnt
                FROM flowers
                GROUP BY story_id
            ) f ON f.story_id = s.id
            ORDER BY $order
            LIMIT :limit OFFSET :offset
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->bindValue('d', $dateYmd);
        $st->bindValue('limit', $limit, PDO::PARAM_INT);
        $st->bindValue('offset', $offset, PDO::PARAM_INT);
        $st->execute();

        $rows = $st->fetchAll();
        return array_map(fn($r) => Story::fromArray($r), $rows);
    }

    // HELPERS FOR ADMINISTRATION PURPOSES
    public function countTotal(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM stories")->fetchColumn();
    }

    public function countOnDate(string $date): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM stories WHERE created_at::date = :d");
        $stmt->execute([':d'=>$date]);
        return (int)$stmt->fetchColumn();
    }

    public function topOfDay(string $date): ?array {
        $sql = "SELECT s.id, s.title, s.created_at,
                       u.username,
                       COUNT(f.id) AS flowers
                FROM stories s
                LEFT JOIN users   u ON u.id = s.user_id
                LEFT JOIN flowers f ON f.story_id = s.id
                                      AND f.created_at::date = :d
                WHERE s.created_at::date = :d
                GROUP BY s.id, u.username, s.title, s.created_at
                ORDER BY flowers DESC, s.created_at DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':d'=>$date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Returns: [ ['bucket'=>'2025-01-01','cnt'=>12], ... ] */
    public function timeSeries(int $months=12, string $bucket='month'): array {
        $bucket = $bucket === 'day' ? 'day' : 'month';
        $sql = "SELECT date_trunc('$bucket', created_at)::date AS bucket, COUNT(*)::int AS cnt
                FROM stories
                WHERE created_at >= (CURRENT_DATE - INTERVAL :months)
                GROUP BY 1 ORDER BY 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':months' => $months.' months']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
