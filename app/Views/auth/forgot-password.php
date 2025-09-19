<?php
$title = 'Recuperar Contrase&ntilde;a - AgendaFlow';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <h1 class="h3 text-primary fw-bold">
                <i class="bi bi-calendar-check"></i> AgendaFlow
            </h1>
            <p class="text-muted">Fluye con tu agenda digital</p>
        </div>
        
        <div class="card">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">Recuperar Contrase&ntilde;a</h2>
                
                <p class="text-muted mb-4">
                    Ingresa tu email y te enviaremos instrucciones para restablecer tu contrase&ntilde;a.
                </p>
                
                <form method="POST" action="<?= $basePath ?>/forgot-password">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control <?php echo isset($_SESSION['errors']['email']) ? 'is-invalid' : ''; ?>" 
                               id="email" 
                               name="email" 
                               value="<?php echo $_SESSION['old']['email'] ?? ''; ?>"
                               required 
                               autofocus>
                        <?php if (isset($_SESSION['errors']['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['email']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-envelope"></i> Enviar Instrucciones
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p>
                        &iquest;Recordaste tu contrase&ntilde;a? 
                        <a href="<?= $basePath ?>/login" class="text-decoration-none">
                            Volver a iniciar sesi&oacute;n
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Clear old input and errors
unset($_SESSION['old']);
unset($_SESSION['errors']);
?>