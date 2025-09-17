<?php
$title = 'Reportes - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-graph-up"></i> Reportes
        </h1>
        <p class="text-muted">Análisis de tu negocio</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= $basePath ?>/reports">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Fecha desde</label>
                    <input type="date" 
                           class="form-control" 
                           id="start_date" 
                           name="start_date" 
                           value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Fecha hasta</label>
                    <input type="date" 
                           class="form-control" 
                           id="end_date" 
                           name="end_date" 
                           value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-3">
                    <label for="service_id" class="form-label">Servicio</label>
                    <select class="form-select" id="service_id" name="service_id">
                        <option value="">Todos los servicios</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>" 
                                    <?php echo $selectedService == $service['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($service['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <a href="<?= $basePath ?>/reports/export?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&service_id=<?php echo $selectedService; ?>" 
                       class="btn btn-success">
                        <i class="bi bi-download"></i> Exportar CSV
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Ingresos Totales</h6>
                        <h3 class="mb-0"><?php echo \App\Core\Helpers::formatPrice($metrics['total_revenue']); ?></h3>
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
                        <h6 class="text-muted mb-2">Turnos Totales</h6>
                        <h3 class="mb-0"><?php echo $metrics['total_appointments']; ?></h3>
                        <small class="text-success">
                            <?php echo $metrics['completed_appointments']; ?> completados
                        </small>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-calendar-check fs-1"></i>
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
                        <h6 class="text-muted mb-2">Ticket Promedio</h6>
                        <h3 class="mb-0"><?php echo \App\Core\Helpers::formatPrice($metrics['average_ticket']); ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-receipt fs-1"></i>
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
                        <h6 class="text-muted mb-2">Tasa Completados</h6>
                        <h3 class="mb-0"><?php echo number_format($metrics['completion_rate'], 1); ?>%</h3>
                        <?php if ($metrics['canceled_appointments'] > 0): ?>
                            <small class="text-danger">
                                <?php echo $metrics['canceled_appointments']; ?> cancelados
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-percent fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i> Ingresos Mensuales
                </h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart"></i> Distribución por Servicio
                </h5>
            </div>
            <div class="card-body">
                <canvas id="servicesChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-table"></i> Detalle de Turnos
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <p class="text-center text-muted py-4">No hay turnos en el período seleccionado</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Precio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($appointments, 0, 20) as $appointment): ?>
                        <tr>
                            <td>
                                <?php echo \App\Core\Helpers::formatDateTime($appointment['starts_at'], 'd/m/Y H:i'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $appointment['service_color'] ?? '#6c757d'; ?>">
                                    <?php echo htmlspecialchars($appointment['service_name'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td><?php echo \App\Core\Helpers::formatPrice($appointment['price']); ?></td>
                            <td>
                                <?php if ($appointment['status'] === 'completed'): ?>
                                    <span class="badge bg-success">Completado</span>
                                <?php elseif ($appointment['status'] === 'scheduled'): ?>
                                    <span class="badge bg-primary">Programado</span>
                                <?php elseif ($appointment['status'] === 'canceled'): ?>
                                    <span class="badge bg-danger">Cancelado</span>
                                <?php elseif ($appointment['status'] === 'no_show'): ?>
                                    <span class="badge bg-warning">No asistió</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (count($appointments) > 20): ?>
                    <p class="text-center text-muted">
                        Mostrando 20 de <?php echo count($appointments); ?> turnos. 
                        <a href="<?= $basePath ?>/reports/export?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&service_id=<?php echo $selectedService; ?>">
                            Exportar todos en CSV
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartData['monthly']['labels']); ?>,
        datasets: [{
            label: 'Ingresos',
            data: <?php echo json_encode($chartData['monthly']['data']); ?>,
            borderColor: 'rgb(79, 70, 229)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '$' + context.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString('es-AR');
                    }
                }
            }
        }
    }
});

// Services Chart
const servicesCtx = document.getElementById('servicesChart').getContext('2d');
new Chart(servicesCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($chartData['services']['labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($chartData['services']['data']); ?>,
            backgroundColor: <?php echo json_encode($chartData['services']['colors']); ?>
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': $' + context.parsed.toLocaleString('es-AR', {minimumFractionDigits: 2});
                    }
                }
            }
        }
    }
});
</script>