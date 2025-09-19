<?php
$title = 'Clientes - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-people"></i> Clientes
        </h1>
        <p class="text-muted">Gestiona tu base de clientes</p>
    </div>
</div>

<!-- Search and Create -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <form method="GET" action="<?= $basePath ?>/clients">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Buscar por nombre o telÃ©fono..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= $basePath ?>/clients" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i> Limpiar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClientModal">
                    <i class="bi bi-person-plus"></i> Nuevo Cliente
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Clients List -->
<?php if (empty($clients)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3">
                <?php if (!empty($search)): ?>
                    No se encontraron clientes
                <?php else: ?>
                    No hay clientes registrados
                <?php endif; ?>
            </h5>
            <p class="text-muted">
                <?php if (!empty($search)): ?>
                    Intenta con otros tÃ©rminos de bÃºsqueda
                <?php else: ?>
                    Los clientes se crean automÃ&iexcl;ticamente al guardarlos en los turnos
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>TelÃ©fono</th>
                            <th>Turnos</th>
                            <th>Registrado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                <?php if ($client['notes']): ?>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-sticky"></i> <?php echo htmlspecialchars(substr($client['notes'], 0, 50)); ?>...
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client['phone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>" class="text-decoration-none">
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($client['phone']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo $client['appointment_count'] ?? 0; ?> turnos
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo \App\Core\Helpers::formatDate($client['created_at'], 'd/m/Y'); ?>
                                </small>
                            </td>
                            <td class="text-end">
                                <a href="<?= $basePath ?>/clients/<?php echo $client['id']; ?>/edit" 
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip" 
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <?php if (empty($client['appointment_count']) || $client['appointment_count'] == 0): ?>
                                    <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteClient(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['name']); ?>')"
                                            data-bs-toggle="tooltip" 
                                            title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($client['phone']): ?>
                                    <a href="<?php echo \App\Core\Helpers::generateWhatsAppLink($client['phone'], 'Hola ' . $client['name'] . '!'); ?>" 
                                       target="_blank"
                                       class="btn btn-sm btn-outline-success"
                                       data-bs-toggle="tooltip" 
                                       title="WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Create Client Modal -->
<div class="modal fade" id="createClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus"></i> Nuevo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= $basePath ?>/clients/store">
                <div class="modal-body">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" 
                               class="form-control" 
                               id="name" 
                               name="name" 
                               required 
                               autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">TelÃ©fono (opcional)</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone"
                               placeholder="351-1234567">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="3"
                                  placeholder="InformaciÃ³n adicional del cliente..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Crear Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminaciÃ³n</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>&iquest;Est&aacute;s seguro de que deseas eliminar al cliente <strong id="clientName"></strong>?</p>
                <p class="text-muted">Esta acciÃ³n no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <?php echo \App\Core\CSRF::field(); ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteClient(id, name) {
    document.getElementById('clientName').textContent = name;
    const basePath = window.APP_BASE_PATH || '';
    const action = (basePath ? basePath : '') + '/clients/' + id + '/delete';
    document.getElementById('deleteForm').action = action || '/clients/' + id + '/delete';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

