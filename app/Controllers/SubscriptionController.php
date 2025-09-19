<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\MercadoPago;
use App\Models\User;
use App\Models\Subscription;

class SubscriptionController extends Controller
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
    
    public function index(): void
    {
        $this->requireAuth();
        
        $subscription = $this->subscriptionModel->getByUser($this->user['id']);
        $trialDaysRemaining = null;
        
        if ($this->user['subscription_status'] === 'trialing') {
            $trialDaysRemaining = \App\Core\Helpers::getTrialDaysRemaining($this->user['trial_ends_at']);
        }
        
        $this->render('subscription/index', [
            'subscription' => $subscription,
            'trialDaysRemaining' => $trialDaysRemaining
        ]);
    }
    
    public function checkout(): void
    {
        $this->requireAuth();

        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Token de seguridad inválido.'], 403);
        }

        try {
            // Create MercadoPago preapproval
            $config = \App\Core\Config::get();

            // Check if MercadoPago is enabled
            if (!isset($config['mercadopago']['enabled']) || !$config['mercadopago']['enabled']) {
                $this->setFlash('warning', 'Los pagos en línea no están habilitados temporalmente. Por favor, contacta al administrador para activar tu suscripción manualmente.');
                $this->redirect('/subscription');
                return;
            }

            $preapprovalData = [
                'reason' => $config['business']['plan_name'],
                'amount' => $config['business']['plan_price'],
                'currency' => $config['app']['currency'],
                'email' => $this->user['email'],
                'external_reference' => 'user_' . $this->user['id'],
                'back_url' => $config['app']['url'] . '/subscription/success',
                'notification_url' => $config['app']['url'] . '/webhook/mercadopago'
            ];
            
            $response = $this->mercadoPago->createPreapproval($preapprovalData);
            
            if (!isset($response['init_point'])) {
                throw new \Exception('No se pudo crear la suscripción en MercadoPago');
            }
            
            // Save subscription record
            $this->subscriptionModel->createOrUpdate($this->user['id'], [
                'currency' => $config['app']['currency'],
                'amount' => $config['business']['plan_price'],
                'status' => 'pending',
                'mp_preapproval_id' => $response['id'],
                'raw_data' => json_encode($response)
            ]);
            
            $this->auditLog('subscription_checkout_initiated', 'subscription', null, [
                'preapproval_id' => $response['id']
            ]);
            
            // Redirect to MercadoPago checkout
            $this->redirect($response['init_point']);
            
        } catch (\Exception $e) {
            error_log('MercadoPago checkout error: ' . $e->getMessage());
            $this->setFlash('error', 'Error al procesar el pago. Por favor intenta nuevamente.');
            $this->redirect('/subscription');
        }
    }
    
    public function success(): void
    {
        $this->requireAuth();
        
        // Get preapproval_id from query params
        $preapprovalId = $_GET['preapproval_id'] ?? null;
        
        if ($preapprovalId) {
            try {
                // Get preapproval status from MercadoPago
                $preapproval = $this->mercadoPago->getPreapproval($preapprovalId);
                
                if ($preapproval['status'] === 'authorized') {
                    // Update user subscription status
                    $this->userModel->updateSubscriptionStatus($this->user['id'], 'active', $preapprovalId);
                    
                    // Update subscription record
                    $this->subscriptionModel->updateStatus($preapprovalId, 'active', $preapproval);
                    
                    $this->auditLog('subscription_activated', 'subscription', null, [
                        'preapproval_id' => $preapprovalId
                    ]);
                    
                    $this->setFlash('success', '¡Suscripción activada exitosamente! Ya puedes disfrutar de todas las funcionalidades de AgendaFlow.');
                } else {
                    $this->setFlash('warning', 'Tu suscripción está pendiente de confirmación. Te notificaremos cuando esté activa.');
                }
            } catch (\Exception $e) {
                error_log('MercadoPago success error: ' . $e->getMessage());
                $this->setFlash('info', 'Estamos procesando tu pago. Te notificaremos cuando tu suscripción esté activa.');
            }
        }
        
        $this->redirect('/subscription');
    }
    
    public function failure(): void
    {
        $this->requireAuth();
        
        $this->setFlash('error', 'No se pudo procesar el pago. Por favor intenta con otro medio de pago.');
        $this->redirect('/subscription');
    }
    
    public function cancel(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Token de seguridad inválido.'], 403);
        }
        
        $subscription = $this->subscriptionModel->getByUser($this->user['id']);
        
        if (!$subscription || $subscription['status'] !== 'active') {
            $this->setFlash('error', 'No tienes una suscripción activa para cancelar.');
            $this->redirect('/subscription');
        }
        
        try {
            // Cancel in MercadoPago
            $this->mercadoPago->cancelPreapproval($subscription['mp_preapproval_id'], 'User requested cancellation');
            
            // Update local status
            $this->userModel->updateSubscriptionStatus($this->user['id'], 'canceled');
            $this->subscriptionModel->updateStatus($subscription['mp_preapproval_id'], 'canceled');
            
            $this->auditLog('subscription_canceled', 'subscription', $subscription['id']);
            
            $this->setFlash('success', 'Tu suscripción ha sido cancelada. Puedes seguir usando AgendaFlow hasta el final del período pagado.');
            
        } catch (\Exception $e) {
            error_log('MercadoPago cancel error: ' . $e->getMessage());
            $this->setFlash('error', 'Error al cancelar la suscripción. Por favor contacta a soporte.');
        }
        
        $this->redirect('/subscription');
    }
}