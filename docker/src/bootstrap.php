<?php
declare(strict_types=1);

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

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
