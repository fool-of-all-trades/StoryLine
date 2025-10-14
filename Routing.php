<?php

class Routing
{
    public static function run(string $path)
    {
      $path = trim($path, "/");

      switch ($path) {
        case '':
        case 'dashboard':
          include 'public/views/dashboard.html';
          return;

        case 'login':
          include 'public/views/login.html';
          return;
      }

      // dynamiczna ścieżka: user/{id}
      if (preg_match('#^user/(\d+)$#', $path, $match)) {
        $userId = (int)$match[1];

        $GLOBALS['route_params']['user_id'] = $userId;
        include 'public/views/user.php';
        return;
      }

      http_response_code(404);
      include 'public/views/404.html';
    }
}
