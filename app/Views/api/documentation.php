<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaFlow API Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <style>
        .endpoint { border-left: 4px solid #0d6efd; padding-left: 1rem; margin-bottom: 2rem; }
        .method { font-weight: bold; padding: 0.25rem 0.5rem; border-radius: 4px; }
        .method-get { background: #28a745; color: white; }
        .method-post { background: #007bff; color: white; }
        .method-put { background: #ffc107; color: black; }
        .method-patch { background: #17a2b8; color: white; }
        .method-delete { background: #dc3545; color: white; }
        .response-example { background: #f8f9fa; border-radius: 8px; padding: 1rem; }
        .header-required { color: #dc3545; }
        pre { background: #2d2d2d; border-radius: 8px; }
        .nav-pills .nav-link.active { background-color: #0d6efd; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">AgendaFlow API v1 Documentation</span>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-3">
                <div class="sticky-top" style="top: 20px;">
                    <h5>Endpoints</h5>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#auth">Authentication</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#appointments">Appointments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#services">Services</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#clients">Clients</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-9">
                <h2>API Overview</h2>
                <p>Welcome to the AgendaFlow API. This RESTful API allows you to manage appointments, services, and clients programmatically.</p>

                <div class="alert alert-info">
                    <?php $apiBase = rtrim($config['app']['url'], '/') . '/api/v1'; ?>
                    <strong>Base URL:</strong> <code><?php echo htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8'); ?></code><br>
                    <strong>Authentication:</strong> Bearer Token (JWT)<br>
                    <strong>Content-Type:</strong> application/json
                </div>

                <h3>Rate Limiting</h3>
                <p>API requests are rate limited to prevent abuse:</p>
                <ul>
                    <li>Default: 60 requests per minute</li>
                    <li>Login: 5 attempts per 15 minutes</li>
                    <li>Registration: 3 attempts per hour</li>
                </ul>

                <hr class="my-5">

                <!-- Authentication Section -->
                <section id="auth">
                    <h2>Authentication</h2>
                    <p>All protected endpoints require a valid JWT token in the Authorization header.</p>

                    <!-- Login -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-post">POST</span>
                            <code>/auth/login</code>
                        </h4>
                        <p>Authenticate user and receive JWT tokens.</p>

                        <h6>Request Body:</h6>
                        <pre><code class="language-json">{
  "email": "user@example.com",
  "password": "password123"
}</code></pre>

                        <h6>Response (200 OK):</h6>
                        <pre><code class="language-json">{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "subscription_status": "active"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
      "token_type": "Bearer",
      "expires_in": 86400
    }
  }
}</code></pre>
                    </div>

                    <!-- Register -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-post">POST</span>
                            <code>/auth/register</code>
                        </h4>
                        <p>Create a new account with 14-day trial.</p>

                        <h6>Request Body:</h6>
                        <pre><code class="language-json">{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password123",
  "business_name": "My Business",
  "phone": "+541234567890"
}</code></pre>
                    </div>

                    <!-- Get Profile -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-get">GET</span>
                            <code>/auth/me</code>
                        </h4>
                        <p>Get current user profile.</p>

                        <h6>Headers:</h6>
                        <p><span class="header-required">Authorization:</span> Bearer {access_token}</p>
                    </div>
                </section>

                <hr class="my-5">

                <!-- Appointments Section -->
                <section id="appointments">
                    <h2>Appointments</h2>

                    <!-- List Appointments -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-get">GET</span>
                            <code>/appointments</code>
                        </h4>
                        <p>Get list of appointments with pagination.</p>

                        <h6>Query Parameters:</h6>
                        <ul>
                            <li><code>page</code> - Page number (default: 1)</li>
                            <li><code>per_page</code> - Items per page (default: 20, max: 100)</li>
                            <li><code>status</code> - Filter by status (scheduled, completed, canceled, no_show)</li>
                            <li><code>date</code> - Filter by specific date (YYYY-MM-DD)</li>
                            <li><code>start_date</code> - Filter from date</li>
                            <li><code>end_date</code> - Filter to date</li>
                        </ul>

                        <h6>Headers:</h6>
                        <p><span class="header-required">Authorization:</span> Bearer {access_token}</p>
                    </div>

                    <!-- Create Appointment -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-post">POST</span>
                            <code>/appointments</code>
                        </h4>
                        <p>Create a new appointment.</p>

                        <h6>Request Body:</h6>
                        <pre><code class="language-json">{
  "service_id": 1,
  "client_id": 2,
  "starts_at": "2025-09-20 10:00:00",
  "client_name": "Jane Doe",
  "phone": "+541234567890",
  "notes": "First consultation"
}</code></pre>
                        <p class="text-muted small">También se admite el alias <code>client_phone</code> para compatibilidad con versiones anteriores.</p>
                    </div>

                    <!-- Update Appointment -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-put">PUT</span>
                            <code>/appointments/{id}</code>
                        </h4>
                        <p>Update an existing appointment.</p>

                        <h6>Request Body:</h6>
                        <pre><code class="language-json">{
  "starts_at": "2025-09-20 11:00:00",
  "status": "completed",
  "notes": "Updated notes"
}</code></pre>
                    </div>

                    <!-- Delete Appointment -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-delete">DELETE</span>
                            <code>/appointments/{id}</code>
                        </h4>
                        <p>Cancel an appointment.</p>
                    </div>

                    <!-- Check Availability -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-post">POST</span>
                            <code>/appointments/availability</code>
                        </h4>
                        <p>Check available time slots for a service on a specific date.</p>

                        <h6>Request Body:</h6>
                        <pre><code class="language-json">{
  "date": "2025-09-20",
  "service_id": 1
}</code></pre>
                    </div>
                </section>

                <hr class="my-5">

                <!-- Services Section -->
                <section id="services">
                    <h2>Services</h2>

                    <!-- List Services -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-get">GET</span>
                            <code>/services</code>
                        </h4>
                        <p>Get list of services.</p>

                        <h6>Query Parameters:</h6>
                        <ul>
                            <li><code>page</code> - Page number</li>
                            <li><code>per_page</code> - Items per page</li>
                            <li><code>active</code> - Filter by active status (true/false)</li>
                        </ul>
                    </div>

                    <!-- Create Service -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-post">POST</span>
                            <code>/services</code>
                        </h4>
                        <p>Create a new service.</p>

                        <h6>Request Body:</h6>
                        <pre><code class="language-json">{
  "name": "Consultation",
  "description": "60-minute consultation",
  "duration": 60,
  "price": 5000,
  "is_active": true
}</code></pre>
                    </div>

                    <!-- Service Statistics -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-get">GET</span>
                            <code>/services/{id}/statistics</code>
                        </h4>
                        <p>Get statistics for a specific service.</p>
                    </div>
                </section>

                <hr class="my-5">

                <!-- Clients Section -->
                <section id="clients">
                    <h2>Clients</h2>

                    <!-- List Clients -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-get">GET</span>
                            <code>/clients</code>
                        </h4>
                        <p>Get list of clients.</p>

                        <h6>Query Parameters:</h6>
                        <ul>
                            <li><code>search</code> - Search by name, email, or phone</li>
                            <li><code>page</code> - Page number</li>
                            <li><code>per_page</code> - Items per page</li>
                        </ul>
                    </div>

                    <!-- Client Appointments -->
                    <div class="endpoint">
                        <h4>
                            <span class="method method-get">GET</span>
                            <code>/clients/{id}/appointments</code>
                        </h4>
                        <p>Get all appointments for a specific client.</p>
                    </div>
                </section>

                <hr class="my-5">

                <h2>Error Responses</h2>
                <p>All error responses follow this format:</p>
                <pre><code class="language-json">{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": "Field-specific error message"
  },
  "timestamp": "2025-09-15 10:30:00"
}</code></pre>

                <h3>HTTP Status Codes</h3>
                <ul>
                    <li><code>200</code> - Success</li>
                    <li><code>201</code> - Created</li>
                    <li><code>400</code> - Bad Request</li>
                    <li><code>401</code> - Unauthorized</li>
                    <li><code>402</code> - Payment Required (Subscription expired)</li>
                    <li><code>403</code> - Forbidden</li>
                    <li><code>404</code> - Not Found</li>
                    <li><code>409</code> - Conflict</li>
                    <li><code>422</code> - Unprocessable Entity (Validation error)</li>
                    <li><code>429</code> - Too Many Requests</li>
                    <li><code>500</code> - Internal Server Error</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script>
        // Smooth scrolling for navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Update active state
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>