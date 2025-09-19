<?php
$title = 'Iniciar Sesi&oacute;n - AgendaFlow';
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
                <h2 class="h5 mb-4">Iniciar Sesi&oacute;n</h2>
                
                <form method="POST" action="<?= $basePath ?>/login">
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
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contrase&ntilde;a</label>
                        <input type="password" 
                               class="form-control <?php echo isset($_SESSION['errors']['password']) ? 'is-invalid' : ''; ?>" 
                               id="password" 
                               name="password" 
                               required>
                        <?php if (isset($_SESSION['errors']['password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Recordarme
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Ingresar
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="mb-2">
                        <a href="<?= $basePath ?>/forgot-password" class="text-decoration-none">
                            &iquest;Olvidaste tu contrase&ntilde;a?
                        </a>
                    </p>
                    <p>
                        &iquest;No tienes cuenta? 
                        <a href="<?= $basePath ?>/register" class="text-decoration-none fw-bold">
                            Reg&iacute;strate gratis
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                Al iniciar sesi&oacute;n, aceptas nuestros 
                <a href="#" class="text-decoration-none">T&eacute;rminos y Condiciones</a>
            </small>
        </div>
    </div>
</div>

<?php
// Clear old input and errors
unset($_SESSION['old']);
unset($_SESSION['errors']);
?>