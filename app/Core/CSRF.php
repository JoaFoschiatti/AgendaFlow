<?php

namespace App\Core;

class CSRF
{
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    public static function validate(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Token expires after 1 hour
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function field(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        // Check if token exists and is not expired
        $needNewToken = false;
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            $needNewToken = true;
        } else {
            // Check if token expired (1 hour)
            if (time() - $_SESSION['csrf_token_time'] > 3600) {
                $needNewToken = true;
            }
        }
        
        if ($needNewToken) {
            $token = self::generate();
        } else {
            $token = $_SESSION['csrf_token'];
        }
        
        $fieldName = Config::get('security.csrf_token_name', '_token');

        return '<input type="hidden" name="' . $fieldName . '" value="' . $token . '">';
    }
}