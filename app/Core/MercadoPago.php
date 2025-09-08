<?php

namespace App\Core;

class MercadoPago
{
    private string $accessToken;
    private bool $sandbox;
    private string $baseUrl;
    
    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->accessToken = $config['mercadopago']['access_token'];
        $this->sandbox = $config['mercadopago']['sandbox'];
        $this->baseUrl = 'https://api.mercadopago.com';
    }
    
    public function createPreapproval(array $data): array
    {
        $preapprovalData = [
            'reason' => $data['reason'] ?? 'AgendaFlow Mensual',
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $data['amount'] ?? 8900.00,
                'currency_id' => $data['currency'] ?? 'ARS'
            ],
            'back_url' => $data['back_url'],
            'payer_email' => $data['email'],
            'external_reference' => $data['external_reference'] ?? null,
            'notification_url' => $data['notification_url'] ?? null
        ];
        
        $response = $this->makeRequest('POST', '/preapproval', $preapprovalData);
        
        return $response;
    }
    
    public function getPreapproval(string $preapprovalId): array
    {
        return $this->makeRequest('GET', "/preapproval/{$preapprovalId}");
    }
    
    public function cancelPreapproval(string $preapprovalId, string $reason = 'User requested'): array
    {
        return $this->makeRequest('PUT', "/preapproval/{$preapprovalId}", [
            'status' => 'cancelled',
            'reason' => $reason
        ]);
    }
    
    public function searchPreapprovals(string $email): array
    {
        return $this->makeRequest('GET', '/preapproval/search', [
            'payer_email' => $email
        ]);
    }
    
    public function validateWebhookSignature(string $signature, string $requestId, string $dataId): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $secret = $config['mercadopago']['webhook_secret'] ?? '';
        
        if (empty($secret)) {
            // If no secret is configured, skip validation (development only)
            return true;
        }
        
        // Build the manifest string
        $manifest = "id:{$dataId};request-id:{$requestId}";
        
        // Calculate HMAC
        $calculatedSignature = 'ts=' . time() . ',v1=' . hash_hmac('sha256', $manifest, $secret);
        
        // Compare signatures
        return hash_equals($calculatedSignature, $signature);
    }
    
    private function makeRequest(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        // Set common options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        
        // Set method-specific options
        switch ($method) {
            case 'GET':
                if ($data) {
                    $url .= '?' . http_build_query($data);
                }
                break;
                
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $responseData['message'] ?? 'Unknown error';
            throw new \Exception("MercadoPago API Error ({$httpCode}): {$errorMessage}");
        }
        
        return $responseData ?: [];
    }
}