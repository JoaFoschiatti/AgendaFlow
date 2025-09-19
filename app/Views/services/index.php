<?php
$title = 'Servicios - AgendaFlow';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-scissors"></i> Servicios
        </h1>
        <p class="text-muted">Gestiona los servicios que ofreces</p>
    </div>
    <div class="col-auto">
        <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
        <a href="<?= $basePath ?>/services/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Servicio
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($services)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-scissors text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3">No hay servicios creados</h5>
            <p class="text-muted">Comienza creando tu primer servicio</p>
            <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
            <a href="<?= $basePath ?>/services/create" class="btn btn-primary mt-3">
                <i class="bi bi-plus-circle"></i> Crear primer servicio
            </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Precio</th>
                            <th>Duración</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: <?php echo $service['color'] ?? '#6c757d'; ?>; border-radius: 4px;"></div>
                                    <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                                </div>
                            </td>
                            <td>
                                <?php echo \App\Core\Helpers::formatPrice($service['price_default'] ?? 0); ?>
                            </td>
                            <td>
                                <?php if ($service['duration_min']): ?>
                                    <?php echo $service['duration_min']; ?> min
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($service['is_active']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= $basePath ?>/appointments/create?service_id=<?php echo $service['id']; ?>"
                                   class="btn btn-sm btn-success"
                                   data-bs-toggle="tooltip"
                                   title="Crear turno con este servicio">
                                    <i class="bi bi-calendar-plus"></i>
                                </a>
                                <a href="<?= $basePath ?>/services/<?php echo $service['id']; ?>/edit"
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($user && \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']) > 0 || $user['subscription_status'] === 'active'): ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name']); ?>')"
                                        data-bs-toggle="tooltip"
                                        title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
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

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>&iquest;Est&aacute;s seguro de que deseas eliminar el servicio <strong id="serviceName"></strong>?</p>
                <p class="text-muted">Si el servicio tiene turnos asociados, ser&aacute; desactivado en lugar de eliminado.</p>
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
function deleteService(id, name) {
    document.getElementById('serviceName').textContent = name;
    const basePath = window.APP_BASE_PATH || '';
    const action = (basePath ? basePath : '') + '/services/' + id + '/delete';
    document.getElementById('deleteForm').action = action || '/services/' + id + '/delete';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>





