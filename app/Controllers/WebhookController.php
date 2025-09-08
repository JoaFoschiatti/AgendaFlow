<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\MercadoPago;
use App\Models\User;
use App\Models\Subscription;

class WebhookController extends Controller
{
    private User $userModel;
    private Subscription $subscriptionModel;
    private MercadoPago $mercadoPago;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->subscriptionModel = new Subscription();
        $this->mercadoPago = new MercadoPago();
    }
    
    public function mercadopago(): void
    {
        // Get webhook data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Log webhook for debugging
        error_log('MercadoPago webhook received: ' . $input);
        
        // Validate signature (optional but recommended)
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        
        if (!empty($signature) && !empty($requestId) && isset($data['data']['id'])) {
            if (!$this->mercadoPago->validateWebhookSignature($signature, $requestId, $data['data']['id'])) {
                error_log('Invalid webhook signature');
                http_response_code(401);
                echo json_encode(['error' => 'Invalid signature']);
                exit;
            }
        }
        
        // Process webhook based on type
        if (isset($data['type']) && isset($data['data']['id'])) {
            try {
                switch ($data['type']) {
                    case 'subscription_preapproval':
                    case 'subscription_authorized_payment':
                        $this->handlePreapprovalUpdate($data['data']['id']);
                        break;
                        
                    case 'payment':
                        $this->handlePayment($data['data']['id']);
                        break;
                        
                    default:
                        error_log('Unknown webhook type: ' . $data['type']);
                }
            } catch (\Exception $e) {
                error_log('Webhook processing error: ' . $e->getMessage());
            }
        }
        
        // Always return 200 OK to MercadoPago
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }
    
    private function handlePreapprovalUpdate(string $preapprovalId): void
    {
        // Get preapproval details from MercadoPago
        $preapproval = $this->mercadoPago->getPreapproval($preapprovalId);
        
        if (!$preapproval) {
            error_log("Preapproval not found: {$preapprovalId}");
            return;
        }
        
        // Find subscription by preapproval ID
        $subscription = $this->subscriptionModel->getByPreapprovalId($preapprovalId);
        
        if (!$subscription) {
            error_log("Subscription not found for preapproval: {$preapprovalId}");
            return;
        }
        
        // Update subscription status based on MercadoPago status
        $mpStatus = $preapproval['status'] ?? '';
        $localStatus = $this->mapMercadoPagoStatus($mpStatus);
        
        // Update subscription
        $this->subscriptionModel->update($subscription['id'], [
            'status' => $localStatus,
            'next_charge_at' => isset($preapproval['next_payment_date']) 
                ? date('Y-m-d H:i:s', strtotime($preapproval['next_payment_date']))
                : null,
            'raw_data' => json_encode($preapproval)
        ]);
        
        // Update user subscription status
        $user = $this->userModel->find($subscription['user_id']);
        if ($user) {
            $this->userModel->updateSubscriptionStatus($user['id'], $localStatus);
            
            // Log the status change
            $this->auditLog(
                'subscription_status_changed',
                'subscription',
                $subscription['id'],
                [
                    'old_status' => $subscription['status'],
                    'new_status' => $localStatus,
                    'mp_status' => $mpStatus
                ]
            );
        }
    }
    
    private function handlePayment(string $paymentId): void
    {
        // Log payment webhook
        error_log("Payment webhook received: {$paymentId}");
        
        // Here you could track individual payments if needed
        // For now, we're focusing on subscription status updates
    }
    
    private function mapMercadoPagoStatus(string $mpStatus): string
    {
        $statusMap = [
            'authorized' => 'active',
            'pending' => 'pending',
            'paused' => 'past_due',
            'cancelled' => 'canceled',
            'canceled' => 'canceled',
        ];
        
        return $statusMap[$mpStatus] ?? 'pending';
    }
    
    protected function auditLog(string $action, ?string $entity = null, ?int $entityId = null, array $payload = []): void
    {
        // Override to allow webhook logging without user session
        $auditModel = new \App\Models\AuditLog();
        $auditModel->log(
            null, // No user in webhook context
            $action,
            $entity,
            $entityId,
            $payload,
            $_SERVER['REMOTE_ADDR'] ?? null,
            'MercadoPago Webhook'
        );
    }
}