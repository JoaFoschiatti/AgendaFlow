<?php
$title = 'Agenda - AgendaFlow';
// Renombrar la variable para evitar conflictos con View::render
$view = $viewType ?? 'day';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-calendar-week"></i> Agenda
        </h1>
    </div>
    <div class="col-auto">
        <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quickAppointmentModal">
            <i class="bi bi-plus-circle"></i> Nuevo Turno
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Date Navigation -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="btn-group" role="group">
                    <a href="<?= $basePath ?>/appointments?view=day&date=<?php echo $date; ?>" 
                       class="btn <?php echo $view === 'day' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-calendar-day"></i> Día
                    </a>
                    <a href="<?= $basePath ?>/appointments?view=week&date=<?php echo $date; ?>" 
                       class="btn <?php echo $view === 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-calendar-week"></i> Semana
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 text-center">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <?php 
                    $prevDate = date('Y-m-d', strtotime($date . ' -1 ' . ($view === 'week' ? 'week' : 'day')));
                    $nextDate = date('Y-m-d', strtotime($date . ' +1 ' . ($view === 'week' ? 'week' : 'day')));
                    ?>
                    <a href="<?= $basePath ?>/appointments?view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    
                    <h5 class="mb-0">
                        <?php if ($view === 'week'): ?>
                            <?php 
                            $weekStart = date('d/m', strtotime($weekDates[0]));
                            $weekEnd = date('d/m', strtotime($weekDates[6]));
                            echo "{$weekStart} - {$weekEnd}";
                            ?>
                        <?php else: ?>
                            <?php echo \App\Core\Helpers::formatDateSpanish($date, 'l d/m/Y'); ?>
                        <?php endif; ?>
                    </h5>
                    
                    <a href="<?= $basePath ?>/appointments?view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 text-end">
                <input type="date" 
                       class="form-control d-inline-block w-auto" 
                       value="<?php echo $date; ?>"
                       onchange="window.location.href='<?= $basePath ?>/appointments?view=<?php echo $view; ?>&date=' + this.value">
                       
                <a href="<?= $basePath ?>/appointments?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>" 
                   class="btn btn-outline-primary">
                    Hoy
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Appointments Display -->
<?php if ($view === 'week'): ?>
    <!-- Week View -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th width="100">Hora</th>
                            <?php foreach ($weekDates as $weekDate): ?>
                                <th class="text-center <?php echo $weekDate === date('Y-m-d') ? 'bg-light' : ''; ?>">
                                    <?php 
                                    $dayName = \App\Core\Helpers::getDayName(date('w', strtotime($weekDate)));
                                    $dayNum = date('d', strtotime($weekDate));
                                    ?>
                                    <div><?php echo $dayName; ?></div>
                                    <div class="small"><?php echo $dayNum; ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($hour = 8; $hour <= 20; $hour++): ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <?php echo str_pad($hour, 2, '0', STR_PAD_LEFT); ?>:00
                                </td>
                                <?php foreach ($weekDates as $weekDate): ?>
                                    <td class="position-relative <?php echo $weekDate === date('Y-m-d') ? 'bg-light' : ''; ?>" style="height: 60px;">
                                        <?php 
                                        $dayAppointments = $appointments[$weekDate] ?? [];
                                        foreach ($dayAppointments as $apt):
                                            $aptHour = (int)date('H', strtotime($apt['starts_at']));
                                            if ($aptHour === $hour):
                                        ?>
                                            <div class="p-1 mb-1 rounded text-white small" 
                                                 style="background-color: <?php echo $apt['service_color'] ?? '#6c757d'; ?>; cursor: pointer;"
                                                 onclick="showAppointmentDetails(<?php echo $apt['id']; ?>)"
                                                 data-bs-toggle="tooltip"
                                                 title="<?php echo htmlspecialchars($apt['client_name']); ?>">
                                                <div class="text-truncate">
                                                    <strong><?php echo date('H:i', strtotime($apt['starts_at'])); ?></strong>
                                                    <?php echo htmlspecialchars($apt['client_name']); ?>
                                                </div>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Day View -->
    <?php if (empty($appointments)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3">No hay turnos programados</h5>
                <p class="text-muted">No tienes turnos para este día</p>
                <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#quickAppointmentModal">
                    <i class="bi bi-plus-circle"></i> Crear primer turno
                </button>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($appointments as $appointment): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="bi bi-clock"></i> 
                                        <?php echo date('H:i', strtotime($appointment['starts_at'])); ?>
                                        <?php if ($appointment['ends_at']): ?>
                                            - <?php echo date('H:i', strtotime($appointment['ends_at'])); ?>
                                        <?php endif; ?>
                                    </h5>
                                    <h6 class="mb-2">
                                        <i class="bi bi-person"></i> 
                                        <?php echo htmlspecialchars($appointment['client_name']); ?>
                                    </h6>
                                    <p class="mb-2">
                                        <span class="badge" style="background-color: <?php echo $appointment['service_color'] ?? '#6c757d'; ?>">
                                            <?php echo htmlspecialchars($appointment['service_name']); ?>
                                        </span>
                                        <span class="ms-2 fw-bold"><?php echo \App\Core\Helpers::formatPrice($appointment['price']); ?></span>
                                    </p>
                                    
                                    <?php if ($appointment['phone']): ?>
                                        <p class="mb-1">
                                            <i class="bi bi-telephone"></i> 
                                            <?php echo htmlspecialchars($appointment['phone']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($appointment['notes']): ?>
                                        <p class="mb-0 text-muted small">
                                            <i class="bi bi-sticky"></i> 
                                            <?php echo htmlspecialchars($appointment['notes']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-end">
                                    <?php if ($appointment['status'] === 'scheduled'): ?>
                                        <span class="badge bg-primary mb-2">Programado</span>
                                    <?php elseif ($appointment['status'] === 'completed'): ?>
                                        <span class="badge bg-success mb-2">Completado</span>
                                    <?php elseif ($appointment['status'] === 'canceled'): ?>
                                        <span class="badge bg-danger mb-2">Cancelado</span>
                                    <?php elseif ($appointment['status'] === 'no_show'): ?>
                                        <span class="badge bg-warning mb-2">No asistió</span>
                                    <?php endif; ?>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="<?= $basePath ?>/appointments/<?php echo $appointment['id']; ?>/edit">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                            </li>
                                            
                                            <?php if ($appointment['status'] === 'scheduled'): ?>
                                                <li>
                                                    <form method="POST" action="<?= $basePath ?>/appointments/<?php echo $appointment['id']; ?>/complete" style="display: inline;">
                                                        <?php echo \App\Core\CSRF::field(); ?>
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="bi bi-check-circle"></i> Marcar completado
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" action="<?= $basePath ?>/appointments/<?php echo $appointment['id']; ?>/cancel" style="display: inline;">
                                                        <?php echo \App\Core\CSRF::field(); ?>
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-x-circle"></i> Cancelar
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($appointment['phone']): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="<?= $basePath ?>/appointments/<?php echo $appointment['id']; ?>/whatsapp" target="_blank">
                                                        <i class="bi bi-whatsapp"></i> Enviar WhatsApp
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Quick Appointment Modal -->
<div class="modal fade" id="quickAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Nuevo Turno Rápido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $basePath ?>/appointments/store">
                <div class="modal-body">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="client_name" class="form-label">Nombre del cliente *</label>
                        <input type="text" 
                               class="form-control" 
                               id="client_name" 
                               name="client_name" 
                               required 
                               autofocus>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Fecha *</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date" 
                                   name="date" 
                                   value="<?php echo $date; ?>"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time" class="form-label">Hora *</label>
                            <input type="time" 
                                   class="form-control" 
                                   id="time" 
                                   name="time" 
                                   value="09:00"
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="service_id" class="form-label">Servicio *</label>
                        <select class="form-select" id="service_id" name="service_id" required onchange="updatePrice()">
                            <option value="">Seleccionar servicio...</option>
                            <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>"
                                                data-price="<?php echo $service['price']; ?>"
                                                data-duration="<?php echo $service['duration'] ?? 30; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Precio *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="price" 
                                   name="price" 
                                   step="0.01"
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono (opcional)</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="2"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="save_client" 
                               name="save_client" 
                               value="1">
                        <label class="form-check-label" for="save_client">
                            Guardar cliente para futuros turnos
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Crear Turno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updatePrice() {
    const select = document.getElementById('service_id');
    const priceInput = document.getElementById('price');
    
    if (select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        priceInput.value = price;
    } else {
        priceInput.value = '';
    }
}

function showAppointmentDetails(id) {
    // Could open a modal with appointment details
    window.location.href = '<?= $basePath ?>/appointments/' + id + '/edit';
}
</script>