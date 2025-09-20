<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo $title ?? 'AgendaFlow - Fluye con tu agenda digital'; ?></title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4F46E5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="AgendaFlow">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= $basePath ?>/manifest.json">

    <!-- iOS Icons -->
    <link rel="apple-touch-icon" href="<?= $basePath ?>/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $basePath ?>/icons/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $basePath ?>/icons/icon-72x72.png">
    
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
                    <!-- PWA Install Button -->
                    <li class="nav-item me-2" id="pwa-install-nav" style="display:none;">
                        <button class="btn btn-sm btn-success" onclick="installPWA()">
                            <i class="bi bi-download"></i> Instalar App
                        </button>
                    </li>

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
    
    <script>
        window.APP_BASE_PATH = <?php echo json_encode($basePath ?? ''); ?>;
    </script>

    <!-- Custom JS -->
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // PWA Installation
        let deferredPrompt;
        const basePath = window.APP_BASE_PATH || '';

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register(basePath + '/service-worker.js')
                    .then(registration => {
                        console.log('Service Worker registered:', registration);

                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'activated' && navigator.serviceWorker.controller) {
                                    console.log('New Service Worker activated');
                                }
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Service Worker registration failed:', error);
                    });
            });
        }

        // PWA Install Prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('beforeinstallprompt event fired');
            e.preventDefault();
            deferredPrompt = e;

            // Show install button in navbar
            const navInstallBtn = document.getElementById('pwa-install-nav');
            if (navInstallBtn) {
                navInstallBtn.style.display = 'block';
            }

            // Show install banner
            showInstallPromotion();
        });

        function showInstallPromotion() {
            // Only show if not already installed and not dismissed today
            const lastDismissed = localStorage.getItem('pwaBannerDismissed');
            const today = new Date().toDateString();

            if (lastDismissed === today) {
                return;
            }

            // Create install banner if not exists
            if (!document.getElementById('pwa-install-banner')) {
                const banner = document.createElement('div');
                banner.id = 'pwa-install-banner';
                banner.className = 'alert alert-info alert-dismissible fade show position-fixed bottom-0 start-50 translate-middle-x mb-3';
                banner.style.zIndex = '9999';
                banner.style.maxWidth = '500px';
                banner.style.width = '90%';
                banner.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <strong>📱 Instalar AgendaFlow</strong>
                            <p class="mb-0 small">Accede más rápido desde tu dispositivo</p>
                        </div>
                        <div class="ms-3">
                            <button onclick="installPWA()" class="btn btn-sm btn-primary">
                                <i class="bi bi-download"></i> Instalar
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                            onclick="dismissInstallBanner()" aria-label="Close"></button>
                `;
                document.body.appendChild(banner);

                // Auto hide after 30 seconds
                setTimeout(() => {
                    const banner = document.getElementById('pwa-install-banner');
                    if (banner) {
                        banner.classList.add('fade');
                        setTimeout(() => banner.remove(), 300);
                    }
                }, 30000);
            }
        }

        function dismissInstallBanner() {
            const banner = document.getElementById('pwa-install-banner');
            if (banner) {
                banner.remove();
                // Save dismissal for today
                localStorage.setItem('pwaBannerDismissed', new Date().toDateString());
            }
        }

        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;

                    // Hide install banner
                    const banner = document.getElementById('pwa-install-banner');
                    if (banner) {
                        banner.remove();
                    }
                });
            }
        }

        // Check if app is installed
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');

            // Hide install banner if exists
            const banner = document.getElementById('pwa-install-banner');
            if (banner) {
                banner.remove();
            }
        });

        // iOS install instructions
        if (navigator.standalone === false && /iPhone|iPad|iPod/.test(navigator.userAgent)) {
            // Show iOS install instructions
            setTimeout(() => {
                if (!localStorage.getItem('iosInstallShown')) {
                    const iosModal = document.createElement('div');
                    iosModal.className = 'modal fade';
                    iosModal.id = 'iosInstallModal';
                    iosModal.innerHTML = `
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">📱 Instalar AgendaFlow</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Para instalar AgendaFlow en tu iPhone/iPad:</p>
                                    <ol>
                                        <li>Toca el botón compartir <i class="bi bi-share"></i></li>
                                        <li>Selecciona "Añadir a pantalla de inicio"</li>
                                        <li>Toca "Añadir"</li>
                                    </ol>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(iosModal);
                    new bootstrap.Modal(document.getElementById('iosInstallModal')).show();
                    localStorage.setItem('iosInstallShown', 'true');
                }
            }, 3000);
        }
    </script>
</body>
</html>
