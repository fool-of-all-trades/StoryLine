<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo) return self::$pdo;

        $cfg = [
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'db'   => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS'),
        ];

        $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']}";
        
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);


        self::$pdo = $pdo;
        return $pdo;
    }

    public static function begin(): void { self::get()->beginTransaction(); }
    public static function commit(): void { self::get()->commit(); }
    public static function rollBack(): void { if (self::get()->inTransaction()) self::get()->rollBack(); }
}
