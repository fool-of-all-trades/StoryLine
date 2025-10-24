<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Warsaw');
mb_internal_encoding('UTF-8');

// ——— Session hardening ———
ini_set('session.use_strict_mode', '1');     // reject non-existing SIDs
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');     // JS won't read the cookie
ini_set('session.cookie_samesite', 'Lax');   // protects against CSRF for external forms

// for reverse proxy
if (!empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    ini_set('session.cookie_secure', '1');   // only over HTTPS
}
session_name('SLSESSID');

// własne parametry sesji
$lifetime = 60 * 60 * 2; // 2h
session_set_cookie_params([
  'lifetime' => $lifetime,
  'path'     => '/',
  'secure'   => ini_get('session.cookie_secure') === '1',
  'httponly' => true,
  'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Idle timeout
if (!isset($_SESSION['__last'])) {
  $_SESSION['__last'] = time();
} elseif (time() - (int)$_SESSION['__last'] > $lifetime) {
  session_regenerate_id(true);
  session_unset();
  session_destroy();
  session_start();
}
$_SESSION['__last'] = time();

$base = __DIR__; // /app/docker/src

// Autoload classes from App\ namespace, mapping class namespaces to filesystem paths
spl_autoload_register(function (string $class) use ($base) {
  $prefix = 'App\\';
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    return; // if other namespace, do nothing
  }
  $relative = substr($class, $len);            // Controllers\UserController
  $file = $base . '/' . str_replace('\\', '/', $relative) . '.php';
  if (is_file($file)) {
    require $file;
  }
});


// helpers
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (($_SESSION['user']['role'] ?? 'user') === 'admin'); }
