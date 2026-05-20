<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class MailService
{
    public function assertConfigured(): void
    {
        if ($this->env('APP_ENV', 'local') === 'production') {
            foreach (['MAIL_HOST', 'MAIL_FROM_ADDRESS', 'APP_BASE_URL'] as $required) {
                if ($this->env($required) === '') {
                    throw new RuntimeException('mail_not_configured');
                }
            }
        }
    }

    public function sendPasswordReset(string $to, string $resetUrl): void
    {
        $this->send(
            $to,
            'Reset your StoryLine password',
            "We received a request to reset your StoryLine password.\r\n\r\n"
            . "Open this link to choose a new password:\r\n"
            . $resetUrl . "\r\n\r\n"
            . "If you did not request this, you can ignore this email.\r\n"
        );
    }

    private function send(string $to, string $subject, string $body): void
    {
        $this->assertConfigured();

        $host = $this->env('MAIL_HOST', 'mailhog');
        $port = (int)$this->env('MAIL_PORT', '1025');
        $from = $this->env('MAIL_FROM_ADDRESS', 'no-reply@example.test');
        $fromName = $this->env('MAIL_FROM_NAME', 'StoryLine');
        $encryption = strtolower($this->env('MAIL_ENCRYPTION'));
        $username = $this->env('MAIL_USERNAME');
        $password = $this->env('MAIL_PASSWORD');
        $target = $encryption === 'ssl' ? 'ssl://' . $host : $host;

        $socket = @fsockopen($target, $port, $errno, $errstr, 10);
        if (!$socket) {
            throw new RuntimeException('mail_send_failed');
        }

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO storyline.local', 250);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('mail_send_failed');
                }
                $this->command($socket, 'EHLO storyline.local', 250);
            }

            if ($username !== '') {
                $this->command($socket, 'AUTH LOGIN', 334);
                $this->command($socket, base64_encode($username), 334);
                $this->command($socket, base64_encode($password), 235);
            }

            $this->command($socket, 'MAIL FROM:<' . $from . '>', 250);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($socket, 'DATA', 354);

            $headers = [
                'From: ' . $this->formatAddress($from, $fromName),
                'To: <' . $to . '>',
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];

            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $this->escapeBody($body) . "\r\n.\r\n");
            $this->expect($socket, 250);
            $this->command($socket, 'QUIT', 221);
        } finally {
            fclose($socket);
        }
    }

    private function command($socket, string $command, int|array $expected): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expected);
    }

    private function expect($socket, int|array $expected): void
    {
        $code = 0;
        do {
            $line = fgets($socket, 512);
            if ($line === false) {
                throw new RuntimeException('mail_send_failed');
            }

            $code = (int)substr($line, 0, 3);
        } while (isset($line[3]) && $line[3] === '-');

        $expectedCodes = is_array($expected) ? $expected : [$expected];

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('mail_send_failed');
        }
    }

    private function formatAddress(string $email, string $name): string
    {
        $safeName = str_replace(['"', "\r", "\n"], '', $name);

        return '"' . $safeName . '" <' . $email . '>';
    }

    private function escapeBody(string $body): string
    {
        return preg_replace('/^\./m', '..', $body) ?? $body;
    }

    private function env(string $key, string $default = ''): string
    {
        $value = getenv($key);

        return $value === false ? $default : trim((string)$value);
    }
}
