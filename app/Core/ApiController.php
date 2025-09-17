<?php

namespace App\Core;

class ApiController extends Controller
{
    protected ?array $currentUser = null;
    protected array $requestData = [];
    protected string $apiVersion = 'v1';

    public function __construct()
    {
        parent::__construct();

        // Set JSON response headers
        header('Content-Type: application/json; charset=utf-8');
        header('X-API-Version: ' . $this->apiVersion);

        // Enable CORS for API
        $this->enableCORS();

        // Parse request data
        $this->parseRequestData();
    }

    protected function enableCORS(): void
    {
        $allowedOrigins = Config::get('api.allowed_origins', ['*']);

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Max-Age: 86400");
        }

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    protected function parseRequestData(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $rawData = file_get_contents('php://input');
            $this->requestData = json_decode($rawData, true) ?? [];
        } else {
            $this->requestData = $_REQUEST;
        }
    }

    protected function requireApiAuth(): void
    {
        try {
            $token = JWT::extractTokenFromHeader();

            if (!$token) {
                throw new \Exception('No token provided', 401);
            }

            $payload = JWT::validateToken($token);

            if ($payload['type'] !== 'access') {
                throw new \Exception('Invalid token type', 401);
            }

            // Load user from database
            $userModel = new \App\Models\User();
            $this->currentUser = $userModel->find($payload['user_id']);

            if (!$this->currentUser) {
                throw new \Exception('User not found', 401);
            }

            // Check if subscription is active
            if (!$userModel->hasActiveSubscription($this->currentUser['id'])) {
                throw new \Exception('Subscription expired', 402);
            }

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), $e->getCode() ?: 401);
        }
    }

    protected function successResponse($data = null, string $message = 'Success', int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function errorResponse(string $message = 'Error', int $code = 400, array $errors = []): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function paginatedResponse(array $data, int $total, int $page, int $perPage, string $message = 'Success'): void
    {
        $totalPages = ceil($total / $perPage);

        $this->successResponse([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ], $message);
    }

    protected function validateRequired(array $fields, array $data = null): array
    {
        $data = $data ?? $this->requestData;
        $errors = [];

        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = "The field {$field} is required";
            }
        }

        if (!empty($errors)) {
            $this->errorResponse('Validation failed', 422, $errors);
        }

        return $data;
    }

    protected function validateOwnership($resource, string $userIdField = 'user_id'): void
    {
        if (!$resource) {
            $this->errorResponse('Resource not found', 404);
        }

        if ($resource[$userIdField] != $this->currentUser['id']) {
            $this->errorResponse('Forbidden', 403);
        }
    }

    protected function logApiAccess(string $endpoint, string $method, ?array $payload = null): void
    {
        $this->auditLog('api_access', 'api', null, [
            'endpoint' => $endpoint,
            'method' => $method,
            'user_id' => $this->currentUser['id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'payload' => $payload
        ]);
    }
}