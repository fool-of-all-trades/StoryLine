<?php

declare(strict_types=1);

chdir(dirname(__DIR__));

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Routing.php';

# ten plik to front controller

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::run($path);

// var_dump($path);

# kod odpowiedzi HTTP
# 100 - informacyjne
# 200 - sukces
# 300 - przekierowania
# 400 - błędy klienta
# 500 - błędy serwera

?>