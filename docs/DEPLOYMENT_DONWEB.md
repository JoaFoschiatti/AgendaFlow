# Despliegue en DonWeb

Guia rapida para publicar AgendaFlow en un hosting compartido de DonWeb.

## 1. Preparar entorno local
- Ejecuta `composer install` para asegurarte de que `vendor/` esta completo.
- Copia `config/config.example.php` a `config/config.php` y reemplaza los valores con tus credenciales reales.
- Ajusta `config/api.config.php` con un `secret_key` largo y origenes CORS permitidos.
- Coloca `debug => false` y `session.secure => true` si usaras HTTPS.
- Corre `php tests/run.php` y realiza un smoke test manual.

## 2. Crear base de datos
1. Ingresa al dPanel de DonWeb y crea una base MySQL + usuario.
2. Apunta el nombre exacto de la base, usuario, host y clave (ej. `panel123_agen`, `mysql.panel123.donweb.com`).
3. Edita las migraciones SQL y elimina las lineas `CREATE DATABASE` y `USE agendaflow;` si usaras phpMyAdmin.

## 3. Subir archivos
- Comprime el proyecto completo SIN el directorio `.git` y subelo via SFTP o Administrador de Archivos.
- Asegurate de incluir `vendor/` y la carpeta `storage/` con sus subcarpetas.
- En dPanel > Dominios, apunta el document root a la carpeta `public/`. Si no esta permitido, copia el contenido de `public/` a la raiz publica y ajusta `config/config.php` y `.htaccess`.

## 4. Configurar permisos
- Marca `storage/`, `storage/logs/` y eventuales carpetas de uploads con permisos 755 (o 775 si el servidor lo requiere).
- Crea un archivo vacio `storage/logs/php-error.log` si se necesita el log.

## 5. Ejecutar migraciones
- Si tienes acceso SSH, corre `php migrate.php`.
- Caso contrario, importa de forma ordenada cada archivo de `migrations/` mediante phpMyAdmin.
- El usuario demo (`demo@agendaflow.com` / `password`) se crea con las semillas. Eliminalo si no lo necesitas.

## 6. Verificacion final
- Ingresa al dominio y prueba login, alta de cliente, creacion de turno y facturacion.
- Testea endpoints API (`/api/v1/...`) con la clave JWT configurada.
- Configura HTTPS (Lets Encrypt en DonWeb) y prueba redireccion forzada a `https` en `.htaccess`.

## 7. Integracion MercadoPago
- Cambia `mercadopago.sandbox` a `false` cuando pases a produccion.
- Coloca las credenciales productivas (Access Token y Public Key) en `config/config.php`.
- Genera un `webhook_secret` en MercadoPago y actualiza `config/config.php`. Asegurate de replicarlo en la seccion de Webhooks del panel MP.
- Revisa `storage/logs/php-error.log` despues de las primeras notificaciones.

## 8. Mantenimiento
- Programa respaldos de la base en dPanel o descarga dumps periodicamente.
- Limpia `storage/rate_limits.json` si crece demasiado.
- Documenta cambios en tus despliegues y considera automatizar un zip con fecha para futuros releases.
