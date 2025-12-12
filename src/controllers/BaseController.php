<?php
namespace App\Controllers;

abstract class BaseController
{
    protected function render(string $template, array $variables = []): void
    {
        $templatePath = __DIR__ . '/../../public/views/' . $template . '.php';
        $fallback404  = __DIR__ . '/../../public/views/404.html';

        if (!empty($variables)) {
            extract($variables, EXTR_SKIP);
        }

        if (is_file($templatePath)) {
            include $templatePath;
        } else {
            include $fallback404;
        }

        $output = ob_get_clean();
        echo $output;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function isPost(): bool 
    { 
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; 
    }
    protected function isGet(): bool 
    { 
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET'; 
    }

    protected function redirect(string $location): void
    {
        http_response_code(302);
        header('Location: ' . $location);
        exit;
    }

    protected function notFound(string $message): void
    {
        http_response_code(404);
        echo $message;
        $this->render('404.html');
        exit;
    }
}
