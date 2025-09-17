<?php
$title = 'Editar Turno - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-calendar-event"></i> Editar Turno
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/AgendaFlow/public/appointments/<?php echo $appointment['id']; ?>/update">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="client_name" class="form-label">Nombre del cliente *</label>
                        <input type="text" 
                               class="form-control <?php echo isset($_SESSION['errors']['client_name']) ? 'is-invalid' : ''; ?>" 
                               id="client_name" 
                               name="client_name" 
                               value="<?php echo $_SESSION['old']['client_name'] ?? $appointment['client_name']; ?>"
                               required 
                               autofocus>
                        <?php if (isset($_SESSION['errors']['client_name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['client_name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Fecha *</label>
                            <input type="date" 
                                   class="form-control <?php echo isset($_SESSION['errors']['date']) ? 'is-invalid' : ''; ?>" 
                                   id="date" 
                                   name="date" 
                                   value="<?php echo $_SESSION['old']['date'] ?? date('Y-m-d', strtotime($appointment['starts_at'])); ?>"
                                   required>
                            <?php if (isset($_SESSION['errors']['date'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $_SESSION['errors']['date']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="time" class="form-label">Hora *</label>
                            <input type="time" 
                                   class="form-control <?php echo isset($_SESSION['errors']['time']) ? 'is-invalid' : ''; ?>" 
                                   id="time" 
                                   name="time" 
                                   value="<?php echo $_SESSION['old']['time'] ?? date('H:i', strtotime($appointment['starts_at'])); ?>"
                                   required>
                            <?php if (isset($_SESSION['errors']['time'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $_SESSION['errors']['time']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="service_id" class="form-label">Servicio *</label>
                        <select class="form-select <?php echo isset($_SESSION['errors']['service_id']) ? 'is-invalid' : ''; ?>" 
                                id="service_id" 
                                name="service_id" 
                                required 
                                onchange="updatePrice()">
                            <option value="">Seleccionar servicio...</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>"
                                        data-price="<?php echo $service['price']; ?>"
                                        data-duration="<?php echo $service['duration'] ?? 30; ?>"
                                        <?php echo ($_SESSION['old']['service_id'] ?? $appointment['service_id']) == $service['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($_SESSION['errors']['service_id'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['service_id']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Precio *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   class="form-control <?php echo isset($_SESSION['errors']['price']) ? 'is-invalid' : ''; ?>" 
                                   id="price" 
                                   name="price" 
                                   value="<?php echo $_SESSION['old']['price'] ?? $appointment['price']; ?>"
                                   step="0.01"
                                   required>
                            <?php if (isset($_SESSION['errors']['price'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $_SESSION['errors']['price']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Puedes ajustar el precio si es necesario</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone"
                               value="<?php echo $_SESSION['old']['phone'] ?? $appointment['phone']; ?>"
                               placeholder="351-1234567">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="3"><?php echo $_SESSION['old']['notes'] ?? $appointment['notes']; ?></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="save_client" 
                               name="save_client" 
                               value="1"
                               <?php echo $appointment['client_id'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="save_client">
                            Guardar/actualizar cliente para futuros turnos
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                        <a href="/AgendaFlow/public/appointments?date=<?php echo date('Y-m-d', strtotime($appointment['starts_at'])); ?>" 
                           class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-info-circle"></i> Información del turno
                </h5>
                
                <dl class="row">
                    <dt class="col-sm-4">Estado actual:</dt>
                    <dd class="col-sm-8">
                        <?php if ($appointment['status'] === 'scheduled'): ?>
                            <span class="badge bg-primary">Programado</span>
                        <?php elseif ($appointment['status'] === 'completed'): ?>
                            <span class="badge bg-success">Completado</span>
                        <?php elseif ($appointment['status'] === 'canceled'): ?>
                            <span class="badge bg-danger">Cancelado</span>
                        <?php elseif ($appointment['status'] === 'no_show'): ?>
                            <span class="badge bg-warning">No asistió</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Creado:</dt>
                    <dd class="col-sm-8"><?php echo \App\Core\Helpers::formatDateTime($appointment['created_at']); ?></dd>
                    
                    <dt class="col-sm-4">Última actualización:</dt>
                    <dd class="col-sm-8"><?php echo \App\Core\Helpers::formatDateTime($appointment['updated_at']); ?></dd>
                </dl>
                
                <?php if ($appointment['status'] === 'scheduled'): ?>
                <hr>
                <h6>Acciones rápidas</h6>
                <div class="d-flex gap-2">
                    <form method="POST" action="/AgendaFlow/public/appointments/<?php echo $appointment['id']; ?>/complete" style="display: inline;">
                        <?php echo \App\Core\CSRF::field(); ?>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Marcar completado
                        </button>
                    </form>
                    
                    <form method="POST" action="/AgendaFlow/public/appointments/<?php echo $appointment['id']; ?>/cancel" style="display: inline;">
                        <?php echo \App\Core\CSRF::field(); ?>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-x-circle"></i> Cancelar turno
                        </button>
                    </form>
                    
                    <?php if ($appointment['phone']): ?>
                        <a href="/AgendaFlow/public/appointments/<?php echo $appointment['id']; ?>/whatsapp" 
                           target="_blank"
                           class="btn btn-success btn-sm">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Check for conflicts -->
        <div class="card mt-3">
            <div class="card-body">
                <h6>Verificación de disponibilidad</h6>
                <div id="overlapCheck">
                    <p class="text-muted">Selecciona fecha y hora para verificar disponibilidad</p>
                </div>
            </div>
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
        // Only update if user hasn't manually changed the price
        if (priceInput.dataset.manuallyChanged !== 'true') {
            priceInput.value = price;
        }
    }
}

// Track manual price changes
document.getElementById('price').addEventListener('input', function() {
    this.dataset.manuallyChanged = 'true';
});

// Check for overlaps when date/time changes
function checkOverlap() {
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    const serviceSelect = document.getElementById('service_id');
    const duration = serviceSelect.options[serviceSelect.selectedIndex]?.getAttribute('data-duration') || 30;
    
    if (date && time) {
        fetch(`/AgendaFlow/public/api/appointments/check-overlap?date=${date}&time=${time}&duration=${duration}&exclude=<?php echo $appointment['id']; ?>`)
            .then(response => response.json())
            .then(data => {
                const overlapDiv = document.getElementById('overlapCheck');
                if (data.overlap) {
                    overlapDiv.innerHTML = '<p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Hay un conflicto con otro turno en ese horario</p>';
                } else {
                    overlapDiv.innerHTML = '<p class="text-success"><i class="bi bi-check-circle"></i> Horario disponible</p>';
                }
            })
            .catch(error => {
                console.error('Error checking overlap:', error);
            });
    }
}

document.getElementById('date').addEventListener('change', checkOverlap);
document.getElementById('time').addEventListener('change', checkOverlap);
document.getElementById('service_id').addEventListener('change', checkOverlap);

// Initial check
checkOverlap();
</script>

<?php
// Clear old input and errors
unset($_SESSION['old']);
unset($_SESSION['errors']);
?>