<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        $layoutFile = dirname(__DIR__) . "/Views/layouts/main.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$view}");
        }
        
        // Capture the view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        
        // Render with layout
        require $layoutFile;
    }
    
    public static function renderPartial(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$view}");
        }
        
        require $viewFile;
    }
    
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public static function formatDate($date, string $format = 'd/m/Y'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        return $date->format($format);
    }
    
    public static function formatMoney(float $amount): string
    {
        return '$' . number_format($amount, 2, ',', '.');
    }
    
    public static function formatDateTime($date, string $format = 'd/m/Y H:i'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        return $date->format($format);
    }
    
    public static function asset(string $path): string
    {

        return Url::full($path);

    }

    public static function url(string $path = ''): string
    {

        return Url::full($path);

    }
}
