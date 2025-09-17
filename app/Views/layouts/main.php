<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'AgendaFlow - Fluye con tu agenda digital'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Responsive CSS -->
    <link rel="stylesheet" href="<?= $basePath ?>/css/responsive.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --af-primary: #4F46E5;
            --af-primary-dark: #4338CA;
            --af-success: #10B981;
            --af-danger: #EF4444;
            --af-warning: #F59E0B;
            --af-info: #3B82F6;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #F9FAFB;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--af-primary) !important;
        }
        
        .btn-primary {
            background-color: var(--af-primary);
            border-color: var(--af-primary);
        }
        
        .btn-primary:hover {
            background-color: var(--af-primary-dark);
            border-color: var(--af-primary-dark);
        }
        
        .card {
            border: none;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .navbar {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        
        .badge-trial {
            background-color: var(--af-warning);
        }
        
        .badge-active {
            background-color: var(--af-success);
        }
        
        .trial-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .fab-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--af-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .fab-button:hover {
            background-color: var(--af-primary-dark);
            transform: scale(1.1);
        }
    </style>
    
    <!-- Chart.js for Reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php if (isset($user) && $user): ?>
    <!-- Navbar for authenticated users -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $basePath ?>/dashboard">
                <i class="bi bi-calendar-check"></i> AgendaFlow
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/dashboard">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/appointments">
                            <i class="bi bi-calendar-week"></i> Agenda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/services">
                            <i class="bi bi-scissors"></i> Servicios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/clients">
                            <i class="bi bi-people"></i> Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/reports">
                            <i class="bi bi-graph-up"></i> Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/settings">
                            <i class="bi bi-gear"></i> Configuración
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($user['subscription_status'] === 'trialing'): ?>
                        <?php 
                        $daysRemaining = \App\Core\Helpers::getTrialDaysRemaining($user['trial_ends_at']);
                        ?>
                        <li class="nav-item me-3">
                            <a class="nav-link" href="<?= $basePath ?>/subscription">
                                <span class="badge badge-trial">
                                    <i class="bi bi-clock"></i> <?php echo $daysRemaining; ?> días de prueba
                                </span>
                            </a>
                        </li>
                    <?php elseif ($user['subscription_status'] === 'active'): ?>
                        <li class="nav-item me-3">
                            <a class="nav-link" href="<?= $basePath ?>/subscription">
                                <span class="badge badge-active">
                                    <i class="bi bi-check-circle"></i> Suscripción activa
                                </span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item me-3">
                            <a class="nav-link text-danger" href="<?= $basePath ?>/subscription">
                                <i class="bi bi-exclamation-triangle"></i> Activar suscripción
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?>/settings">
                                    <i class="bi bi-gear"></i> Mi cuenta
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?>/subscription">
                                    <i class="bi bi-credit-card"></i> Suscripción
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?>/logout">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Trial/Subscription Banner -->
    <?php if ($user['subscription_status'] === 'trialing'): ?>
        <?php if ($daysRemaining <= 3): ?>
        <div class="trial-banner py-2 text-center">
            <small>
                <i class="bi bi-exclamation-circle"></i>
                Tu prueba gratis termina en <?php echo $daysRemaining; ?> días.
                <a href="<?= $basePath ?>/subscription" class="text-white fw-bold">Activar suscripción →</a>
            </small>
        </div>
        <?php endif; ?>
    <?php elseif ($user['subscription_status'] !== 'active'): ?>
        <div class="bg-danger text-white py-2 text-center">
            <small>
                <i class="bi bi-lock"></i>
                Tu suscripción ha vencido. Solo puedes ver tus datos.
                <a href="<?= $basePath ?>/subscription" class="text-white fw-bold">Reactivar ahora →</a>
            </small>
        </div>
    <?php endif; ?>
    <?php endif; ?>
    
    <main class="py-4">
        <div class="container">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $_SESSION['flash_success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['flash_error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash_warning'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['flash_warning']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_warning']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash_info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle"></i> <?php echo $_SESSION['flash_info']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_info']); ?>
            <?php endif; ?>
            
            <!-- Main Content -->
            <?php echo $content; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="text-center text-muted py-4 mt-5">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> AgendaFlow - Fluye con tu agenda digital
            </p>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>