<?php
$title = 'Dashboard - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h1>
        <p class="text-muted">Bienvenido, <?php echo htmlspecialchars($user['business_name']); ?></p>
    </div>
    <div class="col-auto">
        <a href="<?= $basePath ?>/appointments/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Turno
        </a>
    </div>
</div>

<?php if ($trialDaysRemaining !== null && $trialDaysRemaining <= 7): ?>
<div class="alert alert-warning" role="alert">
    <h5 class="alert-heading">
        <i class="bi bi-clock"></i> Tu prueba gratis termina en <?php echo $trialDaysRemaining; ?> d&iacute;as
    </h5>
    <p class="mb-2">
        Activa tu suscripci&oacute;n para seguir usando AgendaFlow sin interrupciones.
    </p>
    <hr>
    <a href="<?= $basePath ?>/subscription" class="btn btn-warning">
        <i class="bi bi-credit-card"></i> Activar Suscripci&oacute;n
    </a>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Turnos Hoy</h6>
                        <h3 class="mb-0"><?php echo count($todayAppointments); ?></h3>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-calendar-day fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Ingresos del Mes</h6>
                        <h3 class="mb-0"><?php echo \App\Core\Helpers::formatPrice($monthRevenue); ?></h3>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-currency-dollar fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Servicios Activos</h6>
                        <h3 class="mb-0"><?php echo $servicesCount; ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-scissors fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Clientes</h6>
                        <h3 class="mb-0"><?php echo $clientsCount; ?></h3>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-people fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Appointments -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check"></i> Turnos de Hoy
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($todayAppointments)): ?>
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-calendar-x fs-1"></i><br>
                        No hay turnos programados para hoy
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($todayAppointments as $appointment): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo \App\Core\Helpers::formatDateTime($appointment['starts_at'], 'H:i'); ?> - 
                                            <?php echo htmlspecialchars($appointment['client_name']); ?>
                                        </h6>
                                        <p class="mb-1">
                                            <span class="badge" style="background-color: <?php echo $appointment['service_color'] ?? '#6c757d'; ?>">
                                                <?php echo htmlspecialchars($appointment['service_name']); ?>
                                            </span>
                                            <span class="ms-2"><?php echo \App\Core\Helpers::formatPrice($appointment['price']); ?></span>
                                        </p>
                                    </div>
                                    <div>
                                        <?php if ($appointment['status'] === 'scheduled'): ?>
                                            <span class="badge bg-primary">Programado</span>
                                        <?php elseif ($appointment['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Completado</span>
                                        <?php elseif ($appointment['status'] === 'canceled'): ?>
                                            <span class="badge bg-danger">Cancelado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <a href="<?= $basePath ?>/appointments" class="btn btn-sm btn-outline-primary">
                    Ver agenda completa →
                </a>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-clock"></i> Pr&oacute;ximos Turnos
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingAppointments)): ?>
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-calendar-plus fs-1"></i><br>
                        No hay turnos pr&oacute;ximos
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($appointment['client_name']); ?>
                                        </h6>
                                        <p class="mb-0 text-muted">
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo \App\Core\Helpers::formatDateTime($appointment['starts_at'], 'd/m'); ?> - 
                                            <?php echo \App\Core\Helpers::formatDateTime($appointment['starts_at'], 'H:i'); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="badge" style="background-color: <?php echo $appointment['service_color'] ?? '#6c757d'; ?>">
                                            <?php echo htmlspecialchars($appointment['service_name']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Services -->
<?php if (!empty($topServices)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-star"></i> Servicios M&aacute;s Solicitados (Este Mes)
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($topServices as $service): ?>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                <div>
                                    <h6 class="mb-1">
                                        <span class="badge" style="background-color: <?php echo $service['color'] ?? '#6c757d'; ?>">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </span>
                                    </h6>
                                    <p class="mb-0 text-muted">
                                        <?php echo $service['count']; ?> turnos
                                    </p>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo \App\Core\Helpers::formatPrice($service['revenue'] ?? 0); ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i> Acciones R&aacute;pidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?= $basePath ?>/appointments/create" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Nuevo Turno
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= $basePath ?>/services/create" class="btn btn-outline-success w-100">
                            <i class="bi bi-scissors"></i> Nuevo Servicio
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= $basePath ?>/clients" class="btn btn-outline-info w-100">
                            <i class="bi bi-person-plus"></i> Nuevo Cliente
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= $basePath ?>/reports" class="btn btn-outline-warning w-100">
                            <i class="bi bi-graph-up"></i> Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>