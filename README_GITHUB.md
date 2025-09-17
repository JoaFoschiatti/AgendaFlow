# AgendaFlow - Sistema de Gestión de Turnos

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap">
  <img src="https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge" alt="License">
</div>

## 📋 Descripción

AgendaFlow es un sistema SaaS completo de gestión de citas y turnos desarrollado con arquitectura MVC en PHP puro. Diseñado para profesionales independientes y pequeños negocios que necesitan organizar su agenda de manera eficiente.

### ✨ Características Principales

- 📅 **Gestión de Turnos** - Agenda diaria y semanal con detección de solapamientos
- 💰 **Control de Ingresos** - Seguimiento de precios y reportes financieros
- 👥 **Gestión de Clientes** - Base de datos con historial completo
- 📊 **Analytics y Reportes** - Visualización de datos con Chart.js
- 💳 **Modelo SaaS** - 14 días trial + suscripción mensual con MercadoPago
- 📱 **100% Responsive** - Optimizado para móvil, tablet y desktop
- 🔒 **Seguridad Robusta** - CSRF, Argon2id, prepared statements, XSS prevention
- 📲 **Notificaciones** - Integración con WhatsApp para recordatorios

## 🚀 Instalación Rápida

### Requisitos
- PHP ≥ 7.4 (recomendado 8.2)
- MySQL ≥ 5.7 o MariaDB ≥ 10.2
- Apache con mod_rewrite
- Composer

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/tu-usuario/agendaflow.git
cd agendaflow
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar base de datos**
```bash
cp config/config.example.php config/config.php
# Editar config/config.php con tus credenciales
```

> **Nota:** Sin este archivo personalizado la app usará automáticamente `config/config.example.php`, pero deberías completar tus propios datos antes de desplegarla.

4. **Ejecutar migraciones**
```bash
php migrate.php
```

5. **Acceder a la aplicación**
```
http://localhost/agendaflow/public/
```

### 🔑 Credenciales Demo
- **Email:** demo@agendaflow.com
- **Password:** password

## 🏗️ Arquitectura

```
agendaflow/
├── app/
│   ├── Controllers/     # Controladores MVC
│   ├── Models/          # Modelos de datos
│   ├── Views/           # Vistas y templates
│   └── Core/            # Framework core
├── config/              # Configuración
├── migrations/          # Scripts SQL
├── public/              # Archivos públicos
│   ├── css/            # Estilos
│   └── index.php       # Entry point
└── storage/            # Logs y cache
```

## 🛠️ Stack Tecnológico

- **Backend:** PHP 8.2, PDO, MVC Pattern
- **Frontend:** Bootstrap 5.3, JavaScript Vanilla
- **Base de Datos:** MySQL/MariaDB
- **Pagos:** MercadoPago API
- **Seguridad:** Argon2id, CSRF Tokens, XSS Protection

## 📱 Responsive Design

- Mobile First approach
- Breakpoints optimizados
- Touch targets de 44x44px mínimo
- Navegación adaptable
- Tablas con scroll horizontal

## 🔒 Seguridad Implementada

- ✅ Password hashing con Argon2id/bcrypt
- ✅ Protección CSRF con tokens únicos
- ✅ Prevención SQL Injection (100% prepared statements)
- ✅ Prevención XSS (escape automático)
- ✅ Validación de sesiones seguras
- ✅ Auditoría completa de acciones

## 📊 Modelo de Negocio

- **Trial:** 14 días gratis sin tarjeta
- **Suscripción:** $8,900 ARS/mes
- **Pagos:** MercadoPago (preapproval)
- **Estados:** trialing, active, past_due, canceled

## 🤝 Contribuciones

Este es un proyecto propietario. Para consultas sobre licencias o colaboraciones, contactar al equipo de desarrollo.

## 📝 Documentación

La documentación completa está disponible en el archivo `README.md` del proyecto.

## ⚖️ Licencia

© 2025 AgendaFlow - Software Propietario. Todos los derechos reservados.

---

**Desarrollado con ❤️ para profesionales que valoran su tiempo**