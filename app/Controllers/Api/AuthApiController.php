<?php

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\JWT;
use App\Core\Auth;
use App\Core\RateLimiter;
use App\Models\User;

class AuthApiController extends ApiController
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function login(): void
    {
        // Apply rate limiting for login attempts
        $identifier = $_SERVER['REMOTE_ADDR'] . ':login';
        RateLimiter::middleware($identifier, 5, 15); // 5 attempts per 15 minutes

        // Validate required fields
        $this->validateRequired(['email', 'password']);

        $email = filter_var($this->requestData['email'], FILTER_VALIDATE_EMAIL);
        $password = $this->requestData['password'];

        if (!$email) {
            $this->errorResponse('Invalid email format', 422);
        }

        // Find user by email
        $user = $this->userModel->findByEmail($email);

        if (!$user || !Auth::verifyPassword($password, $user['password_hash'])) {
            $this->logApiAccess('/api/v1/auth/login', 'POST', ['email' => $email, 'status' => 'failed']);
            $this->errorResponse('Invalid credentials', 401);
        }

        // Check if subscription is active
        if (!$this->userModel->hasActiveSubscription($user['id'])) {
            $this->errorResponse('Subscription expired. Please renew to continue.', 402);
        }

        // Generate JWT tokens
        $tokens = JWT::generateToken([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]);

        // Log successful login
        $this->logApiAccess('/api/v1/auth/login', 'POST', ['email' => $email, 'status' => 'success']);

        // Return user data with tokens
        $this->successResponse([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'business_name' => $user['business_name'],
                'subscription_status' => $user['subscription_status'],
                'trial_ends_at' => $user['trial_ends_at']
            ],
            'tokens' => $tokens
        ], 'Login successful');
    }

    public function register(): void
    {
        // Apply rate limiting for registration
        $identifier = $_SERVER['REMOTE_ADDR'] . ':register';
        RateLimiter::middleware($identifier, 3, 60); // 3 attempts per hour

        // Validate required fields
        $this->validateRequired(['name', 'email', 'password', 'business_name']);

        $data = [
            'name' => htmlspecialchars($this->requestData['name']),
            'email' => filter_var($this->requestData['email'], FILTER_VALIDATE_EMAIL),
            'password' => $this->requestData['password'],
            'business_name' => htmlspecialchars($this->requestData['business_name']),
            'phone' => htmlspecialchars($this->requestData['phone'] ?? '')
        ];

        // Validate email
        if (!$data['email']) {
            $this->errorResponse('Invalid email format', 422);
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            $this->errorResponse('Password must be at least 8 characters long', 422);
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            $this->errorResponse('Email already registered', 409);
        }

        // Create user with trial
        $userId = $this->userModel->createWithTrial($data);

        if (!$userId) {
            $this->errorResponse('Registration failed. Please try again.', 500);
        }

        // Get created user
        $user = $this->userModel->find($userId);

        // Generate JWT tokens
        $tokens = JWT::generateToken([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]);

        // Log registration
        $this->logApiAccess('/api/v1/auth/register', 'POST', ['email' => $data['email']]);

        $this->successResponse([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'business_name' => $user['business_name'],
                'subscription_status' => $user['subscription_status'],
                'trial_ends_at' => $user['trial_ends_at']
            ],
            'tokens' => $tokens
        ], 'Registration successful. Your 14-day trial has started.', 201);
    }

    public function refresh(): void
    {
        $this->validateRequired(['refresh_token']);

        try {
            $refreshToken = $this->requestData['refresh_token'];
            $tokens = JWT::refreshToken($refreshToken);

            $this->successResponse([
                'tokens' => $tokens
            ], 'Token refreshed successfully');

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), $e->getCode() ?: 401);
        }
    }

    public function me(): void
    {
        $this->requireApiAuth();

        $this->successResponse([
            'user' => [
                'id' => $this->currentUser['id'],
                'name' => $this->currentUser['name'],
                'email' => $this->currentUser['email'],
                'business_name' => $this->currentUser['business_name'],
                'phone' => $this->currentUser['phone'],
                'timezone' => $this->currentUser['timezone'],
                'currency' => $this->currentUser['currency'],
                'subscription_status' => $this->currentUser['subscription_status'],
                'trial_starts_at' => $this->currentUser['trial_starts_at'],
                'trial_ends_at' => $this->currentUser['trial_ends_at'],
                'created_at' => $this->currentUser['created_at']
            ]
        ], 'User profile retrieved');
    }

    public function logout(): void
    {
        $this->requireApiAuth();

        // In a stateless JWT system, logout is handled client-side
        // Here we can log the logout action or blacklist the token if needed

        $this->logApiAccess('/api/v1/auth/logout', 'POST', ['user_id' => $this->currentUser['id']]);

        $this->successResponse(null, 'Logout successful');
    }
}