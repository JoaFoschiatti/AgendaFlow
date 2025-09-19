<?php

namespace App\Core;

class MercadoPago
{
    private string $accessToken;
    private bool $sandbox;
    private string $baseUrl;
    
    public function __construct()
    {
        $config = Config::get('mercadopago');
        $this->accessToken = $config['access_token'];
        $this->sandbox = $config['sandbox'];
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
    
    public function validateWebhookSignature(string $signatureHeader, string $requestId, string $dataId): bool
    {
        $secret = Config::get('mercadopago.webhook_secret', '');
        
        if ($secret === '') {
            // Development or test mode without secret configured
            return true;
        }
        
        if ($signatureHeader === '' || $requestId === '' || $dataId === '') {
            return false;
        }
        
        $parts = array_filter(array_map('trim', explode(',', $signatureHeader)));
        $signatureValues = [];
        
        foreach ($parts as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key !== null && $value !== null) {
                $signatureValues[$key] = $value;
            }
        }
        
        if (!isset($signatureValues['ts'], $signatureValues['v1'])) {
            return false;
        }
        
        $timestamp = $signatureValues['ts'];
        $providedHash = $signatureValues['v1'];
        
        $manifests = [
            sprintf('id:%s;request-id:%s;ts:%s', $dataId, $requestId, $timestamp),
            sprintf('id:%s&request-id:%s&ts:%s', $dataId, $requestId, $timestamp),
        ];
        
        foreach ($manifests as $manifest) {
            $calculated = hash_hmac('sha256', $manifest, $secret);
            if (hash_equals($calculated, $providedHash)) {
                return true;
            }
        }
        
        return false;
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