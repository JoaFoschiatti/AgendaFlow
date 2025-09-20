AGENDAFLOW - PRODUCTION READY
==============================

Requirements:
- PHP 8.1+
- MySQL 5.7+ (or MariaDB 10.3+)
- Composer (for vendor dependencies)
- SSL certificate (recommended for PWA & secure cookies)

Database Setup:
- Import agendaflow_database.sql into MySQL
- Or run: mysql -u root -p < agendaflow_database.sql
- Edit config/config.php with your DB credentials and production URL

Install Dependencies:
- Run: composer install

Web Server:
- Point the document root to the public/ directory
- Ensure .htaccess is enabled (Apache: AllowOverride All)

Permissions:
- storage/logs must be writable by the web server user

Configuration Tips:
- Set app.debug to false in production (config/config.php)
- Set app.url to your HTTPS base URL (e.g., https://example.com/AgendaFlow)
- MercadoPago integration is optional; enable only after setting valid credentials

Verification:
- Access / to verify dashboard loads
- API: /api/v1 should return metadata JSON

Version: 2.0.0
Date: 2025-09-19
