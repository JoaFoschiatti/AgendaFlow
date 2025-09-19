<?php
$title = 'Editar Servicio - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-scissors"></i> Editar Servicio
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= $basePath ?>/services/<?php echo $service['id']; ?>/update">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del servicio *</label>
                        <input type="text" 
                               class="form-control <?php echo isset($_SESSION['errors']['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" 
                               name="name" 
                               value="<?php echo $_SESSION['old']['name'] ?? $service['name']; ?>"
                               required 
                               autofocus>
                        <?php if (isset($_SESSION['errors']['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Precio por defecto *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number"
                                   class="form-control <?php echo isset($_SESSION['errors']['price']) ? 'is-invalid' : ''; ?>"
                                   id="price"
                                   name="price"
                                   value="<?php echo $_SESSION['old']['price'] ?? $service['price']; ?>"
                                   step="0.01"
                                   min="0"
                                   required>
                            <?php if (isset($_SESSION['errors']['price'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $_SESSION['errors']['price']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Este precio se usar&aacute; por defecto al crear turnos</small>
                    </div>

                    <div class="mb-3">
                        <label for="duration" class="form-label">Duraci&oacute;n estimada (opcional)</label>
                        <div class="input-group">
                            <input type="number"
                                   class="form-control"
                                   id="duration"
                                   name="duration"
                                   value="<?php echo $_SESSION['old']['duration'] ?? $service['duration']; ?>"
                                   placeholder="30"
                                   min="5"
                                   max="480"
                                   step="5">
                            <span class="input-group-text">minutos</span>
                        </div>
                        <small class="text-muted">Ayuda a calcular el horario de fin del turno</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Color identificador</label>
                        <input type="color" 
                               class="form-control form-control-color" 
                               id="color" 
                               name="color" 
                               value="<?php echo $_SESSION['old']['color'] ?? $service['color'] ?? '#6c757d'; ?>"
                               title="Elige un color">
                        <small class="text-muted">Este color se usar&aacute; para identificar el servicio en la agenda</small>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   <?php echo (isset($_SESSION['old']['is_active']) ? $_SESSION['old']['is_active'] : $service['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Servicio activo
                            </label>
                        </div>
                        <small class="text-muted">Los servicios inactivos no aparecer&aacute;n al crear nuevos turnos</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                        <a href="<?= $basePath ?>/services" class="btn btn-outline-secondary">
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
                    <i class="bi bi-info-circle"></i> Informaci&oacute;n del servicio
                </h5>
                <dl class="row">
                    <dt class="col-sm-4">Creado:</dt>
                    <dd class="col-sm-8"><?php echo \App\Core\Helpers::formatDateTime($service['created_at']); ?></dd>
                    
                    <dt class="col-sm-4">&Uacute;ltima actualizaci&oacute;n:</dt>
                    <dd class="col-sm-8"><?php echo \App\Core\Helpers::formatDateTime($service['updated_at']); ?></dd>
                    
                    <dt class="col-sm-4">Estado actual:</dt>
                    <dd class="col-sm-8">
                        <?php if ($service['is_active']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                
                <hr>
                
                <p class="mb-0">
                    <strong>Nota:</strong> Los cambios en el precio no afectar&aacute;n a los turnos ya creados.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Clear old input and errors
unset($_SESSION['old']);
unset($_SESSION['errors']);
?>