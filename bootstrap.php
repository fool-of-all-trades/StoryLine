<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

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

// Autoloader
$base = __DIR__ . '/src';

spl_autoload_register(function (string $class) use ($base) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len); // Controllers\UserController ...
    $file = $base . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Helpers
function auth_service(): \App\Services\AuthService {
    static $service = null;

    if (!$service instanceof \App\Services\AuthService) {
        $service = new \App\Services\AuthService();
    }

    return $service;
}

function current_auth_user(): ?array {
    return auth_service()->currentUser();
}

function current_profile(): ?array {
    $user = current_auth_user();

    return is_array($user['profile'] ?? null) ? $user['profile'] : null;
}

function current_user(): ?array {
    return current_auth_user();
}

function is_logged_in(): bool {
    return current_auth_user() !== null;
}

function is_admin(): bool {
    $authUser = current_auth_user();
    return $authUser
        && ((bool)($authUser['is_admin'] ?? false) || (($authUser['role'] ?? 'user') === 'admin'));
}

function require_login(): void {
  if (!is_logged_in()) { http_response_code(401); exit('Unauthorized'); }
}

function require_role(array $roles): void {
  require_login();
  $r = current_user()['role'] ?? 'user';
  if (!in_array($r, $roles, true)) { http_response_code(403); exit('Forbidden'); }
}
