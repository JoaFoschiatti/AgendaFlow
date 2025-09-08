<?php
$title = 'Nuevo Turno - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-calendar-plus"></i> Nuevo Turno
        </h1>
    </div>
    <div class="col-auto">
        <a href="/AgendaFlow/public/appointments" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/AgendaFlow/public/appointments/store" id="appointmentForm">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Información del Cliente</h5>
                            
                            <div class="mb-3">
                                <label for="client_name" class="form-label">Nombre del cliente *</label>
                                <input type="text" 
                                       class="form-control <?php echo isset($_SESSION['errors']['client_name']) ? 'is-invalid' : ''; ?>" 
                                       id="client_name" 
                                       name="client_name" 
                                       value="<?php echo $_SESSION['old']['client_name'] ?? ''; ?>"
                                       required 
                                       autofocus>
                                <?php if (isset($_SESSION['errors']['client_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $_SESSION['errors']['client_name']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono (opcional)</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone"
                                       value="<?php echo $_SESSION['old']['phone'] ?? ''; ?>"
                                       placeholder="351-123-4567">
                                <small class="text-muted">Para enviar recordatorios por WhatsApp</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="save_client" 
                                       name="save_client" 
                                       value="1"
                                       <?php echo (isset($_SESSION['old']['save_client']) && $_SESSION['old']['save_client']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="save_client">
                                    Guardar cliente para futuros turnos
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="mb-3">Detalles del Turno</h5>
                            
                            <div class="mb-3">
                                <label for="service_id" class="form-label">Servicio *</label>
                                <select class="form-select <?php echo isset($_SESSION['errors']['service_id']) ? 'is-invalid' : ''; ?>" 
                                        id="service_id" 
                                        name="service_id" 
                                        required
                                        onchange="updateServiceDetails()">
                                    <option value="">Seleccionar servicio...</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" 
                                                data-price="<?php echo $service['price_default']; ?>"
                                                data-duration="<?php echo $service['duration_min'] ?? 30; ?>"
                                                data-color="<?php echo $service['color'] ?? '#6c757d'; ?>"
                                                <?php echo (isset($_SESSION['old']['service_id']) && $_SESSION['old']['service_id'] == $service['id']) ? 'selected' : ''; ?>>
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
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date" class="form-label">Fecha *</label>
                                    <input type="date" 
                                           class="form-control <?php echo isset($_SESSION['errors']['date']) ? 'is-invalid' : ''; ?>" 
                                           id="date" 
                                           name="date" 
                                           value="<?php echo $_SESSION['old']['date'] ?? $defaultDate; ?>"
                                           min="<?php echo date('Y-m-d'); ?>"
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
                                           value="<?php echo $_SESSION['old']['time'] ?? $defaultTime; ?>"
                                           required>
                                    <?php if (isset($_SESSION['errors']['time'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $_SESSION['errors']['time']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Precio *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" 
                                           class="form-control <?php echo isset($_SESSION['errors']['price']) ? 'is-invalid' : ''; ?>" 
                                           id="price" 
                                           name="price" 
                                           step="0.01"
                                           value="<?php echo $_SESSION['old']['price'] ?? ''; ?>"
                                           required>
                                    <?php if (isset($_SESSION['errors']['price'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $_SESSION['errors']['price']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="3"
                                  placeholder="Notas adicionales sobre el turno..."><?php echo $_SESSION['old']['notes'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Crear Turno
                        </button>
                        <button type="button" class="btn btn-outline-secondary ms-2" onclick="checkOverlap()">
                            <i class="bi bi-calendar-check"></i> Verificar Disponibilidad
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Service Details Card -->
        <div class="card mb-3" id="serviceDetailsCard" style="display: none;">
            <div class="card-body">
                <h5 class="card-title">Detalles del Servicio</h5>
                <div id="serviceDetails">
                    <p class="mb-2">
                        <i class="bi bi-clock"></i> Duración: <span id="serviceDuration">-</span> minutos
                    </p>
                    <p class="mb-2">
                        <i class="bi bi-cash"></i> Precio sugerido: $<span id="servicePrice">-</span>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-palette"></i> Color: 
                        <span id="serviceColorBadge" class="badge">Vista previa</span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Quick Clients List -->
        <?php if (!empty($clients)): ?>
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Clientes Recientes</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach (array_slice($clients, 0, 10) as $client): ?>
                        <a href="#" class="list-group-item list-group-item-action" 
                           onclick="selectClient('<?php echo htmlspecialchars($client['name']); ?>', '<?php echo htmlspecialchars($client['phone'] ?? ''); ?>'); return false;">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-0"><?php echo htmlspecialchars($client['name']); ?></h6>
                            </div>
                            <?php if ($client['phone']): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($client['phone']); ?></small>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateServiceDetails() {
    const select = document.getElementById('service_id');
    const priceInput = document.getElementById('price');
    const detailsCard = document.getElementById('serviceDetailsCard');
    
    if (select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        const duration = selectedOption.getAttribute('data-duration');
        const color = selectedOption.getAttribute('data-color');
        
        // Update price
        priceInput.value = price;
        
        // Update service details card
        document.getElementById('serviceDuration').textContent = duration;
        document.getElementById('servicePrice').textContent = price;
        
        const colorBadge = document.getElementById('serviceColorBadge');
        colorBadge.style.backgroundColor = color;
        colorBadge.style.color = '#fff';
        
        detailsCard.style.display = 'block';
    } else {
        priceInput.value = '';
        detailsCard.style.display = 'none';
    }
}

function selectClient(name, phone) {
    document.getElementById('client_name').value = name;
    document.getElementById('phone').value = phone;
    document.getElementById('save_client').checked = false;
}

function checkOverlap() {
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    const serviceId = document.getElementById('service_id').value;
    
    if (!date || !time || !serviceId) {
        alert('Por favor completa fecha, hora y servicio para verificar disponibilidad.');
        return;
    }
    
    const selectedOption = document.getElementById('service_id').options[document.getElementById('service_id').selectedIndex];
    const duration = selectedOption.getAttribute('data-duration') || 30;
    
    // Make AJAX request to check overlap
    fetch(`/AgendaFlow/public/api/appointments/check-overlap?date=${date}&time=${time}&duration=${duration}`)
        .then(response => response.json())
        .then(data => {
            if (data.overlap) {
                alert('⚠️ Ya existe un turno en ese horario. Por favor elige otro horario.');
            } else {
                alert('✅ Horario disponible.');
            }
        })
        .catch(error => {
            console.error('Error checking overlap:', error);
        });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    const dateInput = document.getElementById('date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
    
    // If service is preselected, update details
    updateServiceDetails();
});
</script>

<?php
// Clear old input and errors
unset($_SESSION['old']);
unset($_SESSION['errors']);
?>