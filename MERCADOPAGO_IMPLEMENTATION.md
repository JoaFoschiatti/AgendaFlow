# Implementación de MercadoPago en AgendaFlow

## 📋 Resumen Ejecutivo

Esta guía detalla la implementación completa del sistema de pagos con MercadoPago para las suscripciones de AgendaFlow. Incluye la integración del SDK, validación de pagos, webhooks, y medidas de seguridad.

## 🎯 Objetivos

1. Permitir que los usuarios paguen su suscripción mensual ($2500 ARS)
2. Validar pagos de forma segura para evitar fraudes
3. Actualizar automáticamente el estado de suscripción
4. Manejar renovaciones y cancelaciones
5. Mantener un registro auditable de todas las transacciones

## 🏗️ Arquitectura de la Solución

### Flujo de Pago

```
Usuario → Checkout Pro → MercadoPago → Webhook → AgendaFlow → DB
```

1. **Usuario**: Clickea "Activar Suscripción"
2. **AgendaFlow**: Crea una preferencia de pago con datos del usuario
3. **MercadoPago**: Procesa el pago mediante Checkout Pro
4. **Webhook**: MercadoPago notifica el resultado
5. **Validación**: AgendaFlow valida la notificación
6. **Actualización**: Se actualiza el estado de suscripción

## 🔧 Implementación Técnica

### 1. Configuración Inicial

#### Credenciales Necesarias
- **Access Token**: Para autenticación en la API
- **Public Key**: Para el frontend (Checkout Pro)
- **Webhook Secret**: Para validar notificaciones (opcional pero recomendado)

#### Variables de Entorno (.env)
```env
MP_ACCESS_TOKEN=TEST-xxx-xxx (producción: APP_USR-xxx)
MP_PUBLIC_KEY=TEST-xxx-xxx
MP_WEBHOOK_SECRET=xxx
MP_NOTIFICATION_URL=https://tudominio.com/AgendaFlow/public/webhooks/mercadopago
```

### 2. Instalación del SDK

```bash
composer require mercadopago/dx-php
```

### 3. Estructura de Base de Datos

#### Tabla: payments
```sql
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    mp_payment_id VARCHAR(100) UNIQUE,
    mp_preference_id VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'refunded') DEFAULT 'pending',
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ARS',
    payment_method VARCHAR(50),
    payment_type VARCHAR(50),
    payer_email VARCHAR(150),
    payer_identification VARCHAR(50),
    processed_at DATETIME,
    notification_received_at DATETIME,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_mp_payment_id (mp_payment_id),
    INDEX idx_user_status (user_id, status)
);
```

#### Tabla: payment_logs
```sql
CREATE TABLE payment_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT,
    event_type VARCHAR(50),
    event_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);
```

### 4. Implementación del Controlador de Pagos

#### PaymentController.php

```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payment;
use App\Models\User;
use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;
use MercadoPago\Payer;

class PaymentController extends Controller
{
    private Payment $paymentModel;
    private User $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->paymentModel = new Payment();
        $this->userModel = new User();
        
        // Configurar SDK de MercadoPago
        SDK::setAccessToken($_ENV['MP_ACCESS_TOKEN']);
    }
    
    /**
     * Crear preferencia de pago para checkout
     */
    public function createPreference(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        try {
            // Crear preferencia
            $preference = new Preference();
            
            // Item (suscripción)
            $item = new Item();
            $item->title = 'Suscripción AgendaFlow - Plan Mensual';
            $item->quantity = 1;
            $item->unit_price = 2500.00;
            $item->currency_id = 'ARS';
            
            // Payer (comprador)
            $payer = new Payer();
            $payer->email = $this->user['email'];
            $payer->name = $this->user['name'];
            
            // Configurar preferencia
            $preference->items = [$item];
            $preference->payer = $payer;
            
            // URLs de retorno
            $baseUrl = $_ENV['APP_URL'] . '/public';
            $preference->back_urls = [
                'success' => $baseUrl . '/subscription/success',
                'failure' => $baseUrl . '/subscription/failure',
                'pending' => $baseUrl . '/subscription/pending'
            ];
            
            // Configuración adicional
            $preference->auto_return = 'approved';
            $preference->binary_mode = true; // Solo aprobado o rechazado
            $preference->statement_descriptor = 'AgendaFlow';
            $preference->external_reference = 'user_' . $this->user['id'] . '_' . time();
            
            // Webhook URL
            $preference->notification_url = $_ENV['MP_NOTIFICATION_URL'];
            
            // Guardar y obtener ID
            $preference->save();
            
            // Registrar intento de pago
            $this->paymentModel->create([
                'user_id' => $this->user['id'],
                'mp_preference_id' => $preference->id,
                'amount' => 2500.00,
                'status' => 'pending'
            ]);
            
            $this->jsonResponse([
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Payment preference creation failed', $e);
            $this->jsonResponse(['error' => 'Error al crear la preferencia de pago'], 500);
        }
    }
    
    /**
     * Procesar webhook de MercadoPago
     */
    public function webhook(): void
    {
        // Obtener datos del webhook
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Log del webhook recibido
        $this->logWebhook($data);
        
        // Validar origen (opcional pero recomendado)
        if (!$this->validateWebhookSignature()) {
            http_response_code(401);
            exit('Unauthorized');
        }
        
        // Procesar según tipo de notificación
        if ($data['type'] === 'payment') {
            $this->processPaymentNotification($data['data']['id']);
        }
        
        // Responder 200 OK a MercadoPago
        http_response_code(200);
        echo 'OK';
    }
    
    /**
     * Procesar notificación de pago
     */
    private function processPaymentNotification(string $paymentId): void
    {
        try {
            // Obtener información del pago desde MP
            $payment = \MercadoPago\Payment::find_by_id($paymentId);
            
            if (!$payment) {
                throw new \Exception('Payment not found: ' . $paymentId);
            }
            
            // Buscar o crear registro de pago
            $localPayment = $this->paymentModel->findByMpId($paymentId);
            
            if (!$localPayment) {
                // Extraer user_id del external_reference
                preg_match('/user_(\d+)_/', $payment->external_reference, $matches);
                $userId = $matches[1] ?? null;
                
                if (!$userId) {
                    throw new \Exception('Cannot extract user_id from external_reference');
                }
                
                $localPayment = $this->paymentModel->create([
                    'user_id' => $userId,
                    'mp_payment_id' => $payment->id,
                    'amount' => $payment->transaction_amount,
                    'status' => 'pending'
                ]);
            }
            
            // Actualizar información del pago
            $this->paymentModel->update($localPayment['id'], [
                'status' => $this->mapPaymentStatus($payment->status),
                'payment_method' => $payment->payment_method_id,
                'payment_type' => $payment->payment_type_id,
                'payer_email' => $payment->payer->email,
                'payer_identification' => $payment->payer->identification->number ?? null,
                'processed_at' => $payment->date_approved ?? $payment->date_created,
                'notification_received_at' => date('Y-m-d H:i:s'),
                'raw_data' => json_encode($payment)
            ]);
            
            // Si el pago fue aprobado, actualizar suscripción
            if ($payment->status === 'approved') {
                $this->activateSubscription($localPayment['user_id']);
            }
            
        } catch (\Exception $e) {
            $this->logError('Payment notification processing failed', $e);
        }
    }
    
    /**
     * Activar suscripción del usuario
     */
    private function activateSubscription(int $userId): void
    {
        $nextMonth = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        $this->userModel->update($userId, [
            'subscription_status' => 'active',
            'subscription_ends_at' => $nextMonth
        ]);
        
        // Log de auditoría
        $this->auditLog('subscription_activated', 'user', $userId, [
            'valid_until' => $nextMonth
        ]);
    }
    
    /**
     * Validar firma del webhook (seguridad adicional)
     */
    private function validateWebhookSignature(): bool
    {
        if (empty($_ENV['MP_WEBHOOK_SECRET'])) {
            return true; // Si no hay secret configurado, skip validation
        }
        
        $headers = getallheaders();
        $xSignature = $headers['X-Signature'] ?? '';
        $xRequestId = $headers['X-Request-Id'] ?? '';
        
        if (empty($xSignature) || empty($xRequestId)) {
            return false;
        }
        
        // Parsear x-signature header
        $parts = explode(',', $xSignature);
        $ts = null;
        $hash = null;
        
        foreach ($parts as $part) {
            $keyValue = explode('=', $part, 2);
            if ($keyValue[0] === 'ts') {
                $ts = $keyValue[1];
            } elseif ($keyValue[0] === 'v1') {
                $hash = $keyValue[1];
            }
        }
        
        // Validar timestamp (no más de 5 minutos)
        if (abs(time() - intval($ts)) > 300) {
            return false;
        }
        
        // Construir y validar firma
        $body = file_get_contents('php://input');
        $manifest = "id:{$xRequestId};request-id:{$xRequestId};ts:{$ts};{$body}";
        $expectedHash = hash_hmac('sha256', $manifest, $_ENV['MP_WEBHOOK_SECRET']);
        
        return hash_equals($expectedHash, $hash);
    }
    
    /**
     * Mapear estado de MP a estado local
     */
    private function mapPaymentStatus(string $mpStatus): string
    {
        $mapping = [
            'approved' => 'approved',
            'pending' => 'pending',
            'in_process' => 'pending',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded'
        ];
        
        return $mapping[$mpStatus] ?? 'pending';
    }
}
```

### 5. Vista de Suscripción Mejorada

#### subscription/index.php

```javascript
// Inicializar Checkout Pro
const mp = new MercadoPago('<?php echo $_ENV["MP_PUBLIC_KEY"]; ?>', {
    locale: 'es-AR'
});

async function handleSubscription() {
    try {
        // Crear preferencia
        const response = await fetch('/AgendaFlow/public/api/payments/preference', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('[name="_token"]').value
            }
        });
        
        const data = await response.json();
        
        if (data.preference_id) {
            // Abrir checkout
            mp.checkout({
                preference: {
                    id: data.preference_id
                },
                autoOpen: true
            });
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar el pago');
    }
}
```

## 🔐 Medidas de Seguridad

### 1. Validación de Webhooks
- **Firma HMAC**: Validar que la notificación viene de MercadoPago
- **Timestamp**: Rechazar notificaciones viejas (>5 minutos)
- **IP Whitelist**: Opcional, validar IPs de MercadoPago

### 2. Validación de Pagos
- **Doble verificación**: Siempre consultar la API de MP para confirmar el pago
- **Idempotencia**: Usar mp_payment_id como clave única
- **Estados**: Solo activar suscripción con status 'approved'

### 3. Prevención de Fraude
- **Límites**: Un pago por usuario por período
- **Validación de montos**: Verificar que el monto pagado sea correcto
- **Logs completos**: Registrar toda actividad para auditoría

### 4. Protección de Datos
- **Encriptación**: Guardar datos sensibles encriptados
- **PCI Compliance**: No almacenar datos de tarjetas
- **HTTPS**: Obligatorio para producción

## 📝 Checklist de Implementación

### Fase 1: Preparación
- [ ] Crear cuenta en MercadoPago
- [ ] Obtener credenciales de prueba
- [ ] Configurar variables de entorno
- [ ] Instalar SDK via Composer

### Fase 2: Base de Datos
- [ ] Crear tabla payments
- [ ] Crear tabla payment_logs
- [ ] Crear modelo Payment
- [ ] Crear migraciones

### Fase 3: Backend
- [ ] Implementar PaymentController
- [ ] Crear endpoint para preferencias
- [ ] Implementar webhook handler
- [ ] Agregar validación de firmas
- [ ] Implementar logging

### Fase 4: Frontend
- [ ] Integrar SDK de MercadoPago
- [ ] Actualizar vista de suscripción
- [ ] Agregar botón de pago
- [ ] Implementar feedback visual

### Fase 5: Testing
- [ ] Test con tarjetas de prueba
- [ ] Verificar webhooks
- [ ] Validar actualizaciones de estado
- [ ] Probar casos de error

### Fase 6: Producción
- [ ] Cambiar a credenciales de producción
- [ ] Configurar webhook URL en MP
- [ ] Habilitar HTTPS
- [ ] Monitoreo y alertas

## 🧪 Testing

### Tarjetas de Prueba (Argentina)

#### Aprobadas
- Mastercard: 5031 7557 3453 0604
- Visa: 4509 9535 6623 3704

#### Rechazadas
- Mastercard: 5031 7557 3453 0604 (con nombre "REJECTED")

### Datos de Prueba
- DNI: 12345678
- Email: test_user_123456@testuser.com
- CVV: 123
- Vencimiento: 11/25

## 📊 Monitoreo

### KPIs a Trackear
1. **Tasa de conversión**: Usuarios que completan el pago
2. **Tasa de abandono**: Usuarios que no completan checkout
3. **Métodos de pago**: Distribución de medios utilizados
4. **Errores**: Pagos rechazados y sus causas
5. **Tiempo de procesamiento**: Latencia de webhooks

### Logs Importantes
- Todas las preferencias creadas
- Webhooks recibidos
- Validaciones fallidas
- Cambios de estado de suscripción
- Errores de API

## 🚨 Manejo de Errores

### Casos Comunes

1. **Pago rechazado**: Informar al usuario, sugerir otro medio
2. **Webhook duplicado**: Usar idempotencia, responder 200
3. **Timeout de API**: Reintentar con backoff exponencial
4. **Firma inválida**: Rechazar, log de seguridad
5. **Usuario no encontrado**: Log error, notificar admin

## 📚 Referencias

- [Documentación MercadoPago](https://www.mercadopago.com.ar/developers/es/docs)
- [SDK PHP](https://github.com/mercadopago/dx-php)
- [Checkout Pro](https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/landing)
- [Webhooks](https://www.mercadopago.com.ar/developers/es/docs/your-integrations/notifications/webhooks)
- [Test Cards](https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/test-cards)

## 🔄 Próximos Pasos

1. **Suscripciones recurrentes**: Implementar cobros automáticos mensuales
2. **Múltiples planes**: Ofrecer planes trimestrales/anuales con descuento
3. **Cupones**: Sistema de descuentos y promociones
4. **Split payments**: Para marketplace (si aplica)
5. **Wallet**: Permitir guardar medios de pago

---

**Nota**: Esta implementación está diseñada para el mercado argentino con pesos (ARS). Para otros países, ajustar currency_id y validaciones según corresponda.