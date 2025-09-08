<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Models\User;
use App\Models\AuditLog;

class AuthController extends Controller
{
    private User $userModel;
    private AuditLog $auditLog;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
    }
    
    public function showLogin(): void
    {
        $this->requireGuest();
        $this->render('auth/login');
    }
    
    public function login(): void
    {
        $this->requireGuest();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/login');
        }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validate input
        $errors = $this->validate($_POST, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/login');
        }
        
        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user || !Auth::verifyPassword($password, $user['password_hash'])) {
            $this->setFlash('error', 'Credenciales inválidas.');
            $_SESSION['old'] = ['email' => $email];
            
            // Log failed attempt
            $this->auditLog->log(
                null,
                'login_failed',
                'user',
                null,
                ['email' => $email],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            
            $this->redirect('/login');
        }
        
        // Login user
        Auth::login($user['id']);
        
        // Set remember me cookie if requested
        if ($remember) {
            $token = Auth::generateToken();
            $this->userModel->update($user['id'], ['remember_token' => $token]);
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
        }
        
        // Log successful login
        $this->auditLog->log(
            $user['id'],
            'login_success',
            'user',
            $user['id'],
            [],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        $this->setFlash('success', '¡Bienvenido de nuevo, ' . $user['name'] . '!');
        $this->redirect('/dashboard');
    }
    
    public function showRegister(): void
    {
        $this->requireGuest();
        $this->render('auth/register');
    }
    
    public function register(): void
    {
        $this->requireGuest();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/register');
        }
        
        // Validate input
        $errors = $this->validate($_POST, [
            'name' => 'required|min:3|max:100',
            'business_name' => 'required|min:3|max:150',
            'email' => 'required|email|max:150',
            'password' => 'required|min:6',
            'password_confirmation' => 'required',
            'terms' => 'required'
        ]);
        
        // Check password confirmation
        if (($_POST['password'] ?? '') !== ($_POST['password_confirmation'] ?? '')) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }
        
        // Check if email already exists
        if (empty($errors['email'])) {
            $existingUser = $this->userModel->findByEmail($_POST['email']);
            if ($existingUser) {
                $errors['email'] = 'Este email ya está registrado.';
            }
        }
        
        // Check terms acceptance
        if (!isset($_POST['terms'])) {
            $errors['terms'] = 'Debes aceptar los términos y condiciones.';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/register');
        }
        
        // Create user with trial
        $userId = $this->userModel->createWithTrial([
            'name' => $_POST['name'],
            'business_name' => $_POST['business_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'] ?? null,
            'password' => $_POST['password']
        ]);
        
        if (!$userId) {
            $this->setFlash('error', 'Error al crear la cuenta. Por favor intenta nuevamente.');
            $this->redirect('/register');
        }
        
        // Create default settings for the user
        $this->createDefaultSettings($userId);
        
        // Log registration
        $this->auditLog->log(
            $userId,
            'user_registered',
            'user',
            $userId,
            ['email' => $_POST['email']],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        // Auto-login
        Auth::login($userId);
        
        $this->setFlash('success', '¡Bienvenido a AgendaFlow! Tu prueba gratis de 14 días ha comenzado.');
        $this->redirect('/dashboard');
    }
    
    public function logout(): void
    {
        $this->requireAuth();
        
        // Log logout
        $this->auditLog->log(
            $this->user['id'],
            'logout',
            'user',
            $this->user['id'],
            [],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        Auth::logout();
        
        $this->setFlash('success', 'Has cerrado sesión correctamente.');
        $this->redirect('/login');
    }
    
    public function showForgotPassword(): void
    {
        $this->requireGuest();
        $this->render('auth/forgot-password');
    }
    
    public function forgotPassword(): void
    {
        $this->requireGuest();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/forgot-password');
        }
        
        $email = $_POST['email'] ?? '';
        
        // Validate input
        $errors = $this->validate($_POST, [
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/forgot-password');
        }
        
        // Find user
        $user = $this->userModel->findByEmail($email);
        
        // Always show success message (for security)
        $this->setFlash('success', 'Si el email existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña.');
        
        if ($user) {
            // Generate reset token
            $token = Auth::generateToken();
            
            // Save token (in production, save to a password_resets table with expiration)
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_expires'] = time() + 3600; // 1 hour
            
            // In production, send email with reset link
            // For now, we'll just log it
            error_log("Password reset token for user {$user['email']}: {$token}");
            
            // Log password reset request
            $this->auditLog->log(
                $user['id'],
                'password_reset_requested',
                'user',
                $user['id'],
                [],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        }
        
        $this->redirect('/login');
    }
    
    private function createDefaultSettings(int $userId): void
    {
        $settingsModel = new \App\Models\Setting();
        
        // Create default business hours (Monday to Saturday)
        for ($day = 0; $day <= 6; $day++) {
            $isClosed = ($day == 0); // Sunday closed
            $openTime = $isClosed ? null : '09:00:00';
            $closeTime = $isClosed ? null : ($day == 6 ? '13:00:00' : '20:00:00'); // Saturday half day
            
            $settingsModel->create([
                'user_id' => $userId,
                'day_of_week' => $day,
                'open_time' => $openTime,
                'close_time' => $closeTime,
                'slot_minutes' => 15,
                'allow_overlaps' => 0,
                'closed' => $isClosed ? 1 : 0
            ]);
        }
    }
}