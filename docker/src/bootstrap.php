<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Warsaw');
mb_internal_encoding('UTF-8');

// Session parameters
$secure = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

ini_set('session.use_strict_mode', '1');   // reject foreign IDs
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
if ($secure) {
    ini_set('session.cookie_secure', '1');
}

ini_set('session.gc_maxlifetime', '86400'); // 24h

session_start();

// Timeouts and session ID rotation
$NOW = time();
$IDLE_LIMIT = 3600; // 1h of inactivity
$ABSOLUTE_LIMIT = 86400; // 24 h from session start
$REGEN_EVERY = 900; // ID rotation every 15 min

$_SESSION['_meta'] = $_SESSION['_meta'] ?? [
    'started_at'    => $NOW,
    'last_activity' => $NOW,
    'last_regen'    => $NOW,
];

// Absolute timeout
if ($NOW - $_SESSION['_meta']['started_at'] > $ABSOLUTE_LIMIT) {
    session_unset();
    session_destroy();
    session_start();
    if (class_exists('\\App\\Security\\Csrf')) {
        \App\Security\Csrf::regenerate();
    }
} else {
    // Idle timeout
    if ($NOW - ($_SESSION['_meta']['last_activity'] ?? $NOW) > $IDLE_LIMIT) {
        session_unset();
        session_destroy();
        session_start();
        if (class_exists('\\App\\Security\\Csrf')) {
            \App\Security\Csrf::regenerate();
        }
    } else {
        // Activity update
        $_SESSION['_meta']['last_activity'] = $NOW;

        // rotation of session ID
        if ($NOW - ($_SESSION['_meta']['last_regen'] ?? $NOW) > $REGEN_EVERY) {
            session_regenerate_id(true);
            $_SESSION['_meta']['last_regen'] = $NOW;
            if (class_exists('\\App\\Security\\Csrf')) {
                \App\Security\Csrf::regenerate();
            }
        }
    }
}

// Autoloader przestrzeni App\
$base = __DIR__;

spl_autoload_register(function (string $class) use ($base) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len); // Controllers\UserController
    $file = $base . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Helpers
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool {
    return (($_SESSION['user']['role'] ?? 'user') === 'admin');
}

function require_login(): void {
  if (!isset($_SESSION['user'])) { http_response_code(401); exit('Unauthorized'); }
}

function require_role(array $roles): void {
  require_login();
  $r = $_SESSION['user']['role'] ?? 'user';
  if (!in_array($r, $roles, true)) { http_response_code(403); exit('Forbidden'); }
}
