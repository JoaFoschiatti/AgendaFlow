<?php
$title = 'Suscripción - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-credit-card"></i> Suscripción
        </h1>
        <p class="text-muted">Gestiona tu plan y métodos de pago</p>
    </div>
</div>

<?php if ($user['subscription_status'] === 'trialing'): ?>
    <!-- Trial Status -->
    <div class="card mb-4 border-primary">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="card-title">
                        <i class="bi bi-gift"></i> Período de Prueba Gratis
                    </h5>
                    <?php if ($trialDaysRemaining > 0): ?>
                        <p class="mb-2">
                            Te quedan <strong><?php echo $trialDaysRemaining; ?> días</strong> de prueba gratis.
                        </p>
                        <p class="text-muted mb-0">
                            Tu prueba termina el <?php echo \App\Core\Helpers::formatDate($user['trial_ends_at'], 'd/m/Y'); ?>
                        </p>
                    <?php else: ?>
                        <p class="text-danger mb-0">
                            Tu período de prueba ha terminado. Activa tu suscripción para continuar.
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($trialDaysRemaining > 0): ?>
                        <div class="progress mb-2" style="height: 10px;">
                            <?php 
                            $totalDays = $config['business']['trial_days'];
                            $usedDays = $totalDays - $trialDaysRemaining;
                            $percentage = ($usedDays / $totalDays) * 100;
                            ?>
                            <div class="progress-bar bg-warning" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <small class="text-muted">
                            <?php echo $usedDays; ?> de <?php echo $totalDays; ?> días usados
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($user['subscription_status'] === 'active' && $subscription): ?>
    <!-- Active Subscription -->
    <div class="card mb-4 border-success">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="card-title">
                        <i class="bi bi-check-circle text-success"></i> Suscripción Activa
                    </h5>
                    <p class="mb-2">
                        Plan: <strong><?php echo $config['business']['plan_name']; ?></strong>
                    </p>
                    <p class="mb-2">
                        Precio: <strong><?php echo \App\Core\Helpers::formatPrice($subscription['amount']); ?>/mes</strong>
                    </p>
                    <?php if ($subscription['next_charge_at']): ?>
                        <p class="text-muted mb-0">
                            Próximo cobro: <?php echo \App\Core\Helpers::formatDate($subscription['next_charge_at'], 'd/m/Y'); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-x-circle"></i> Cancelar Suscripción
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- No Active Subscription -->
    <div class="alert alert-warning" role="alert">
        <h5 class="alert-heading">
            <i class="bi bi-exclamation-triangle"></i> Sin Suscripción Activa
        </h5>
        <p>
            <?php if (\App\Core\Helpers::isTrialExpired($user['trial_ends_at'])): ?>
                Tu período de prueba ha terminado. Activa tu suscripción para continuar usando todas las funcionalidades de AgendaFlow.
            <?php else: ?>
                No tienes una suscripción activa. Puedes activarla cuando lo desees.
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<!-- Pricing Card -->
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card text-center">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Plan Mensual</h4>
            </div>
            <div class="card-body">
                <h2 class="display-4 mb-0">
                    <?php echo \App\Core\Helpers::formatPrice($config['business']['plan_price']); ?>
                </h2>
                <p class="text-muted mb-4">por mes (IVA incluido)</p>
                
                <ul class="list-unstyled mb-4">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i> 
                        Turnos ilimitados
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i> 
                        Servicios ilimitados
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i> 
                        Base de clientes
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i> 
                        Reportes detallados
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i> 
                        Recordatorios por WhatsApp
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i> 
                        Exportación de datos
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i> 
                        Soporte prioritario
                    </li>
                </ul>
                
                <?php if ($user['subscription_status'] !== 'active'): ?>
                    <?php echo \App\Core\CSRF::field(); ?>
                    <button type="button" id="checkout-btn" class="btn btn-primary btn-lg" onclick="initMercadoPagoCheckout()">
                        <i class="bi bi-credit-card"></i> Activar Suscripción
                    </button>
                    
                    <div id="checkout-loading" class="d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                    
                    <p class="mt-3 mb-0">
                        <img src="https://http2.mlstatic.com/storage/logos-api-admin/a5f047d0-9be0-11ec-aad4-c3381f368aaf-m.svg" 
                             alt="Mercado Pago" 
                             height="30">
                    </p>
                    <small class="text-muted">
                        Pago seguro con Mercado Pago
                    </small>
                <?php else: ?>
                    <button class="btn btn-success btn-lg" disabled>
                        <i class="bi bi-check-circle"></i> Plan Activo
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Benefits -->
<div class="row mt-5">
    <div class="col-12">
        <h4 class="mb-4 text-center">¿Por qué elegir AgendaFlow?</h4>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Seguro y Confiable</h5>
                <p class="text-muted">
                    Tus datos están protegidos con encriptación de nivel bancario. 
                    Backups automáticos diarios.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-graph-up text-success" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Crece tu Negocio</h5>
                <p class="text-muted">
                    Organiza mejor tu tiempo, reduce ausencias y 
                    aumenta tus ingresos con una agenda profesional.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-headset text-info" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Soporte Dedicado</h5>
                <p class="text-muted">
                    Equipo de soporte disponible para ayudarte. 
                    Actualizaciones y mejoras constantes sin costo extra.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- FAQ -->
<div class="row mt-5">
    <div class="col-12">
        <h4 class="mb-4">Preguntas Frecuentes</h4>
        
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        ¿Puedo cancelar en cualquier momento?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Sí, puedes cancelar tu suscripción en cualquier momento. 
                        Seguirás teniendo acceso hasta el final del período pagado.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        ¿Qué métodos de pago aceptan?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Aceptamos todos los medios de pago disponibles en Mercado Pago: 
                        tarjetas de crédito, débito, Mercado Pago, y efectivo en puntos de pago.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        ¿Mis datos están seguros?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Absolutamente. Usamos encriptación SSL para todas las comunicaciones 
                        y tus datos de pago son procesados directamente por Mercado Pago, 
                        nunca almacenamos información de tarjetas.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<?php if ($user['subscription_status'] === 'active'): ?>
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Suscripción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cancelar tu suscripción?</p>
                <p>Podrás seguir usando AgendaFlow hasta el final del período pagado actual.</p>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    Siempre puedes reactivar tu suscripción más tarde.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mantener Suscripción</button>
                <form method="POST" action="<?= $basePath ?>/subscription/cancel" style="display: inline;">
                    <?php echo \App\Core\CSRF::field(); ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Confirmar Cancelación
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MercadoPago Checkout Pro Integration -->
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
async function initMercadoPagoCheckout() {
    const checkoutBtn = document.getElementById('checkout-btn');
    const loadingDiv = document.getElementById('checkout-loading');
    
    // Show loading
    checkoutBtn.classList.add('d-none');
    loadingDiv.classList.remove('d-none');
    
    try {
        // Get CSRF token
        const csrfToken = document.querySelector('input[name="_token"]').value;
        
        // Create preference
        const response = await fetch('<?= $basePath ?>/api/payment/preference', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({})
        });
        
        // Primero obtener el texto de la respuesta
        const responseText = await response.text();
        console.log('Respuesta raw:', responseText);
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Error parseando JSON:', parseError);
            console.error('Respuesta recibida:', responseText.substring(0, 500));
            throw new Error('La respuesta del servidor no es JSON válido. Revisa la consola para más detalles.');
        }
        
        if (!response.ok) {
            throw new Error(data.error || 'Error al crear la preferencia de pago');
        }
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Verificar que tenemos los datos necesarios
        if (!data.preference_id || !data.init_point) {
            console.error('Datos recibidos:', data);
            throw new Error('No se recibió ID de preferencia o URL de pago');
        }
        
        console.log('Preferencia creada:', {
            id: data.preference_id,
            url: data.init_point
        });
        
        // Redirigir directamente al checkout
        window.location.href = data.init_point;
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar el pago. Por favor, intenta nuevamente.');
        
        // Hide loading and show button again
        checkoutBtn.classList.remove('d-none');
        loadingDiv.classList.add('d-none');
    }
}
</script>