<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\Quote;
use PDO;
use DateTimeImmutable;

final class QuoteRepository
{
    public function __construct(private ?PDO $pdo = null) {
        $this->pdo ??= Database::get();
    }

    // Get a quote for a specific date
    public function getByDate(string $dateYmd): ?Quote {
        $st = $this->pdo->prepare('SELECT * FROM daily_prompt WHERE "date" = :d');
        $st->execute(['d'=>$dateYmd]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? Quote::fromArray($row) : null;
    }

    // Get today's quote
    public function getToday(): ?Quote {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        return $this->getByDate($today);
    }

    // Insert a quote for a specific date, if it does not already exist
    // Returns the ID of the quote
    public function insertForDate(string $date, string $sentence, ?string $book, ?string $author, ?string $sourceId = null): int {
        // insert the quote into the db and return it's ID or just do nothing if it already exists
        $sql = <<<SQL
            INSERT INTO daily_prompt("date", sentence, source_book, source_author, source_id)
            VALUES (:d, :s, :b, :a, :sid)
            ON CONFLICT ("date") DO NOTHING
            RETURNING id
        SQL;

        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->execute([
            'd'   => $date,
            's'   => $sentence,
            'b'   => $book,
            'a'   => $author,
            'sid' => $sourceId
        ]);

        // fetch the result of the insertion, if it's an ID then we inserted, we can return the ID
        // if not, then fetchColumn returns false and we know the quote already existed before
        // so then we have to get the quote and then check it's ID
        $id = $pdoStatement->fetchColumn();
        if ($id === false) {
            $todayQuote = $this->getByDate($date);
            return $todayQuote?->id ?? 0;
        }

        return (int)$id;
    }
}
