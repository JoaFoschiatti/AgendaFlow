<?php

namespace App\Core;

use App\Core\Url;

abstract class Controller
{
    protected array $config;
    protected ?array $user = null;
    
    public function __construct()
    {
        $this->config = Config::get();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->getCurrentUser();
        }
    }
    
    protected function render(string $view, array $data = []): void
    {
        // Prevent browser caching for dynamic pages
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $urlGenerator = function (string $path = ''): string {
            return Url::to($path);
        };

        $fullUrlGenerator = function (string $path = ''): string {
            return Url::full($path);
        };

        View::render($view, array_merge($data, [
            'user' => $this->user,
            'config' => $this->config,
            'basePath' => Url::basePath(),
            'baseUrl' => Url::full(),
            'url' => $urlGenerator,
            'fullUrl' => $fullUrlGenerator,
        ]));
    }
    
    protected function redirect(string $url): void
    {
        $target = $this->prepareRedirectUrl($url);
        $isCliTest = (PHP_SAPI === 'cli') && (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'test');
        http_response_code(302);
        header("Location: {$target}");
        if ($isCliTest) {
            throw new \App\Core\Http\HaltRequest('redirect');
        }
        exit;
    }

    private function prepareRedirectUrl(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $path = $url;

        if ($path === '' || $path === null) {
            $path = '/';
        } elseif ($path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        $basePath = $this->resolveBasePath();

        if ($basePath === '') {
            return $path;
        }

        return rtrim($basePath, '/') . $path;
    }

    protected function resolveBasePath(): string
    {

        return Url::basePath();

    }
    
    protected function json(array $data, int $statusCode = 200): void
    {
        $isCliTest = (PHP_SAPI === 'cli') && (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'test');
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        if ($isCliTest) {
            throw new \App\Core\Http\HaltRequest('json');
        }
        exit;
    }
    
    protected function requireAuth(): void
    {
        if (!$this->user) {
            $_SESSION['flash_error'] = 'Debes iniciar sesión para acceder a esta página.';
            $this->redirect('/login');
        }
    }
    
    protected function requireGuest(): void
    {
        if ($this->user) {
            $this->redirect('/dashboard');
        }
    }
    
    protected function requireActiveSubscription(): void
    {
        $this->requireAuth();
        
        if (!$this->hasActiveSubscription()) {
            $_SESSION['flash_error'] = 'Necesitas una suscripción activa para realizar esta acción.';
            $this->redirect('/subscription');
        }
    }
    
    protected function hasActiveSubscription(): bool
    {
        if (!$this->user) {
            return false;
        }
        
        // Check if in trial period
        if ($this->user['subscription_status'] === 'trialing') {
            $trialEnds = new \DateTime($this->user['trial_ends_at']);
            $now = new \DateTime();
            return $trialEnds > $now;
        }
        
        // Check if has active subscription
        return $this->user['subscription_status'] === 'active';
    }
    
    protected function canEdit(): bool
    {
        return $this->hasActiveSubscription();
    }
    
    protected function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $userModel = new \App\Models\User();
        return $userModel->find($_SESSION['user_id']);
    }
    
    protected function validateCSRF(): bool
    {
        return CSRF::validate($_POST['_token'] ?? '');
    }
    
    protected function setFlash(string $type, string $message): void
    {
        $_SESSION["flash_{$type}"] = $message;
    }
    
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $fieldRules = explode('|', $rule);
            
            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field] = "El campo {$field} es requerido.";
                    break;
                }
                
                if (strpos($fieldRule, 'min:') === 0 && !empty($value)) {
                    $min = (int) substr($fieldRule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres.";
                        break;
                    }
                }
                
                if (strpos($fieldRule, 'max:') === 0 && !empty($value)) {
                    $max = (int) substr($fieldRule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field] = "El campo {$field} no puede tener más de {$max} caracteres.";
                        break;
                    }
                }
                
                if ($fieldRule === 'email' && !empty($value)) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "El campo {$field} debe ser un email válido.";
                        break;
                    }
                }
                
                if ($fieldRule === 'numeric' && !empty($value)) {
                    if (!is_numeric($value)) {
                        $errors[$field] = "El campo {$field} debe ser numérico.";
                        break;
                    }
                }
            }
        }
        
        return $errors;
    }
    
    protected function auditLog(string $action, ?string $entity = null, ?int $entityId = null, array $payload = []): void
    {
        $auditModel = new \App\Models\AuditLog();
        $auditModel->log(
            $this->user['id'] ?? null,
            $action,
            $entity,
            $entityId,
            $payload,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
    }
    
    protected function validateOwnership(?array $resource, string $redirectPath = '/'): void
    {
        if (!$resource || ($resource['user_id'] ?? null) != $this->user['id']) {
            $this->setFlash('error', 'Recurso no encontrado.');
            $this->redirect($redirectPath);
        }
    }
}
