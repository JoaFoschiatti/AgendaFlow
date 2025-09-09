<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\CSRF;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;

class PaymentController extends Controller
{
    private Payment $paymentModel;
    private User $userModel;
    private Subscription $subscriptionModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->paymentModel = new Payment();
        $this->userModel = new User();
        $this->subscriptionModel = new Subscription();
    }
    
    /**
     * Create payment preference for MercadoPago checkout
     */
    public function createPreference(): void
    {
        $this->requireAuth();
        
        // Check for JSON request
        $isJsonRequest = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        
        if ($isJsonRequest) {
            // For AJAX JSON requests, check CSRF in header
            $headers = getallheaders();
            $csrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
            if (!CSRF::validate($csrfToken)) {
                $this->json(['error' => 'Token de seguridad inválido'], 403);
                return;
            }
        } else {
            // For regular form requests
            if (!$this->validateCSRF()) {
                $this->json(['error' => 'Token de seguridad inválido'], 403);
                return;
            }
        }
        
        try {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            
            // Initialize MercadoPago SDK
            \MercadoPago\SDK::setAccessToken($config['mercadopago']['access_token']);
            
            // Create preference
            $preference = new \MercadoPago\Preference();
            
            // Create item (subscription)
            $item = new \MercadoPago\Item();
            $item->title = $config['business']['plan_name'];
            $item->quantity = 1;
            $item->unit_price = $config['business']['plan_price'];
            $item->currency_id = $config['app']['currency'];
            $item->description = 'Suscripción mensual a AgendaFlow';
            
            // Set payer info
            $payer = new \MercadoPago\Payer();
            $payer->email = $this->user['email'];
            $payer->name = $this->user['name'];
            
            // Configure preference
            $preference->items = [$item];
            $preference->payer = $payer;
            
            // Set return URLs - REQUIRED for auto_return
            $baseUrl = $config['app']['url'] . '/public';
            $preference->back_urls = [
                'success' => $baseUrl . '/payment/success',
                'failure' => $baseUrl . '/payment/failure',
                'pending' => $baseUrl . '/payment/pending'
            ];
            
            // Additional configuration - auto_return ONLY works if back_urls are defined
            $preference->auto_return = 'approved';
            $preference->binary_mode = true; // Only approved or rejected
            $preference->statement_descriptor = 'AgendaFlow';
            $preference->external_reference = 'user_' . $this->user['id'] . '_' . time();
            
            // Set webhook URL
            $preference->notification_url = $baseUrl . '/webhook/payment';
            
            // Payment methods configuration
            $preference->payment_methods = [
                'excluded_payment_types' => [],
                'installments' => 1, // No installments for subscription
                'default_installments' => 1
            ];
            
            // Save preference
            $saved = $preference->save();
            
            // Check if preference was saved successfully
            if (!$preference->id) {
                // Log the error for debugging
                error_log('MercadoPago Preference Error: ' . json_encode($preference->error ?? 'Unknown error'));
                throw new \Exception('No se pudo crear la preferencia de pago. Verifica las credenciales y configuración.');
            }
            
            // Create payment record
            $paymentId = $this->paymentModel->create([
                'user_id' => $this->user['id'],
                'mp_preference_id' => $preference->id,
                'amount' => $config['business']['plan_price'],
                'currency' => $config['app']['currency'],
                'status' => 'pending',
                'raw_data' => json_encode($preference)
            ]);
            
            // Log event only if payment was created successfully
            if ($paymentId) {
                $this->paymentModel->logEvent($paymentId, 'preference_created', [
                    'preference_id' => $preference->id,
                    'external_reference' => $preference->external_reference
                ]);
                
                $this->auditLog('payment_preference_created', 'payment', $paymentId, [
                    'preference_id' => $preference->id
                ]);
            } else {
                // Log error but continue - payment record is optional for preference creation
                error_log('Payment record could not be created, but preference was successful');
            }
            
            // IMPORTANTE: Para cuentas TEST, SIEMPRE usar sandbox_init_point
            // Determinar qué URL usar
            $initPoint = null;
            
            // Primero intentar con sandbox_init_point (para cuentas TEST)
            if (!empty($preference->sandbox_init_point)) {
                $initPoint = $preference->sandbox_init_point;
                error_log('Using sandbox_init_point: ' . $initPoint);
            }
            // Si no hay sandbox URL, usar la normal
            elseif (!empty($preference->init_point)) {
                $initPoint = $preference->init_point;
                error_log('Using init_point: ' . $initPoint);
            }
            
            // Si no hay ninguna URL, hay un problema
            if (!$initPoint) {
                error_log('No init_point available. Preference dump: ' . json_encode([
                    'id' => $preference->id,
                    'init_point' => $preference->init_point,
                    'sandbox_init_point' => $preference->sandbox_init_point
                ]));
                throw new \Exception('No se pudo obtener la URL de pago');
            }
            
            // Return preference data
            $this->json([
                'preference_id' => $preference->id,
                'init_point' => $initPoint,
                'public_key' => $config['mercadopago']['public_key']
            ]);
            
        } catch (\Exception $e) {
            error_log('Payment preference creation failed: ' . $e->getMessage());
            $this->json(['error' => 'Error al crear la preferencia de pago'], 500);
        }
    }
    
    /**
     * Handle successful payment return
     */
    public function success(): void
    {
        $this->requireAuth();
        
        $paymentId = $_GET['payment_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $externalReference = $_GET['external_reference'] ?? null;
        
        if ($status === 'approved' && $paymentId) {
            // Payment will be confirmed via webhook
            $this->setFlash('success', '¡Pago procesado exitosamente! Tu suscripción se activará en breve.');
            
            $this->auditLog('payment_success_return', 'payment', null, [
                'payment_id' => $paymentId,
                'external_reference' => $externalReference
            ]);
        } else {
            $this->setFlash('info', 'Estamos procesando tu pago. Te notificaremos cuando esté confirmado.');
        }
        
        $this->redirect('/subscription');
    }
    
    /**
     * Handle failed payment return
     */
    public function failure(): void
    {
        $this->requireAuth();
        
        $this->auditLog('payment_failure_return', 'payment', null, $_GET);
        
        $this->setFlash('error', 'No se pudo procesar el pago. Por favor, intenta con otro medio de pago.');
        $this->redirect('/subscription');
    }
    
    /**
     * Handle pending payment return
     */
    public function pending(): void
    {
        $this->requireAuth();
        
        $this->auditLog('payment_pending_return', 'payment', null, $_GET);
        
        $this->setFlash('warning', 'Tu pago está pendiente de confirmación. Te notificaremos cuando se complete.');
        $this->redirect('/subscription');
    }
    
    /**
     * Process webhook notification from MercadoPago
     */
    public function webhook(): void
    {
        // Get raw input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Log webhook reception
        error_log('MercadoPago webhook received: ' . $input);
        
        // Validate webhook signature if configured
        if (!$this->validateWebhookSignature($input)) {
            http_response_code(401);
            echo 'Unauthorized';
            return;
        }
        
        try {
            // Process based on notification type
            if (isset($data['type']) && $data['type'] === 'payment') {
                $this->processPaymentNotification($data['data']['id']);
            } elseif (isset($data['action']) && $data['action'] === 'payment.created') {
                $this->processPaymentNotification($data['data']['id']);
            }
            
            // Always respond 200 OK to MercadoPago
            http_response_code(200);
            echo 'OK';
            
        } catch (\Exception $e) {
            error_log('Webhook processing error: ' . $e->getMessage());
            // Still respond 200 to avoid retries
            http_response_code(200);
            echo 'OK';
        }
    }
    
    /**
     * Process payment notification
     */
    private function processPaymentNotification(string $mpPaymentId): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        
        // Initialize SDK
        \MercadoPago\SDK::setAccessToken($config['mercadopago']['access_token']);
        
        // Get payment info from MercadoPago
        $payment = \MercadoPago\Payment::find_by_id($mpPaymentId);
        
        if (!$payment) {
            throw new \Exception('Payment not found: ' . $mpPaymentId);
        }
        
        // Check if payment already exists (idempotency)
        if ($this->paymentModel->exists($mpPaymentId)) {
            // Update existing payment
            $localPayment = $this->paymentModel->findByMpId($mpPaymentId);
            $this->updatePaymentStatus($localPayment['id'], $payment);
        } else {
            // Create new payment record
            $this->createPaymentFromNotification($payment);
        }
    }
    
    /**
     * Create payment from webhook notification
     */
    private function createPaymentFromNotification(\MercadoPago\Payment $payment): void
    {
        // Extract user ID from external reference
        preg_match('/user_(\d+)_/', $payment->external_reference, $matches);
        $userId = $matches[1] ?? null;
        
        if (!$userId) {
            throw new \Exception('Cannot extract user_id from external_reference: ' . $payment->external_reference);
        }
        
        // Create payment record
        $paymentId = $this->paymentModel->create([
            'user_id' => $userId,
            'mp_payment_id' => $payment->id,
            'mp_preference_id' => $payment->preference_id ?? null,
            'status' => $this->mapPaymentStatus($payment->status),
            'amount' => $payment->transaction_amount,
            'currency' => $payment->currency_id,
            'payment_method' => $payment->payment_method_id,
            'payment_type' => $payment->payment_type_id,
            'payer_email' => $payment->payer->email,
            'payer_identification' => $payment->payer->identification->number ?? null,
            'processed_at' => $payment->date_approved ?? $payment->date_created,
            'notification_received_at' => date('Y-m-d H:i:s'),
            'raw_data' => json_encode($payment)
        ]);
        
        // Log event
        $this->paymentModel->logEvent($paymentId, 'payment_created_from_webhook', [
            'mp_payment_id' => $payment->id,
            'status' => $payment->status
        ]);
        
        // Process based on status
        if ($payment->status === 'approved') {
            $this->activateUserSubscription($userId, $paymentId);
        }
    }
    
    /**
     * Update existing payment status
     */
    private function updatePaymentStatus(int $paymentId, \MercadoPago\Payment $payment): void
    {
        $localPayment = $this->paymentModel->getById($paymentId);
        $newStatus = $this->mapPaymentStatus($payment->status);
        
        // Only update if status changed
        if ($localPayment['status'] !== $newStatus) {
            $this->paymentModel->updateStatus($paymentId, $newStatus, [
                'mp_payment_id' => $payment->id,
                'processed_at' => $payment->date_approved ?? $payment->date_created,
                'notification_received_at' => date('Y-m-d H:i:s'),
                'raw_data' => json_encode($payment)
            ]);
            
            // If newly approved, activate subscription
            if ($newStatus === 'approved' && $localPayment['status'] !== 'approved') {
                $this->activateUserSubscription($localPayment['user_id'], $paymentId);
            }
        }
    }
    
    /**
     * Activate user subscription after successful payment
     */
    private function activateUserSubscription(int $userId, int $paymentId): void
    {
        // Calculate subscription end date (1 month from now)
        $subscriptionEndsAt = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        // Update user subscription status
        $this->userModel->update($userId, [
            'subscription_status' => 'active',
            'subscription_ends_at' => $subscriptionEndsAt
        ]);
        
        // Update or create subscription record
        $this->subscriptionModel->createOrUpdate($userId, [
            'status' => 'active',
            'starts_at' => date('Y-m-d H:i:s'),
            'ends_at' => $subscriptionEndsAt,
            'last_payment_id' => $paymentId,
            'next_billing_date' => date('Y-m-d', strtotime('+1 month'))
        ]);
        
        // Log activation
        $this->auditLog('subscription_activated_by_payment', 'subscription', $userId, [
            'payment_id' => $paymentId,
            'valid_until' => $subscriptionEndsAt
        ]);
        
        // TODO: Send confirmation email to user
    }
    
    /**
     * Validate webhook signature
     */
    private function validateWebhookSignature(string $body): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        
        // If no secret configured, skip validation (not recommended for production)
        if (empty($config['mercadopago']['webhook_secret'])) {
            return true;
        }
        
        $headers = getallheaders();
        $xSignature = $headers['X-Signature'] ?? $headers['x-signature'] ?? '';
        $xRequestId = $headers['X-Request-Id'] ?? $headers['x-request-id'] ?? '';
        
        if (empty($xSignature) || empty($xRequestId)) {
            error_log('Missing webhook signature headers');
            return false;
        }
        
        // Parse signature header
        $parts = explode(',', $xSignature);
        $ts = null;
        $hash = null;
        
        foreach ($parts as $part) {
            $keyValue = explode('=', trim($part), 2);
            if (count($keyValue) === 2) {
                if ($keyValue[0] === 'ts') {
                    $ts = $keyValue[1];
                } elseif ($keyValue[0] === 'v1') {
                    $hash = $keyValue[1];
                }
            }
        }
        
        if (!$ts || !$hash) {
            error_log('Invalid signature format');
            return false;
        }
        
        // Validate timestamp (not older than 5 minutes)
        if (abs(time() - intval($ts)) > 300) {
            error_log('Webhook timestamp too old');
            return false;
        }
        
        // Build manifest and validate signature
        $manifest = "id:{$xRequestId};request-id:{$xRequestId};ts:{$ts};{$body}";
        $expectedHash = hash_hmac('sha256', $manifest, $config['mercadopago']['webhook_secret']);
        
        if (!hash_equals($expectedHash, $hash)) {
            error_log('Invalid webhook signature');
            return false;
        }
        
        return true;
    }
    
    /**
     * Map MercadoPago status to local status
     */
    private function mapPaymentStatus(string $mpStatus): string
    {
        $mapping = [
            'approved' => 'approved',
            'pending' => 'pending',
            'in_process' => 'in_process',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'charged_back' => 'refunded'
        ];
        
        return $mapping[$mpStatus] ?? 'pending';
    }
    
    /**
     * Get user payment history
     */
    public function history(): void
    {
        $this->requireAuth();
        
        $payments = $this->paymentModel->getUserPayments($this->user['id'], 20);
        
        $this->render('payment/history', [
            'payments' => $payments
        ]);
    }
}