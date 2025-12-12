<?php

use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Controllers\StoryController;
use App\Controllers\FlowerController;
use App\Controllers\QuotesApiController;
use App\Controllers\QuoteController;
use App\Controllers\AdminController;
use App\Controllers\PasswordResetController;

class Routing
{
    private static array $routes = [];

    // Register a GET route
    private static function get(string $path, callable|array|string $handler): void
    {
        self::$routes[$path]['GET'] = $handler;
    }

    // Register a POST route
    private static function post(string $path, callable|array $handler): void
    {
        self::$routes[$path]['POST'] = $handler;
    }

    // Define all application routes
    private static function defineRoutes(): void
    {
        // Static pages
        self::get('', fn() => include 'public/views/dashboard.php');
        self::get('dashboard', fn() => include 'public/views/dashboard.php');
        self::get('test', fn() => include 'public/views/test.html');

        // Admin
        self::get('admin', [AdminController::class, 'index']);

        // Stories
        self::get('stories', fn() => include 'public/views/stories.php');
        self::get('stories/today', fn() => header('Location: /stories?date=today&sort=new'));

        // Auth - Views and Actions
        self::get('login', fn() => include 'public/views/login.php');
        self::post('login', [AuthController::class, 'login']);
        self::post('logout', [AuthController::class, 'logout']);
        self::get('register', fn() => include 'public/views/register.php');
        self::post('register', [AuthController::class, 'register']);

        // Password Reset
        self::get('password/forgot', fn() => include 'public/views/password_forgot.php');
        self::post('password/forgot', [PasswordResetController::class, 'forgot']);
        self::get('password/reset', fn() => include 'public/views/password_reset.php');
        self::post('password/reset', [PasswordResetController::class, 'reset']);

        // API - Stories
        self::get('api/stories', [StoryController::class, 'list']);
        self::get('api/story', [StoryController::class, 'getStoryById']);
        self::post('api/story', [StoryController::class, 'create']);

        // API - Flowers (likes)
        self::post('api/story/flower', [FlowerController::class, 'toggle']);
        self::get('api/story/flowers', [FlowerController::class, 'count']);

        // API - Quotes
        self::get('api/quotes/random', [QuotesApiController::class, 'random']);
        self::get('api/quote/today', [QuoteController::class, 'today']);
        self::post('api/quote/today', [QuoteController::class, 'ensureToday']);
        self::get('api/quote', [QuoteController::class, 'byDate']);
        self::post('api/quote', [QuoteController::class, 'ensureByDate']);

        // API - User Profile Updates
        self::post('api/me/favorite-quote', [UserController::class, 'updateFavoriteQuote']);
        self::post('api/me/username', [UserController::class, 'updateUsername']);
        self::post('api/me/password', [UserController::class, 'updatePassword']);
        self::post('api/me/avatar', [UserController::class, 'updateAvatar']);
    }

    // Execute a route handler
    private static function executeHandler(callable|array|string $handler, array $params = []): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();

            if ($params) $controller->$method($params); 
            else  $controller->$method(); 
            
            return;
        }
        $handler($params);
    }

    
    // Main routing logic
    public static function run(string $path): void
    {
        // Define routes once
        if (empty(self::$routes)) {
            self::defineRoutes();
        }

        $path = parse_url($path, PHP_URL_PATH) ?? '/';
        $path = trim($path, "/");
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Check static routes first
        if (isset(self::$routes[$path][$method])) {
            self::executeHandler(self::$routes[$path][$method]);
            return;
        }

        // If route exists but method is wrong, return 405
        if (isset(self::$routes[$path])) {
            http_response_code(405);
            $allowed = implode(', ', array_keys(self::$routes[$path]));
            header("Allow: $allowed");
            echo json_encode(['error' => 'method_not_allowed']);
            return;
        }

        // Dynamic routes with regex
        $dynamicRoutes = [
            // GET /user/{public_id}
            [
                'pattern' => '#^user/([0-9a-fA-F-]{36})$#',
                'method' => 'GET',
                'handler' => [UserController::class, 'profileByPublicId'],
                'params' => fn($m) => ['public_id' => $m[1]]
            ],
            // GET /story/{public_id}
            [
                'pattern' => '#^story/([0-9a-fA-F-]{36})$#',
                'method' => 'GET',
                'handler' => [StoryController::class, 'viewByPublicId'],
                'params' => fn($m) => ['public_id' => $m[1]]
            ],
            // GET /api/user/{public_id}/profile
            [
                'pattern' => '#^api/user/([0-9a-fA-F-]{36})/profile$#',
                'method' => 'GET',
                'handler' => [UserController::class, 'profileData'],
                'params' => fn($m) => ['public_id' => $m[1]]
            ],
            // GET /api/user/{public_id}/stories
            [
                'pattern' => '#^api/user/([0-9a-fA-F-]{36})/stories$#',
                'method' => 'GET',
                'handler' => [UserController::class, 'profileStories'],
                'params' => fn($m) => ['public_id' => $m[1]]
            ],
        ];

        foreach ($dynamicRoutes as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                if ($method !== $route['method']) {
                    http_response_code(405);
                    header("Allow: {$route['method']}");
                    echo json_encode(['error' => 'method_not_allowed']);
                    return;
                }
                
                $params = $route['params']($matches);
                self::executeHandler($route['handler'], $params);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        include 'public/views/404.html';
    }
}