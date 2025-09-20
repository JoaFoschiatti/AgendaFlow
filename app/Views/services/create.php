<?php
$title = 'Crear Servicio - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-scissors"></i> Crear Servicio
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= $basePath ?>/services/store">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del servicio *</label>
                        <input type="text" 
                               class="form-control <?php echo isset($_SESSION['errors']['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" 
                               name="name" 
                               value="<?php echo $_SESSION['old']['name'] ?? ''; ?>"
                               placeholder="Ej: Corte de pelo"
                               required 
                               autofocus>
                        <?php if (isset($_SESSION['errors']['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price_default" class="form-label">Precio por defecto *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number"
                                   class="form-control <?php echo isset($_SESSION['errors']['price']) ? 'is-invalid' : ''; ?>"
                                   id="price_default"
                                   name="price_default"
                                   value="<?php echo $_SESSION['old']['price_default'] ?? ''; ?>"
                                   placeholder="0.00"
                                   step="0.01"
                                   min="0"
                                   required>
                            <?php if (isset($_SESSION['errors']['price_default'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $_SESSION['errors']['price_default']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Este precio se usar&aacute; por defecto al crear turnos</small>
                    </div>

                    <div class="mb-3">
                        <label for="duration_min" class="form-label">Duraci&oacute;n estimada (opcional)</label>
                        <div class="input-group">
                            <input type="number"
                                   class="form-control"
                                   id="duration_min"
                                   name="duration_min"
                                   value="<?php echo $_SESSION['old']['duration_min'] ?? ''; ?>"
                                   placeholder="30"
                                   min="5"
                                   max="480"
                                   step="5">
                            <span class="input-group-text">minutos</span>
                        </div>
                        <small class="text-muted">Ayuda a calcular el horario de fin del turno</small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="color" class="form-label">Color identificador</label>
                        <input type="color" 
                               class="form-control form-control-color" 
                               id="color" 
                               name="color" 
                               value="<?php echo $_SESSION['old']['color'] ?? '#6c757d'; ?>"
                               title="Elige un color">
                        <small class="text-muted">Este color se usar&aacute; para identificar el servicio en la agenda</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Crear Servicio
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
                    <i class="bi bi-info-circle"></i> Informaci&oacute;n
                </h5>
                <p>Los servicios te permiten:</p>
                <ul>
                    <li>Organizar mejor tu agenda</li>
                    <li>Establecer precios predeterminados</li>
                    <li>Calcular autom&aacute;ticamente la duraci&oacute;n de los turnos</li>
                    <li>Generar reportes por tipo de servicio</li>
                    <li>Identificar visualmente los turnos con colores</li>
                </ul>
                <p class="mb-0">
                    <strong>Tip:</strong> Puedes editar el precio en cada turno individual si es necesario.
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