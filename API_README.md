# AgendaFlow REST API

## 🚀 Descripción

API RESTful completa para AgendaFlow que permite la integración con aplicaciones externas, apps móviles y servicios de terceros.

## 📋 Características

- **Autenticación JWT** con tokens de acceso y refresh
- **Rate Limiting** para prevenir abuso
- **CORS habilitado** para aplicaciones web
- **Documentación interactiva** en `/api/v1/docs`
- **Versionado de API** (v1)
- **Respuestas JSON** estandarizadas
- **Códigos HTTP** semánticos
- **Validación** de datos robusta

## 🔑 Autenticación

La API usa JWT (JSON Web Tokens) para autenticación:

1. **Login** en `/api/v1/auth/login` para obtener tokens
2. **Incluir token** en header: `Authorization: Bearer {token}`
3. **Refresh token** cuando expire el access token

## 📍 Endpoints Principales

### Authentication
- `POST /api/v1/auth/login` - Iniciar sesión
- `POST /api/v1/auth/register` - Crear cuenta
- `POST /api/v1/auth/refresh` - Renovar token
- `GET /api/v1/auth/me` - Perfil actual
- `POST /api/v1/auth/logout` - Cerrar sesión

### Appointments
- `GET /api/v1/appointments` - Listar citas
- `GET /api/v1/appointments/{id}` - Detalle de cita
- `POST /api/v1/appointments` - Crear cita
- `PUT /api/v1/appointments/{id}` - Actualizar cita
- `DELETE /api/v1/appointments/{id}` - Cancelar cita
- `POST /api/v1/appointments/availability` - Verificar disponibilidad

### Services
- `GET /api/v1/services` - Listar servicios
- `GET /api/v1/services/{id}` - Detalle de servicio
- `POST /api/v1/services` - Crear servicio
- `PUT /api/v1/services/{id}` - Actualizar servicio
- `DELETE /api/v1/services/{id}` - Eliminar servicio
- `GET /api/v1/services/{id}/statistics` - Estadísticas

### Clients
- `GET /api/v1/clients` - Listar clientes
- `GET /api/v1/clients/{id}` - Detalle de cliente
- `POST /api/v1/clients` - Crear cliente
- `PUT /api/v1/clients/{id}` - Actualizar cliente
- `DELETE /api/v1/clients/{id}` - Eliminar cliente
- `GET /api/v1/clients/{id}/appointments` - Citas del cliente

## 🔒 Rate Limiting

- **General**: 60 requests/minuto
- **Login**: 5 intentos/15 minutos
- **Registro**: 3 intentos/hora
- **Creación de recursos**: 30/minuto

Headers de respuesta:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1631234567
```

## 📝 Ejemplo de Uso

### Login
```bash
curl -X POST http://localhost/AgendaFlow/public/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "demo@agendaflow.com",
    "password": "password"
  }'
```

### Crear Appointment
```bash
curl -X POST http://localhost/AgendaFlow/public/api/v1/appointments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 1,
    "starts_at": "2025-09-20 10:00:00",
    "client_name": "Juan Pérez"
  }'
```

## 🎯 Códigos de Estado

- `200` OK - Solicitud exitosa
- `201` Created - Recurso creado
- `401` Unauthorized - Token inválido/expirado
- `402` Payment Required - Suscripción expirada
- `403` Forbidden - Sin permisos
- `404` Not Found - Recurso no encontrado
- `409` Conflict - Conflicto (ej: horario ocupado)
- `422` Unprocessable Entity - Validación fallida
- `429` Too Many Requests - Rate limit excedido
- `500` Internal Server Error - Error del servidor

## 📊 Formato de Respuesta

### Éxito
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "timestamp": "2025-09-15 10:30:00"
}
```

### Error
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": "Specific error message"
  },
  "timestamp": "2025-09-15 10:30:00"
}
```

### Paginación
```json
{
  "success": true,
  "data": {
    "items": [...],
    "pagination": {
      "total": 100,
      "per_page": 20,
      "current_page": 1,
      "total_pages": 5,
      "has_more": true
    }
  }
}
```

## 🛠️ Testing

### Con cURL
```bash
# Test API root
curl http://localhost/AgendaFlow/public/api/v1

# Test con Postman
Import collection desde: /docs/postman_collection.json
```

### Con JavaScript
```javascript
const API_URL = 'http://localhost/AgendaFlow/public/api/v1';
const token = 'your_jwt_token';

// Login
fetch(`${API_URL}/auth/login`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'demo@agendaflow.com',
    password: 'password'
  })
})
.then(res => res.json())
.then(data => {
  localStorage.setItem('token', data.data.tokens.access_token);
});

// Get appointments
fetch(`${API_URL}/appointments`, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
.then(res => res.json())
.then(data => console.log(data));
```

## 🔧 Configuración

La configuración de la API está en `/config/api.config.php`:

```php
return [
    'jwt' => [
        'secret_key' => 'your-secret-key',
        'access_lifetime' => 86400, // 24 hours
        'refresh_lifetime' => 604800, // 7 days
    ],
    'api' => [
        'rate_limit' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ],
        'allowed_origins' => ['*'],
    ],
];
```

## 📚 Documentación

- **Documentación interactiva**: http://localhost/AgendaFlow/public/api/v1/docs
- **OpenAPI/Swagger**: Próximamente
- **Postman Collection**: Próximamente

## 🚀 Próximas Mejoras

- [ ] WebSockets para actualizaciones en tiempo real
- [ ] GraphQL endpoint
- [ ] Batch operations
- [ ] Webhooks para eventos
- [ ] API Keys para autenticación alternativa
- [ ] Sandbox environment
- [ ] SDKs para diferentes lenguajes

## 📞 Soporte

Para soporte o consultas sobre la API:
- Email: api@agendaflow.com
- Documentación: /api/v1/docs
- Issues: GitHub

---

*API Version: 1.0.0 | Last Updated: September 2025*