# Configuración de MercadoPago para AgendaFlow

## 🚀 Pasos para Configurar MercadoPago

### 1. Instalar Dependencias

Primero, instala el SDK de MercadoPago ejecutando:

```bash
composer install
```

o si no tienes las dependencias instaladas:

```bash
composer update
```

### 2. Obtener Credenciales de MercadoPago

#### Credenciales de Prueba (Sandbox)

1. Ingresa a tu cuenta de MercadoPago Developers: https://www.mercadopago.com.ar/developers/panel
2. Ve a "Tus integraciones" → "Crear aplicación"
3. Dale un nombre a tu aplicación (ej: "AgendaFlow")
4. En la configuración de la aplicación, ve a "Credenciales de prueba"
5. Copia las siguientes credenciales:
   - **Access Token de Prueba**
   - **Public Key de Prueba**

#### Credenciales de Producción

1. En la misma aplicación, ve a "Credenciales de producción"
2. Completa el formulario de activación si es necesario
3. Copia las credenciales de producción cuando estés listo

### 3. Configurar las Credenciales

Edita el archivo `config/config.php` y actualiza la sección de MercadoPago:

```php
'mercadopago' => [
    'access_token' => 'TEST-xxxx-xxxx-xxxx', // Tu Access Token
    'public_key' => 'TEST-xxxx-xxxx-xxxx',   // Tu Public Key
    'sandbox' => true,  // true para pruebas, false para producción
    'webhook_secret' => '', // Opcional, para validar webhooks
],
```

### 4. Configurar Webhook (Opcional pero Recomendado)

1. En tu aplicación de MercadoPago, ve a "Webhooks"
2. Agrega una nueva URL de notificación:
   - **URL**: `https://tudominio.com/AgendaFlow/public/webhook/payment`
   - **Eventos**: Selecciona "Payment"
3. Guarda el webhook secret si lo configuras

### 5. Probar la Integración

#### Tarjetas de Prueba para Argentina

**Tarjetas Aprobadas:**
- Mastercard: `5031 7557 3453 0604`
- Visa: `4509 9535 6623 3704`
- American Express: `3711 803032 57522`

**Datos de Prueba:**
- Titular: `APRO` (para aprobar) o `OTHE` (para rechazar)
- DNI: `12345678`
- Email: `test@test.com`
- CVV: `123`
- Vencimiento: `11/25`

#### Flujo de Prueba:

1. Ingresa a tu cuenta en AgendaFlow
2. Ve a "Suscripción"
3. Haz clic en "Activar Suscripción"
4. Serás redirigido al checkout de MercadoPago
5. Usa una tarjeta de prueba
6. Completa el pago
7. Serás redirigido de vuelta a AgendaFlow
8. Verifica que tu suscripción esté activa

### 6. Verificar Logs

Si algo no funciona, revisa los logs:

1. **Logs de PHP**: En `C:/xampp/apache/logs/error.log`
2. **Logs de la aplicación**: En `storage/logs/`
3. **Console del navegador**: Para errores de JavaScript

### 7. Pasar a Producción

Cuando estés listo para producción:

1. Cambia las credenciales de TEST por las de producción
2. Cambia `sandbox` a `false` en config.php
3. Actualiza la URL del webhook a tu dominio de producción
4. Realiza una transacción real de prueba con montos pequeños

## 🔒 Seguridad

### Recomendaciones Importantes:

1. **NUNCA** subas las credenciales reales a Git
2. Usa variables de entorno en producción
3. Habilita HTTPS obligatoriamente
4. Valida siempre los webhooks
5. Implementa logs de auditoría
6. Monitorea transacciones sospechosas

## 🧪 Testing Checklist

- [ ] Pago exitoso con tarjeta de crédito
- [ ] Pago rechazado (usar nombre OTHE)
- [ ] Webhook recibe notificaciones
- [ ] Suscripción se activa después del pago
- [ ] Usuario puede ver historial de pagos
- [ ] Manejo correcto de errores
- [ ] Timeout de sesión no afecta el pago
- [ ] URLs de retorno funcionan correctamente

## 📞 Soporte

### Recursos Útiles:

- **Documentación MercadoPago**: https://www.mercadopago.com.ar/developers/es/docs
- **SDK PHP**: https://github.com/mercadopago/dx-php
- **Soporte Técnico**: https://www.mercadopago.com.ar/developers/es/support
- **Estado del Servicio**: https://status.mercadopago.com/

### Errores Comunes:

1. **"Invalid credentials"**: Verifica que las credenciales sean correctas y del ambiente correcto (test/prod)
2. **"Preference not found"**: La preferencia expiró o el ID es incorrecto
3. **"Invalid installments"**: Para suscripciones, usa siempre 1 cuota
4. **Webhook no llega**: Verifica que la URL sea accesible públicamente

## 📊 Monitoreo

### KPIs a Seguir:

- Tasa de conversión (checkouts iniciados vs completados)
- Métodos de pago más usados
- Horarios de mayor conversión
- Razones de rechazo más comunes
- Tiempo promedio de procesamiento

### Dashboard de MercadoPago:

Accede a estadísticas detalladas en:
https://www.mercadopago.com.ar/balance/reports

---

**Nota**: Este documento contiene información sensible. No lo compartas públicamente.