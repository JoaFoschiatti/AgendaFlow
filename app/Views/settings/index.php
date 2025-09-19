<?php
$title = 'Configuraci&oacute;n - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-gear"></i> Configuraci&oacute;n
        </h1>
        <p class="text-muted">Configura tu cuenta y preferencias</p>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <!-- Settings Navigation -->
        <div class="list-group mb-4">
            <a href="#account" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                <i class="bi bi-person"></i> Informaci&oacute;n de Cuenta
            </a>
            <a href="#business-hours" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="bi bi-clock"></i> Horarios de Atenci&oacute;n
            </a>
            <a href="#preferences" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="bi bi-sliders"></i> Preferencias
            </a>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="tab-content">
            <!-- Account Information -->
            <div class="tab-pane fade show active" id="account">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informaci&oacute;n de Cuenta</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= $basePath ?>/settings/update">
                            <?php echo \App\Core\CSRF::field(); ?>
                            
                            <div class="mb-3">
                                <label for="business_name" class="form-label">Nombre del Negocio *</label>
                                <input type="text" 
                                       class="form-control <?php echo isset($_SESSION['errors']['business_name']) ? 'is-invalid' : ''; ?>" 
                                       id="business_name" 
                                       name="business_name" 
                                       value="<?php echo $_SESSION['old']['business_name'] ?? $user['business_name']; ?>"
                                       required>
                                <?php if (isset($_SESSION['errors']['business_name'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $_SESSION['errors']['business_name']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Tu Nombre *</label>
                                <input type="text" 
                                       class="form-control <?php echo isset($_SESSION['errors']['name']) ? 'is-invalid' : ''; ?>" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo $_SESSION['old']['name'] ?? $user['name']; ?>"
                                       required>
                                <?php if (isset($_SESSION['errors']['name'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $_SESSION['errors']['name']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       value="<?php echo $user['email']; ?>"
                                       disabled>
                                <small class="text-muted">El email no puede ser modificado</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Tel&eacute;fono</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo $_SESSION['old']['phone'] ?? $user['phone']; ?>"
                                       placeholder="351-1234567">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="timezone" class="form-label">Zona Horaria</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <option value="America/Argentina/Buenos_Aires" <?php echo $user['timezone'] == 'America/Argentina/Buenos_Aires' ? 'selected' : ''; ?>>
                                            Buenos Aires (UTC-3)
                                        </option>
                                        <option value="America/Argentina/Cordoba" <?php echo $user['timezone'] == 'America/Argentina/Cordoba' ? 'selected' : ''; ?>>
                                            C&oacute;rdoba (UTC-3)
                                        </option>
                                        <option value="America/Argentina/Mendoza" <?php echo $user['timezone'] == 'America/Argentina/Mendoza' ? 'selected' : ''; ?>>
                                            Mendoza (UTC-3)
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="currency" class="form-label">Moneda</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="ARS" <?php echo $user['currency'] == 'ARS' ? 'selected' : ''; ?>>
                                            ARS - Peso Argentino
                                        </option>
                                        <option value="USD" <?php echo $user['currency'] == 'USD' ? 'selected' : ''; ?>>
                                            USD - D&oacute;lar
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Guardar Cambios
                            </button>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-lock"></i> Necesitas una suscripci&oacute;n activa para modificar la configuraci&oacute;n.
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Business Hours -->
            <div class="tab-pane fade" id="business-hours">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Horarios de Atenci&oacute;n</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= $basePath ?>/settings/hours">
                            <?php echo \App\Core\CSRF::field(); ?>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>D&iacute;a</th>
                                            <th>Cerrado</th>
                                            <th>Apertura</th>
                                            <th>Cierre</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($businessHours as $day => $hours): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo \App\Core\Helpers::getDayName($day); ?></strong>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input day-closed" 
                                                           type="checkbox" 
                                                           name="days[<?php echo $day; ?>][closed]"
                                                           id="closed_<?php echo $day; ?>"
                                                           data-day="<?php echo $day; ?>"
                                                           <?php echo $hours['closed'] ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="time" 
                                                       class="form-control form-control-sm day-time" 
                                                       name="days[<?php echo $day; ?>][open_time]"
                                                       id="open_<?php echo $day; ?>"
                                                       value="<?php echo $hours['open_time'] ? substr($hours['open_time'], 0, 5) : '09:00'; ?>"
                                                       <?php echo $hours['closed'] ? 'disabled' : ''; ?>>
                                            </td>
                                            <td>
                                                <input type="time" 
                                                       class="form-control form-control-sm day-time" 
                                                       name="days[<?php echo $day; ?>][close_time]"
                                                       id="close_<?php echo $day; ?>"
                                                       value="<?php echo $hours['close_time'] ? substr($hours['close_time'], 0, 5) : '20:00'; ?>"
                                                       <?php echo $hours['closed'] ? 'disabled' : ''; ?>>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="slot_minutes" class="form-label">Duraci&oacute;n de franjas horarias</label>
                                    <select class="form-select" id="slot_minutes" name="slot_minutes">
                                        <option value="15" <?php echo ($businessHours[1]['slot_minutes'] ?? 15) == 15 ? 'selected' : ''; ?>>15 minutos</option>
                                        <option value="30" <?php echo ($businessHours[1]['slot_minutes'] ?? 15) == 30 ? 'selected' : ''; ?>>30 minutos</option>
                                        <option value="45" <?php echo ($businessHours[1]['slot_minutes'] ?? 15) == 45 ? 'selected' : ''; ?>>45 minutos</option>
                                        <option value="60" <?php echo ($businessHours[1]['slot_minutes'] ?? 15) == 60 ? 'selected' : ''; ?>>60 minutos</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Configuraci&oacute;n de turnos</label>
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="allow_overlaps" 
                                               name="allow_overlaps"
                                               value="1"
                                               <?php echo ($businessHours[1]['allow_overlaps'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allow_overlaps">
                                            Permitir turnos superpuestos
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Guardar Horarios
                            </button>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-lock"></i> Necesitas una suscripci&oacute;n activa para modificar los horarios.
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Preferences -->
            <div class="tab-pane fade" id="preferences">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Preferencias</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Pr&oacute;ximamente podr&aacute;s configurar m&aacute;s opciones como:
                        </div>
                        <ul>
                            <li>Notificaciones por email</li>
                            <li>Recordatorios autom&aacute;ticos</li>
                            <li>Plantillas de mensajes</li>
                            <li>Personalizaci&oacute;n de colores</li>
                            <li>Integraci&oacute;n con calendario</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle day time inputs based on closed checkbox
document.querySelectorAll('.day-closed').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const day = this.dataset.day;
        const openInput = document.getElementById('open_' + day);
        const closeInput = document.getElementById('close_' + day);
        
        if (this.checked) {
            openInput.disabled = true;
            closeInput.disabled = true;
        } else {
            openInput.disabled = false;
            closeInput.disabled = false;
        }
    });
});
</script>

<?php
// Clear old input and errors
unset($_SESSION['old']);
unset($_SESSION['errors']);
?>