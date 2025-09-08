<?php
$title = 'Crear Cuenta - AgendaFlow';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h1 class="h3 text-primary fw-bold">
                <i class="bi bi-calendar-check"></i> AgendaFlow
            </h1>
            <p class="text-muted">Fluye con tu agenda digital</p>
        </div>
        
        <div class="card">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Crear Cuenta</h2>
                
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-gift"></i> <strong>14 días de prueba gratis</strong>
                    <br>
                    <small>Sin tarjeta de crédito. Sin compromisos.</small>
                </div>
                
                <form method="POST" action="/AgendaFlow/public/register">
                    <?php echo \App\Core\CSRF::field(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Tu nombre *</label>
                        <input type="text" 
                               class="form-control <?php echo isset($_SESSION['errors']['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" 
                               name="name" 
                               value="<?php echo $_SESSION['old']['name'] ?? ''; ?>"
                               required 
                               autofocus>
                        <?php if (isset($_SESSION['errors']['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="business_name" class="form-label">Nombre del negocio *</label>
                        <input type="text" 
                               class="form-control <?php echo isset($_SESSION['errors']['business_name']) ? 'is-invalid' : ''; ?>" 
                               id="business_name" 
                               name="business_name" 
                               value="<?php echo $_SESSION['old']['business_name'] ?? ''; ?>"
                               placeholder="Ej: Barbería El Estilo"
                               required>
                        <?php if (isset($_SESSION['errors']['business_name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['business_name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" 
                               class="form-control <?php echo isset($_SESSION['errors']['email']) ? 'is-invalid' : ''; ?>" 
                               id="email" 
                               name="email" 
                               value="<?php echo $_SESSION['old']['email'] ?? ''; ?>"
                               required>
                        <?php if (isset($_SESSION['errors']['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['email']; ?>
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
                               placeholder="351-1234567">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña *</label>
                        <input type="password" 
                               class="form-control <?php echo isset($_SESSION['errors']['password']) ? 'is-invalid' : ''; ?>" 
                               id="password" 
                               name="password" 
                               required
                               minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres</small>
                        <?php if (isset($_SESSION['errors']['password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña *</label>
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
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" 
                               class="form-check-input <?php echo isset($_SESSION['errors']['terms']) ? 'is-invalid' : ''; ?>" 
                               id="terms" 
                               name="terms" 
                               required>
                        <label class="form-check-label" for="terms">
                            Acepto los <a href="#" target="_blank">Términos y Condiciones</a> *
                        </label>
                        <?php if (isset($_SESSION['errors']['terms'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $_SESSION['errors']['terms']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> Crear cuenta gratis
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p>
                        ¿Ya tienes cuenta? 
                        <a href="/AgendaFlow/public/login" class="text-decoration-none fw-bold">
                            Iniciar sesión
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="row text-center">
                <div class="col-4">
                    <i class="bi bi-shield-check text-primary fs-3"></i>
                    <p class="small mt-2">Datos seguros</p>
                </div>
                <div class="col-4">
                    <i class="bi bi-clock text-primary fs-3"></i>
                    <p class="small mt-2">14 días gratis</p>
                </div>
                <div class="col-4">
                    <i class="bi bi-x-circle text-primary fs-3"></i>
                    <p class="small mt-2">Cancela cuando quieras</p>
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