<?php

# ten plik to front  controller

require 'Routing.php';

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