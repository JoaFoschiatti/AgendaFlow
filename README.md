# AgendaFlow

Sistema web para la gestiÃ³n integral de turnos orientado a profesionales independientes y pequeÃ±os negocios. Incluye autenticaciÃ³n segura, gestiÃ³n de clientes y servicios, reportes operativos y un modelo de suscripciÃ³n tipo SaaS.

## Tabla de Contenidos
- [VisiÃ³n General](#visiÃ³n-general)
- [Arquitectura TÃ©cnica](#arquitectura-tÃ©cnica)
- [Requisitos](#requisitos)
- [InstalaciÃ³n y ConfiguraciÃ³n](#instalaciÃ³n-y-configuraciÃ³n)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Scripts y Tareas Comunes](#scripts-y-tareas-comunes)
- [API REST](#api-rest)
- [Seguridad y Cumplimiento](#seguridad-y-cumplimiento)
- [Modelo SaaS y Roadmap](#modelo-saas-y-roadmap)
- [Testing y Calidad](#testing-y-calidad)
- [SoluciÃ³n de Problemas](#soluciÃ³n-de-problemas)
- [Soporte y Licencia](#soporte-y-licencia)

## VisiÃ³n General
AgendaFlow ofrece un flujo completo para administrar agendas, clientes y cobros recurrentes. Las funciones principales incluyen:
- Agenda diaria y semanal con validaciÃ³n de solapamientos.
- GestiÃ³n de clientes con historial de turnos y notas.
- CatÃ¡logo de servicios con precios, duraciÃ³n y estados.
- Reportes de ingresos y prÃ³ximos turnos, con exportaciÃ³n bÃ¡sica.
- IntegraciÃ³n con MercadoPago para suscripciones recurrentes.
- API REST autenticada con JWT para integraciones externas.

## Arquitectura TÃ©cnica
**Backend**
- PHP 8.2 (compatible con >= 7.4).
- Motor MVC propio (`app/Core`, `app/Controllers`, `app/Models`).
- PDO para acceso a MySQL/MariaDB.
- Dependencias principales vÃ­a Composer: `firebase/php-jwt`, `mercadopago/dx-php`.

**Frontend**
- Bootstrap 5.3 y Bootstrap Icons.
- CSS responsive personalizado (`public/css/responsive.css`).
- JavaScript vanilla para interacciones puntuales.

**Infraestructura**
- Arquitectura monolÃ­tica + MySQL.
- ConfiguraciÃ³n cargada desde `config/config.php` (o `config.example.php` en desarrollo).
- Router propio con soporte para rutas nombradas y controladores PSR-4.

## Requisitos
- PHP 7.4 o superior (recomendado 8.2+).
- Extensiones PHP: `pdo_mysql`, `json`, `mbstring`, `openssl`.
- MySQL 5.7+ o MariaDB 10.2+.
- Servidor web con `mod_rewrite` (Apache) o reglas equivalentes.
- Composer para gestionar dependencias.

## InstalaciÃ³n y ConfiguraciÃ³n
1. **Clonar o copiar el proyecto**
   ```bash
   git clone https://github.com/<usuario>/AgendaFlow.git
   cd AgendaFlow
   composer install
   ```

2. **ConfiguraciÃ³n de entorno**
   ```bash
   cp config/config.example.php config/config.php
   ```
   Ajusta credenciales de base de datos, URL pÃºblica y claves de MercadoPago antes de desplegar en producciÃ³n.

3. **Migraciones de base de datos**
   ```bash
   php migrate.php
   ```
   Crea el esquema completo y datos de ejemplo (usuario: `demo@agendaflow.com`, password: `password`).

4. **Servidor web**
   Apunta el document root a `public/`. El router ya contempla subdirectorios gracias a la clase `App\Core\Url`.

5. **ConfiguraciÃ³n API**
   JWT y CORS se administran desde `config/api.config.php`. MantÃ©n la `secret_key` fuera del repositorio pÃºblico.

## Estructura del Proyecto
```
app/
  Controllers/        Controladores web y API
  Core/               Infraestructura del framework (Router, View, DB, Auth, etc.)
  Models/             Acceso a datos y lÃ³gica de negocio
  Routing/            Registro centralizado de rutas
  Views/              Vistas PHP
bootstrap/
  init.php            Punto Ãºnico de arranque (autoload, config, sesiones)
config/                Configuraciones de aplicaciÃ³n y API
migrations/           Scripts SQL versionados
public/                Punto de entrada web (`index.php`, `api.php`, assets)
storage/               Logs, archivos temporales (mantener fuera de VCS)
tests/                Suite de pruebas unitarias ligeras
```

## Scripts y Tareas Comunes
- `php migrate.php`: ejecuta todas las migraciones SQL.
- `php tests/run.php`: corre la baterÃ­a de pruebas incluidas.
- `php -S localhost:8000 -t public/`: servidor embebido para desarrollo rÃ¡pido.
- `composer install --no-dev --optimize-autoloader`: preparar despliegue.

Los entrypoints (`public/index.php` y `public/api.php`) cargan `bootstrap/init.php`, que resuelve autoload, configuraciÃ³n y sesiÃ³n de manera consistente.

## API REST
- AutenticaciÃ³n: JWT Bearer (`Authorization: Bearer <token>`).
- Prefijo por defecto: `/api/v1` (tambiÃ©n se registran rutas espejo en `/api`).
- Rate limiting y CORS gestionados en `App\Core\ApiController` y `App\Core\RateLimiter`.

**Endpoints principales**
- `POST /api/v1/auth/login | register | refresh`
- `GET /api/v1/auth/me`
- `GET/POST/PUT/PATCH/DELETE /api/v1/services`
- `GET/POST/PUT/PATCH/DELETE /api/v1/clients`
- `GET/POST/PUT/PATCH/DELETE /api/v1/appointments`
- `GET /api/v1/services/{id}/stats` (alias de `/statistics`).

**Ejemplo de solicitud**
```bash
curl -X POST http://localhost/AgendaFlow/public/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@agendaflow.com","password":"password"}'
```

La respuesta incluye `access_token`, `refresh_token`, metadatos de expiraciÃ³n y datos bÃ¡sicos del usuario.

## Seguridad y Cumplimiento
- Hash de contraseÃ±as con Argon2id (fallback a bcrypt).
- ProtecciÃ³n CSRF automÃ¡tica en formularios (`App\Core\CSRF`).
- Rate limiter configurable por IP para endpoints sensibles.
- SanitizaciÃ³n y validaciones bÃ¡sicas en controladores y modelos.
- ConfiguraciÃ³n de sesiÃ³n endurecida (`httponly`, `SameSite`, soporte HTTPS).

Recomendaciones adicionales:
1. Externalizar credenciales y claves en variables de entorno.
2. Habilitar HTTPS y forzar `secure` en cookies en producciÃ³n.
3. Implementar doble factor y registros de auditorÃ­a extendidos para cumplir normativas (GDPR/LGPD).

## Modelo SaaS y Roadmap
**Estado actual**
- Trial de 14 dÃ­as (`users.trial_ends_at`).
- Estados de suscripciÃ³n: `trialing`, `active`, `past_due`, `canceled`.
- IntegraciÃ³n MercadoPago (preapproval).

**Prioridades sugeridas (extraÃ­das del anÃ¡lisis SaaS):**
1. API pÃºblica mÃ¡s robusta (documentaciÃ³n OpenAPI, claves por cliente, webhooks).
2. Notificaciones automÃ¡ticas (email/SMS/WhatsApp) para reducir ausencias.
3. Multi-tenant avanzado (subdominios, aislaciÃ³n de datos, personalizaciÃ³n).
4. Planes escalonados con lÃ­mites y mÃ©tricas de uso.
5. Onboarding guiado y plantillas por industria.

## Testing y Calidad
- Suite ligera en `tests/` (Config, Helpers, URL) ejecutable con `php tests/run.php`.
- Archivo `test-system-complete.php` disponible para pruebas manuales end-to-end.
- Se recomienda integrar PHPUnit o Pest y ampliar cobertura (modelos, controladores, API) antes de escalar el proyecto.

## SoluciÃ³n de Problemas
- **ConexiÃ³n MySQL**: confirma que el servicio estÃ¡ activo y credenciales correctas (`mysql -u root -p`).
- **Error 404**: verifica que `mod_rewrite` estÃ© habilitado y `.htaccess` se estÃ© leyendo.
- **Permisos de escritura**: la carpeta `storage/` debe ser escribible por el servidor web.
- **Sesiones en CLI**: al ejecutar scripts, `bootstrap/init.php` evita `session_start` cuando `PHP_SAPI === 'cli'`.

## Soporte y Licencia
- Contacto: soporte@agendaflow.com
- Issues y mejoras: abrir tickets en el repositorio.
- Licencia: Software propietario, Â© 2025 AgendaFlow. Todos los derechos reservados.
