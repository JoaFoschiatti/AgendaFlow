<?php
$title = 'Restablecer Contraseña - AgendaFlow';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <h1 class="h3 text-primary fw-bold">
                <i class="bi bi-shield-lock"></i> Restablecer Contraseña
            </h1>
            <p class="text-muted">Ingresa una nueva contraseña segura</p>
        </div>

        <div class="card">
            <div class="card-body p-4">

                <form method="POST" action="<?= $basePath ?>/reset-password">

                    <?php echo \App\Core\CSRF::field(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password"
                               class="form-control <?php echo isset($_SESSION['errors']['password']) ? 'is-invalid' : ''; ?>"
                               id="password"
                               name="password"
                               required
                               autofocus>
                        <?php if (isset($_SESSION['errors']['password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input type="password"
                               class="form-control <?php echo isset($_SESSION['errors']['password_confirmation']) ? 'is-invalid' : ''; ?>"
                               id="password_confirmation"
                               name="password_confirmation"
                               required>
                        <?php if (isset($_SESSION['errors']['password_confirmation'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['password_confirmation']; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-repeat"></i> Restablecer contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-4">

            <a href="<?= $basePath ?>/login" class="text-decoration-none">

                <i class="bi bi-box-arrow-in-right"></i> Volver al inicio de sesión
            </a>
        </div>
    </div>
</div>

<?php
unset($_SESSION['errors']);
?>
