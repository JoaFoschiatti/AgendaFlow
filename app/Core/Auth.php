<?php

namespace App\Core;

class Auth
{
    public static function login(int $userId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
    }
    
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }
    
    public static function id(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function user(): ?array
    {
        $userId = self::id();
        
        if ($userId === null) {
            return null;
        }
        
        $userModel = new \App\Models\User();
        return $userModel->find($userId);
    }
    
    public static function hashPassword(string $password): string
    {
        // Try to use Argon2id if available
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }

        // Fallback to default (bcrypt)
        return password_hash($password, Config::get('security.password_algo', PASSWORD_DEFAULT));
    }
    
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}