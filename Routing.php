<?php

class Routing
{
    public static function run(string $path)
    {
        $path = parse_url($path, PHP_URL_PATH) ?? '/';
        $path = trim($path, "/");

        switch ($path) {
            case '':
            case 'dashboard':
                include 'public/views/dashboard.php';
                return;

            case 'login':
                include 'public/views/login.php';
                return;

            case 'register':
                include 'public/views/register.php';
                return;

            case 'admin':
                include 'public/views/admin.php';
                return;

            case 'stories':
                // lista zbiorcza albo redirect do /stories/today
                $_GET['date'] = date('Y-m-d');
                include 'public/views/stories.php';
                return;

            case 'stories/today':
                $_GET['date'] = date('Y-m-d');
                include 'public/views/stories.php';
                return;
        }

        // Dynamiczne: /user/{id}
        if (preg_match('#^user/(\d+)$#', $path, $m)) {
            $GLOBALS['route_params']['user_id'] = (int)$m[1];
            include 'public/views/user.php';
            return;
        }

        // Dynamiczne: /story/{id}
        if (preg_match('#^story/(\d+)$#', $path, $m)) {
            $GLOBALS['route_params']['story_id'] = (int)$m[1];
            include 'public/views/story.php';
            return;
        }

        // Dynamiczne: /stories/{YYYY-MM-DD}
        if (preg_match('#^stories/(\d{4}-\d{2}-\d{2})$#', $path, $m)) {
            $GLOBALS['route_params']['date'] = $m[1];
            include 'public/views/stories.php';
            return;
        }

        // 404
        http_response_code(404);
        include 'public/views/404.html';
    }
}
