<?php
declare(strict_types=1);

namespace App\Helpers;

final class Logger
{
    public function __construct(
        private string $filePath
    ) {}

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARN', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ts = (new \DateTimeImmutable('now'))->format('c');
        $line = [
            'ts' => $ts,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        // JSON lines = easy to grep/parse
        @file_put_contents($this->filePath, json_encode($line, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
