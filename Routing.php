<?php

use App\Controllers\UserController;
use App\Controllers\StoryController;
use App\Controllers\FlowerController;
use App\Controllers\QuotesApiController;
use App\Controllers\QuoteController;
use App\Controllers\AdminController;
use App\Controllers\PasswordResetController;

// controllers should be singletons

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

            case 'test':
                include 'public/views/test.html';
                return;
                
            case 'admin':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') { 
                    AdminController::index(); 
                    return; 
                }
                http_response_code(405); 
                return;

            case 'stories':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    include 'public/views/stories.php';
                    return;
                }
                http_response_code(405);
                echo 'Method Not Allowed';
                return;

            case 'stories/today':
                header('Location: /stories?date=today&sort=new');
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
                
            # Dynamic: /stories?date={YYYY-MM-DD}&sort=new
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
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    QuoteController::ensureToday();
                    return;
                }
                http_response_code(405);
                return;

            case 'api/quote':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    QuoteController::byDate();
                    return;
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    QuoteController::ensureByDate();
                    return;
                }
                http_response_code(405);
                return;

            case 'api/me/favorite-quote':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    UserController::updateFavoriteQuote();
                    return; 
                }
                http_response_code(405); 
                return;

            case 'api/me/username':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    UserController::updateUsername();
                    return; 
                }
                http_response_code(405); 
                return;

            case 'api/me/password':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                    UserController::updatePassword();
                    return; 
                }
                http_response_code(405); 
                return;

            case 'password/forgot':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    include 'public/views/password_forgot.php';
                    return;
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    PasswordResetController::forgot();
                    return;
                }
                http_response_code(405);
                return;

            case 'password/reset':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    include 'public/views/password_reset.php';
                    return;
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    PasswordResetController::reset();
                    return;
                }
                http_response_code(405);
                return;

            case 'api/me/avatar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    UserController::updateAvatar();
                    return;
                }
                http_response_code(405);
                return;
        }

        // Dynamic: /user/{public_id}
        if (preg_match('#^user/([0-9a-fA-F-]{36})$#', $path, $m)) {
            UserController::profileByPublicId(['public_id' => $m[1]]);
            return;
        }

        // Dynamic: /story/{public_id}
        if (preg_match('#^story/([0-9a-fA-F-]{36})$#', $path, $m)) {
            StoryController::viewByPublicId(['public_id' => $m[1]]);
            return;
        }

        // GET /api/user/{public_id}/profile
        if (preg_match('#^api/user/([0-9a-fA-F-]{36})/profile$#', $path, $m)) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                UserController::profileData(['public_id' => $m[1]]);
                return;
            }
            http_response_code(405);
            return;
        }

        // GET /api/user/{public_id}/stories
        if (preg_match('#^api/user/([0-9a-fA-F-]{36})/stories$#', $path, $m)) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                UserController::profileStories(['public_id' => $m[1]]);
                return;
            }
            http_response_code(405);
            return;
        }

        // 404
        http_response_code(404);
        include 'public/views/404.html';
    }
}
