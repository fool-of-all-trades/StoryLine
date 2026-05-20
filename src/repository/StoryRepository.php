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
        $sql = <<<SQL
            SELECT
                s.*,
                s.score AS flower_count,
                dp."date" AS prompt_date,
                dp.sentence AS prompt_sentence
            FROM vw_public_stories_with_score s
            LEFT JOIN daily_prompt dp ON dp.id = s.prompt_id
            WHERE s.id = :id
            LIMIT 1
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute(['id'=>$id]);
        $row = $st->fetch();
        return $row ? Story::fromArray($row) : null;
    }

    public function getStoryByPublicId(string $uuid): ?Story {
        $sql = <<<SQL
            SELECT
                s.*,
                s.score AS flower_count,
                dp."date" AS prompt_date,
                dp.sentence AS prompt_sentence
            FROM vw_public_stories_with_score s
            LEFT JOIN daily_prompt dp ON dp.id = s.prompt_id
            WHERE s.story_public_id = :uuid
            LIMIT 1
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':uuid' => $uuid]);
        $row = $st->fetch();
        return $row ? Story::fromArray($row) : null;
    }


    /** Creates a logged-in user's story in a transaction. */
    public function create(Story $s): string{
        try {
            Database::begin();

            $sql = 'INSERT INTO stories (prompt_id, user_id, title, content, is_anonymous, visibility)
                    VALUES (:p, :u, :t, :c, :anon, :visibility)
                    RETURNING public_id';
            $st = $this->pdo->prepare($sql);
            $st->execute([
                ':p'   => $s->quoteId,
                ':u'   => $s->userId,
                ':t'   => $s->title,
                ':c'   => $s->content,
                ':anon'=> $s->isAnonymous ? 't' : 'f',
                ':visibility' => 'public',
            ]);

            $publicId = (string)$st->fetchColumn();
            Database::commit();
            return $publicId;

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
    public function listByDate(string $dateYmd, string $sort='new', int $limit=10, int $offset=0): array
    {
        $order = match ($sort) {
            'top' => 'flower_count DESC, s.created_at DESC',
            default => 's.created_at DESC'
        };

        $sql = <<<SQL
            SELECT
                s.*,
                s.score AS flower_count
            FROM vw_public_stories_with_score s
            JOIN daily_prompt dp
                ON dp.id = s.prompt_id AND dp."date" = :d
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

    /**
     * Stories by user – for user profile panel, with pagination
     */
    public function listByUser(int $userId, int $limit = 8, int $offset = 0, bool $includePrivate = false): array
    {
        $visibilityWhere = $includePrivate
            ? ''
            : "AND s.visibility = 'public' AND s.is_anonymous = FALSE";

        $sql = <<<SQL
            SELECT
                s.*,
                COALESCE(f.cnt,0)::int AS flower_count,
                CASE
                    WHEN s.is_anonymous OR s.user_id IS NULL THEN NULL
                    ELSE up.display_name
                END AS username,
                CASE
                    WHEN s.is_anonymous OR s.user_id IS NULL THEN NULL
                    ELSE up.public_id
                END AS user_public_id,
                s.public_id AS story_public_id
            FROM stories s
            LEFT JOIN (
                SELECT story_id, COUNT(*)::int AS cnt
                FROM flowers
                GROUP BY story_id
            ) f ON f.story_id = s.id
            LEFT JOIN user_profiles up
                ON up.user_id = s.user_id
            WHERE s.user_id = :uid
            $visibilityWhere
            ORDER BY s.created_at DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':uid', $userId, PDO::PARAM_INT);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        $rows = $st->fetchAll();
        return array_map(fn($r) => Story::fromArray($r), $rows);
    }

    /**
     * total number of words written by a user
     */
    public function totalWordsByUser(int $userId, bool $includePrivate = false): int
    {
        $visibilityWhere = $includePrivate
            ? ''
            : "AND visibility = 'public' AND is_anonymous = FALSE";

        $st = $this->pdo->prepare(
            "SELECT COALESCE(SUM(word_count),0)::int FROM stories WHERE user_id = :uid $visibilityWhere"
        );
        $st->execute([':uid' => $userId]);
        return (int)$st->fetchColumn();
    }

    /**
     * total number of stories written by a user
     */
    public function totalStoriesByUser(int $userId, bool $includePrivate = false): int
    {
        $visibilityWhere = $includePrivate
            ? ''
            : "AND visibility = 'public' AND is_anonymous = FALSE";

        $st = $this->pdo->prepare(
            "SELECT COUNT(*)::int FROM stories WHERE user_id = :uid $visibilityWhere"
        );
        $st->execute([':uid' => $userId]);
        return (int)$st->fetchColumn();
    }

    // HELPERS FOR ADMINISTRATION PURPOSES
    public function countTotal(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM stories")->fetchColumn();
    }

    public function countOnDate(string $date): int {
        $sql = <<<SQL
            SELECT COUNT(*)::int
            FROM vw_public_stories_with_score s
            JOIN daily_prompt dp ON dp.id = s.prompt_id AND dp."date" = :d
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':d'=>$date]);
        return (int)$stmt->fetchColumn();
    }

    public function topOfDay(string $date): ?array {
        $sql = "SELECT
                    s.id,
                    s.story_public_id,
                    s.title,
                    s.created_at,
                    s.username,
                    s.user_public_id,
                    s.score AS flowers
                FROM vw_public_stories_with_score s
                WHERE s.created_at::date = :d
                ORDER BY s.score DESC, s.created_at DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':d' => $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }


    /** Returns: [ ['bucket'=>'2025-01-01','cnt'=>12], ... ] */
    public function timeSeries(int $months = 12, string $bucket = 'month'): array
    {
        $bucket = $bucket === 'day' ? 'day' : 'month';

        $sql = "SELECT date_trunc('$bucket', created_at)::date AS bucket, COUNT(*)::int AS cnt
                FROM stories
                WHERE created_at >= (CURRENT_DATE - CAST(:interval AS interval))
                GROUP BY 1
                ORDER BY 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':interval' => $months . ' months', // np. "6 months"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}
