<?php

use App\Controllers\UserController;
use App\Controllers\StoryController;
use App\Controllers\FlowerController;
use App\Controllers\QuotesApiController;
use App\Controllers\QuoteController;


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
                
            case 'admin':
                include 'public/views/admin.php';
                return;

            case 'stories':
                // will redirect to today's stories
                header('Location: /stories/' . date('Y-m-d'));
                return;

            case 'stories/today':
                header('Location: /stories/' . date('Y-m-d'));
                return;


            case 'login':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    include 'public/views/login.php'; 
                    return;
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    UserController::login(); 
                    return; 
                }
                http_response_code(405); 
                echo 'Method Not Allowed'; 
                return;

            case 'logout':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    UserController::logout(); 
                    return; 
                }
                http_response_code(405); 
                echo 'Method Not Allowed'; 
                return;

            case 'register':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    include 'public/views/register.php'; 
                    return;
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    UserController::register(); 
                    return; 
                }
                http_response_code(405); 
                echo 'Method Not Allowed'; 
                return;
                
            # Dynamic: /stories/{YYYY-MM-DD}
            case 'api/stories':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') { 
                    StoryController::list(); 
                    return; 
                }
                http_response_code(405); 
                return;

            # Dynamic: /story/{id}
            case 'api/story':
                if ($_SERVER['REQUEST_METHOD'] === 'GET')  { 
                    StoryController::getStoryById(); 
                    return; 
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    StoryController::create(); 
                    return; 
                }
                http_response_code(405); 
                return;

            case 'api/story/flower':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    FlowerController::toggle(); 
                    return; 
                }
                http_response_code(405); 
                return;

            case 'api/story/flowers':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') { 
                    FlowerController::count(); 
                    return; 
                }
                http_response_code(405); 
                return;

            case 'api/quotes/random':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') { 
                    QuotesApiController::random(); 
                    return; 
                }
                http_response_code(405); 
                return;

            case 'api/quote/today':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') { 
                    QuoteController::today(); 
                    return; 
                }
                http_response_code(405); 
                return;

            case 'api/quote/ensure':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    QuoteController::ensureToday(); 
                    return; 
                }
                http_response_code(405); 
                return;
        }

        // Dynamic: /user/{id}
        if (preg_match('#^user/(\d+)$#', $path, $m)) {
            $GLOBALS['route_params']['user_id'] = (int)$m[1];
            include 'public/views/user.php';
            return;
        }

        // Dynamic: /story/{id}
        if (preg_match('#^story/(\d+)$#', $path, $m)) {
            $GLOBALS['route_params']['story_id'] = (int)$m[1];
            include 'public/views/story.php';
            return;
        }

        // Dynamic: /stories/{YYYY-MM-DD}
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
