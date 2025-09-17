<?php
$title = 'Editar Cliente - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-person-gear"></i> Editar Cliente
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= $basePath ?>/clients/<?php echo $client['id']; ?>/update">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" 
                               class="form-control <?php echo isset($_SESSION['errors']['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" 
                               name="name" 
                               value="<?php echo $_SESSION['old']['name'] ?? $client['name']; ?>"
                               required 
                               autofocus>
                        <?php if (isset($_SESSION['errors']['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone"
                               value="<?php echo $_SESSION['old']['phone'] ?? $client['phone']; ?>"
                               placeholder="351-1234567">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="4"><?php echo $_SESSION['old']['notes'] ?? $client['notes']; ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                        <a href="<?= $basePath ?>/clients" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Historial de Turnos</h5>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <p class="text-muted text-center py-3">
                        Este cliente no tiene turnos registrados.
                    </p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($appointments, 0, 10) as $appointment): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo \App\Core\Helpers::formatDateTime($appointment['starts_at'], 'd/m/Y H:i'); ?>
                                        </h6>
                                        <p class="mb-0">
                                            <span class="badge" style="background-color: <?php echo $appointment['service_color'] ?? '#6c757d'; ?>">
                                                <?php echo htmlspecialchars($appointment['service_name']); ?>
                                            </span>
                                            <span class="ms-2"><?php echo \App\Core\Helpers::formatPrice($appointment['price']); ?></span>
                                        </p>
                                    </div>
                                    <div>
                                        <?php if ($appointment['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Completado</span>
                                        <?php elseif ($appointment['status'] === 'scheduled'): ?>
                                            <span class="badge bg-primary">Programado</span>
                                        <?php elseif ($appointment['status'] === 'canceled'): ?>
                                            <span class="badge bg-danger">Cancelado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($appointments) > 10): ?>
                        <p class="text-center text-muted mt-3">
                            Mostrando 10 de <?php echo count($appointments); ?> turnos
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3 bg-light">
            <div class="card-body">
                <h6>Información del cliente</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Registrado:</dt>
                    <dd class="col-sm-8"><?php echo \App\Core\Helpers::formatDateTime($client['created_at']); ?></dd>
                    
                    <dt class="col-sm-4">Total turnos:</dt>
                    <dd class="col-sm-8"><?php echo count($appointments); ?></dd>
                    
                    <?php if ($client['phone']): ?>
                    <dt class="col-sm-4">WhatsApp:</dt>
                    <dd class="col-sm-8">
                        <a href="<?php echo \App\Core\Helpers::generateWhatsAppLink($client['phone'], 'Hola ' . $client['name'] . '!'); ?>" 
                           target="_blank" 
                           class="btn btn-sm btn-success">
                            <i class="bi bi-whatsapp"></i> Enviar mensaje
                        </a>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php
// Clear old input and errors
unset($_SESSION['old']);
unset($_SESSION['errors']);
?>