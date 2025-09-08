# AgendaFlow - Sistema de Turnos Web

> **Fluye con tu agenda digital**

Sistema de gestión de turnos web para profesionales y pequeños negocios. Incluye 14 días de prueba gratis y suscripción mensual a $8900 ARS.

## 🎯 Descripción del Proyecto

AgendaFlow es un sistema completo de gestión de citas y turnos desarrollado con arquitectura MVC personalizada en PHP puro. Diseñado específicamente para profesionales independientes (médicos, psicólogos, peluqueros, etc.) y pequeños negocios que necesitan organizar su agenda de manera eficiente. El sistema incluye gestión de clientes, servicios personalizables, reportes financieros y un modelo de suscripción SaaS con integración nativa de MercadoPago.

## 📋 Características Principales

- 📅 **Gestión de Turnos**: Agenda diaria y semanal con vista intuitiva, validación de solapamientos
- 💰 **Control de Ingresos**: Seguimiento de precios por servicio, reportes financieros detallados
- 👥 **Gestión de Clientes**: Base de datos de clientes con historial de citas
- 📊 **Reportes y Analytics**: Visualización de datos con Chart.js, exportación a Excel
- 💳 **Modelo SaaS**: 14 días trial + suscripción mensual con MercadoPago (preapproval)
- 📱 **Responsive Design**: Bootstrap 5, optimizado para móvil/tablet/desktop
- 🔒 **Seguridad Robusta**: CSRF protection, Argon2id/bcrypt, prepared statements, XSS prevention
- 📧 **Notificaciones**: Integración con WhatsApp para recordatorios
- 🔍 **Auditoría**: Registro completo de actividades del sistema

## 🛠️ Stack Tecnológico

### Backend
- **PHP 8.2.12** (mínimo 7.4)
- **MySQL 5.7+** / MariaDB 10.2+
- **Arquitectura MVC** personalizada (sin framework)
- **PDO** para conexiones a base de datos
- **Composer** para autoloading PSR-4

### Frontend
- **Bootstrap 5.3.0** - Framework CSS
- **Bootstrap Icons 1.11.0** - Iconografía
- **Chart.js** - Gráficos y reportes (CDN)
- **JavaScript Vanilla** - Sin jQuery
- **CSS3 Custom Properties** - Temas y colores

### Seguridad
- **Password Hashing**: Argon2id (preferido) o bcrypt fallback
- **CSRF Protection**: Tokens con expiración de 1 hora
- **SQL Injection Prevention**: Prepared statements PDO
- **XSS Protection**: htmlspecialchars() en todas las salidas
- **Session Security**: httpOnly, SameSite=Lax, regeneración de ID

### Requisitos del Sistema
- PHP ≥ 7.4 (testeado en 8.2.12)
- MySQL ≥ 5.7 o MariaDB ≥ 10.2
- Apache con mod_rewrite habilitado
- Extensiones PHP: PDO, pdo_mysql, json, mbstring, openssl

## Instalación

### 1. Clonar o descargar el proyecto

```bash
# Coloca los archivos en tu servidor web (ej: XAMPP)
cd /xampp/htdocs/
# Copia el proyecto a la carpeta AgendaFlow
```

### 2. Configurar la base de datos

```bash
# Copia el archivo de configuración de ejemplo
cp config/config.example.php config/config.php
```

Edita `config/config.php` con tus credenciales de MySQL:

```php
'database' => [
    'host' => 'localhost',
    'dbname' => 'agendaflow',
    'username' => 'tu_usuario',
    'password' => 'tu_contraseña',
],
```

### 3. Ejecutar las migraciones

```bash
# Desde la raíz del proyecto
php migrate.php
```

Esto creará:
- La base de datos `agendaflow`
- Todas las tablas necesarias
- Datos de prueba (usuario demo)

### 4. Configurar Apache

Asegúrate de que mod_rewrite esté habilitado. El archivo `.htaccess` ya está incluido en `/public`.

### 5. Acceder a la aplicación

Abre tu navegador y visita:
```
http://localhost/AgendaFlow/public/
```

## Credenciales de Prueba

**Usuario Demo:**
- Email: `demo@agendaflow.com`
- Contraseña: `password`

Este usuario tiene 14 días de prueba activos y datos de ejemplo.

## 📁 Estructura del Proyecto

```
/AgendaFlow
├── /app                    # Código principal de la aplicación
│   ├── /Controllers       # Controladores MVC (12 archivos)
│   │   ├── AuthController.php         # Login, registro, logout
│   │   ├── DashboardController.php    # Panel principal
│   │   ├── ServiceController.php      # CRUD de servicios
│   │   ├── AppointmentController.php  # Gestión de turnos
│   │   ├── ClientController.php       # Gestión de clientes
│   │   ├── SettingController.php      # Configuración usuario
│   │   ├── SubscriptionController.php # Planes y pagos
│   │   ├── ReportController.php       # Reportes y estadísticas
│   │   └── WebhookController.php      # Webhooks MercadoPago
│   ├── /Models           # Modelos de datos (8 archivos)
│   │   ├── User.php              # Usuarios y trial
│   │   ├── Service.php           # Servicios ofrecidos
│   │   ├── Appointment.php       # Turnos/citas
│   │   ├── Client.php            # Clientes
│   │   ├── Setting.php           # Configuración horarios
│   │   ├── Subscription.php      # Suscripciones
│   │   └── AuditLog.php          # Logs de auditoría
│   ├── /Views            # Vistas HTML/PHP
│   │   ├── /layouts              # Template principal
│   │   ├── /auth                 # Login, register, forgot
│   │   ├── /dashboard            # Panel principal
│   │   ├── /appointments         # Vistas de turnos
│   │   ├── /services             # Vistas de servicios
│   │   ├── /clients              # Vistas de clientes
│   │   ├── /reports              # Vistas de reportes
│   │   ├── /settings             # Vistas configuración
│   │   └── /subscription         # Vistas suscripción
│   └── /Core             # Framework interno (9 archivos)
│       ├── DB.php                # Singleton database
│       ├── Model.php              # Clase base modelos
│       ├── Controller.php         # Clase base controladores
│       ├── View.php               # Motor de plantillas
│       ├── Router.php             # Sistema de rutas
│       ├── CSRF.php               # Protección CSRF
│       ├── Auth.php               # Autenticación
│       ├── Helpers.php            # Funciones auxiliares
│       └── MercadoPago.php        # Integración MP
├── /config                # Configuración
│   ├── config.php                # Config principal
│   └── config.example.php        # Plantilla config
├── /migrations           # Scripts SQL (8 archivos)
│   ├── 001_init.sql              # Tabla users
│   ├── 002_services.sql          # Tabla services
│   ├── 003_clients.sql           # Tabla clients
│   ├── 004_appointments.sql      # Tabla appointments
│   ├── 005_subscription.sql      # Tabla subscriptions
│   ├── 006_settings.sql          # Tabla settings
│   ├── 007_audit.sql             # Tabla audit_logs
│   └── 008_seed_data.sql         # Datos iniciales
├── /public               # Carpeta pública (DocumentRoot)
│   ├── index.php                 # Front controller principal
│   └── .htaccess                 # Rewrite rules Apache
├── /storage              # Almacenamiento
│   └── /logs                     # Logs de aplicación
├── /vendor               # Dependencias
│   └── autoload.php              # PSR-4 autoloader
├── composer.json         # Configuración Composer
├── migrate.php           # Script de migración DB
├── test.php              # Suite de pruebas (84 tests)
├── test-user-flow.php    # Test flujo de usuario
├── install-check.php     # Verificación de instalación
├── .htaccess             # Redirección a /public
└── README.md             # Documentación
```

## Configuración de MercadoPago

Para habilitar pagos con MercadoPago:

1. Obtén tus credenciales en [MercadoPago Developers](https://www.mercadopago.com.ar/developers)
2. Edita `config/config.php`:

```php
'mercadopago' => [
    'access_token' => 'TU_ACCESS_TOKEN',
    'public_key' => 'TU_PUBLIC_KEY',
    'sandbox' => false, // true para pruebas
],
```

## Flujo de Trabajo

### Para Profesionales/Negocios:

1. **Registro**: Crear cuenta con datos del negocio
2. **Configuración**: Definir servicios y horarios
3. **Gestión de Turnos**: Crear y administrar citas
4. **Clientes**: Guardar datos de clientes (opcional)
5. **Reportes**: Ver ingresos y estadísticas
6. **Suscripción**: Activar plan mensual después del trial

### Estados de Turnos:

- `scheduled`: Turno programado (pendiente)
- `completed`: Turno completado
- `canceled`: Turno cancelado
- `no_show`: Cliente no se presentó

## 🔒 Seguridad Implementada

### Autenticación y Autorización
- **Password Hashing**: Argon2id (preferido) o bcrypt como fallback
- **Session Management**: Regeneración de ID en login, cookies httpOnly
- **Remember Me**: Token seguro con random_bytes(32)
- **Protección de Rutas**: Middleware requireAuth(), requireGuest()
- **Validación de Propiedad**: validateOwnership() para recursos

### Protección contra Ataques
- **CSRF Protection**: Tokens únicos por sesión, expiración 1 hora
- **SQL Injection**: 100% prepared statements con PDO
- **XSS Protection**: htmlspecialchars() en todas las salidas de usuario
- **Directory Traversal**: Validación de rutas en Router
- **Class Injection**: Validación de herencia en Router->callHandler()

### Auditoría y Logs
- **Audit Trail**: Registro de todas las acciones críticas
- **Login Attempts**: Log de intentos fallidos con IP/User-Agent
- **Error Logging**: Captura de excepciones en logs estructurados

## 🌐 API Endpoints y Rutas

### Autenticación
- `GET /login` - Formulario de login
- `POST /login` - Procesar login
- `GET /register` - Formulario de registro
- `POST /register` - Crear cuenta con trial 14 días
- `GET /logout` - Cerrar sesión
- `GET /forgot-password` - Recuperar contraseña
- `POST /reset-password` - Resetear contraseña

### Dashboard y Navegación
- `GET /` - Redirige al dashboard
- `GET /dashboard` - Panel principal con métricas

### Gestión de Turnos
- `GET /appointments` - Lista de turnos (vista día/semana)
- `GET /appointments/create` - Formulario nuevo turno
- `POST /appointments/store` - Guardar turno
- `GET /appointments/{id}/edit` - Editar turno
- `POST /appointments/{id}/update` - Actualizar turno
- `POST /appointments/{id}/cancel` - Cancelar turno
- `POST /appointments/{id}/complete` - Marcar completado
- `GET /appointments/{id}/whatsapp` - Enviar recordatorio

### Servicios
- `GET /services` - Lista de servicios
- `GET /services/create` - Crear servicio
- `POST /services/store` - Guardar servicio
- `GET /services/{id}/edit` - Editar servicio
- `POST /services/{id}/update` - Actualizar servicio
- `POST /services/{id}/delete` - Eliminar servicio

### Clientes
- `GET /clients` - Lista de clientes
- `POST /clients/store` - Crear cliente
- `GET /clients/{id}/edit` - Editar cliente
- `POST /clients/{id}/update` - Actualizar cliente
- `POST /clients/{id}/delete` - Eliminar cliente

### Configuración
- `GET /settings` - Configuración general
- `POST /settings/update` - Actualizar configuración
- `POST /settings/hours` - Actualizar horarios

### Suscripción y Pagos
- `GET /subscription` - Estado de suscripción
- `POST /subscription/checkout` - Iniciar pago
- `GET /subscription/success` - Pago exitoso
- `GET /subscription/failure` - Pago fallido
- `POST /subscription/cancel` - Cancelar suscripción

### Reportes
- `GET /reports` - Dashboard de reportes
- `GET /reports/export` - Exportar a Excel

### API AJAX
- `GET /api/appointments/check-overlap` - Verificar solapamiento
- `GET /api/services/{id}/price` - Obtener precio servicio

### Webhooks
- `POST /webhook/mercadopago` - Webhook de MercadoPago

## 🔧 Desarrollo y Extensión

### Arquitectura MVC

#### Agregar un nuevo Controlador:
```php
// app/Controllers/NuevoController.php
namespace App\Controllers;

use App\Core\Controller;

class NuevoController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth(); // Si requiere autenticación
    }
    
    public function index(): void
    {
        $this->render('nuevo/index', ['data' => $data]);
    }
}
```

#### Agregar un Modelo:
```php
// app/Models/NuevoModelo.php
namespace App\Models;

use App\Core\Model;

class NuevoModelo extends Model
{
    protected string $table = 'tabla_nombre';
    protected array $fillable = ['campo1', 'campo2'];
    
    public function customMethod(): array
    {
        return $this->findAll(['user_id' => $userId]);
    }
}
```

#### Crear una Vista:
```php
// app/Views/nuevo/index.php
<?php $title = 'Título Página'; ?>

<div class="container">
    <h1><?= htmlspecialchars($data['title']) ?></h1>
    <!-- Siempre usar htmlspecialchars() para prevenir XSS -->
</div>
```

### Métodos Helper Disponibles

#### En Controladores (heredados de Controller):
- `$this->requireAuth()` - Requiere usuario autenticado
- `$this->requireGuest()` - Solo usuarios no autenticados
- `$this->requireActiveSubscription()` - Requiere suscripción activa
- `$this->validateCSRF()` - Valida token CSRF
- `$this->validate($data, $rules)` - Validación de datos
- `$this->setFlash($type, $message)` - Mensajes flash
- `$this->redirect($url)` - Redirección
- `$this->json($data, $code)` - Respuesta JSON
- `$this->validateOwnership($resource, $redirect)` - Validar propiedad
- `$this->auditLog($action, $entity, $id, $payload)` - Log auditoría

#### En Modelos (heredados de Model):
- `$this->find($id)` - Buscar por ID
- `$this->findAll($conditions)` - Buscar todos con condiciones
- `$this->findBy($field, $value)` - Buscar por campo
- `$this->create($data)` - Crear registro
- `$this->update($id, $data)` - Actualizar registro
- `$this->delete($id)` - Eliminar registro
- `$this->count($conditions)` - Contar registros
- `$this->exists($field, $value)` - Verificar existencia

### Funciones Helper Globales (Helpers.php):
- `formatPrice($amount)` - Formato moneda ARS
- `formatDate($date, $format)` - Formato fecha
- `formatDateTime($datetime)` - Formato fecha/hora
- `getTrialDaysRemaining($trialEndsAt)` - Días trial restantes
- `isTrialExpired($trialEndsAt)` - Verificar trial expirado
- `generateWhatsAppLink($phone, $message)` - Link WhatsApp
- `sanitizePhone($phone)` - Limpiar teléfono
- `getTimeSlots($start, $end, $interval)` - Generar slots horarios

## 📊 Base de Datos

### Esquema Principal
```sql
-- Tablas principales y sus relaciones:
users (id) <--- 1:N ---> appointments (user_id)
users (id) <--- 1:N ---> services (user_id)
users (id) <--- 1:N ---> clients (user_id)
users (id) <--- 1:N ---> settings (user_id)
users (id) <--- 1:N ---> subscriptions (user_id)
services (id) <--- N:1 ---> appointments (service_id)
clients (id) <--- N:1 ---> appointments (client_id)
```

### Índices Optimizados
- `idx_user_starts` en appointments para queries de calendario
- `idx_subscription_status` en users para verificación rápida
- `idx_trial_ends` en users para alertas de expiración
- Índices compuestos para JOINs frecuentes

## 🧪 Testing

El proyecto incluye una suite completa de pruebas (`test.php`):

```bash
# Ejecutar todas las pruebas
php test.php

# Tests incluidos (84 total):
- Configuración y ambiente
- Conexión a base de datos
- Componentes Core (Router, Auth, CSRF)
- Modelos y relaciones
- Controladores y herencia
- Seguridad (hashing, XSS, CSRF)
- Lógica de negocio
- Vistas y templates
```

Estado actual: **100% tests pasando** ✅

## 🐛 Bugs Conocidos y Soluciones Recientes

### Corregidos recientemente:
1. ✅ **CSRF.php**: Headers already sent - Agregada verificación `headers_sent()`
2. ✅ **User.php**: Cálculo incorrecto de días trial - Removido +1 día extra
3. ✅ **AppointmentController**: Falta validación de propiedad de servicios
4. ✅ **Router.php**: Vulnerabilidad de inyección de clases - Validación de herencia
5. ✅ **ServiceController**: Código redundante - Implementado `validateOwnership()`

### Pendientes (baja prioridad):
- Warning de session_start() en CLI (no afecta producción)
- Optimización de queries N+1 en reportes

## 🚀 Optimizaciones de Performance

- **Database**: Singleton pattern para conexión única
- **Autoloading**: PSR-4 con Composer (sin require manual)
- **Views**: Cache de templates compilados (próxima versión)
- **Sessions**: Lazy loading, solo cuando se necesitan
- **Queries**: Prepared statements reutilizables

## 💡 Mejores Prácticas del Proyecto

1. **Nunca** exponer `config.php` en repositorio
2. **Siempre** usar `htmlspecialchars()` en vistas
3. **Validar** ownership antes de editar/eliminar
4. **Registrar** acciones críticas en audit_logs
5. **Sanitizar** entrada de usuario antes de procesar
6. **Verificar** suscripción activa para features premium

## 🔄 Flujo de Datos

```
Usuario -> Router -> Controller -> Model -> Database
                 |                    |
                 v                    v
              View <-- Controller <-- Model
                 |
                 v
            Response -> Usuario
```

## 📝 Convenciones de Código

- **PSR-4**: Autoloading estándar
- **PSR-12**: Estilo de código
- **Naming**: camelCase métodos, snake_case DB, PascalCase clases
- **Comments**: En español para lógica de negocio, inglés para código técnico

## Solución de Problemas

### Error de conexión a base de datos:
```bash
# Verificar MySQL está corriendo
mysql -u root -p -e "SHOW DATABASES;"
# Verificar permisos
mysql -u root -p -e "SHOW GRANTS FOR 'tu_usuario'@'localhost';"
```

### Página no encontrada (404):
```bash
# Habilitar mod_rewrite en Apache
a2enmod rewrite
# Reiniciar Apache
service apache2 restart
```

### Error de permisos:
```bash
# Linux/Mac
chmod -R 755 storage/
chown -R www-data:www-data storage/
# Windows (como admin)
icacls storage /grant Everyone:F /T
```

### Session warnings en tests:
```bash
# Ejecutar tests sin output buffering
php -d output_buffering=0 test.php
```

## 🤝 Contribuciones

Para contribuir al proyecto:

1. Fork el repositorio
2. Crea una rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📧 Soporte

- **Email**: soporte@agendaflow.com
- **Issues**: GitHub Issues
- **Docs**: Este README.md

## 📄 Licencia

© 2025 AgendaFlow - Software Propietario. Todos los derechos reservados.

---

**Desarrollado con ❤️ para profesionales que valoran su tiempo**

*Última actualización: Enero 2025 - v1.0.0*