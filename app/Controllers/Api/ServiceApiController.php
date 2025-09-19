<?php

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\RateLimiter;
use App\Models\Service;

class ServiceApiController extends ApiController
{
    private Service $serviceModel;

    public function __construct()
    {
        parent::__construct();
        $this->serviceModel = new Service();
    }

    public function index(): void
    {
        $this->requireApiAuth();

        // Apply rate limiting
        RateLimiter::middleware($_SERVER['REMOTE_ADDR'], 100, 1);

        // Get query parameters
        $page = (int)($this->requestData['page'] ?? 1);
        $perPage = min((int)($this->requestData['per_page'] ?? 20), 100);
        $active = $this->requestData['active'] ?? null;

        // Build conditions
        $conditions = ['user_id' => $this->currentUser['id']];

        if ($active !== null) {
            $conditions['is_active'] = $active === 'true' || $active === '1' ? 1 : 0;
        }

        // Get services
        $services = $this->serviceModel->findAll($conditions);

        // Sort by name
        usort($services, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Paginate results
        $total = count($services);
        $offset = ($page - 1) * $perPage;
        $paginatedServices = array_slice($services, $offset, $perPage);

        $this->paginatedResponse($paginatedServices, $total, $page, $perPage, 'Services retrieved');
    }

    public function show(int $id): void
    {
        $this->requireApiAuth();

        $service = $this->serviceModel->find($id);

        $this->validateOwnership($service);

        $this->successResponse($service, 'Service retrieved');
    }

    public function store(): void
    {
        $this->requireApiAuth();

        // Apply rate limiting
        RateLimiter::middleware($_SERVER['REMOTE_ADDR'], 30, 1);

        // Validate required fields (accept legacy keys and map)
        $this->validateRequired(['name', 'duration', 'price']);

        // Map legacy keys duration/price to duration_min/price_default
        $duration = (int)$this->requestData['duration'];
        $price = (float)$this->requestData['price'];

        $data = [
            'user_id' => $this->currentUser['id'],
            'name' => htmlspecialchars($this->requestData['name']),
            'description' => htmlspecialchars($this->requestData['description'] ?? ''),
            'duration_min' => $duration,
            'price_default' => $price,
            'is_active' => isset($this->requestData['is_active']) ?
                ($this->requestData['is_active'] ? 1 : 0) : 1
        ];

        // Validate duration
        if ($data['duration_min'] < 15 || $data['duration_min'] > 480) {
            $this->errorResponse('Duration must be between 15 and 480 minutes', 422);
        }

        // Validate price
        if ($data['price_default'] < 0) {
            $this->errorResponse('Price cannot be negative', 422);
        }

        // Check for duplicate service name
        $existing = $this->serviceModel->findBy('name', $data['name']);
        if ($existing && $existing['user_id'] == $this->currentUser['id']) {
            $this->errorResponse('Service with this name already exists', 409);
        }

        // Create service
        $serviceId = $this->serviceModel->create($data);

        if (!$serviceId) {
            $this->errorResponse('Failed to create service', 500);
        }

        $service = $this->serviceModel->find($serviceId);
        // Include legacy aliases for compatibility
        if ($service) {
            $service['duration'] = $service['duration_min'] ?? null;
            $service['price'] = $service['price_default'] ?? null;
        }

        $this->logApiAccess('/api/v1/services', 'POST', ['service_id' => $serviceId]);

        $this->successResponse($service, 'Service created successfully', 201);
    }

    public function update(int $id): void
    {
        $this->requireApiAuth();

        $service = $this->serviceModel->find($id);
        $this->validateOwnership($service);

        $data = [];

        // Update allowed fields
        if (isset($this->requestData['name'])) {
            $data['name'] = htmlspecialchars($this->requestData['name']);

            // Check for duplicate name
            $existing = $this->serviceModel->findBy('name', $data['name']);
            if ($existing && $existing['id'] != $id && $existing['user_id'] == $this->currentUser['id']) {
                $this->errorResponse('Service with this name already exists', 409);
            }
        }

        if (isset($this->requestData['description'])) {
            $data['description'] = htmlspecialchars($this->requestData['description']);
        }

        if (isset($this->requestData['duration'])) {
            $data['duration_min'] = (int)$this->requestData['duration'];
            if ($data['duration_min'] < 15 || $data['duration_min'] > 480) {
                $this->errorResponse('Duration must be between 15 and 480 minutes', 422);
            }
        }

        if (isset($this->requestData['price'])) {
            $data['price_default'] = (float)$this->requestData['price'];
            if ($data['price_default'] < 0) {
                $this->errorResponse('Price cannot be negative', 422);
            }
        }

        if (isset($this->requestData['is_active'])) {
            $data['is_active'] = $this->requestData['is_active'] ? 1 : 0;
        }

        if (empty($data)) {
            $this->errorResponse('No fields to update', 422);
        }

        $success = $this->serviceModel->update($id, $data);

        if (!$success) {
            $this->errorResponse('Failed to update service', 500);
        }

        $service = $this->serviceModel->find($id);
        if ($service) {
            $service['duration'] = $service['duration_min'] ?? null;
            $service['price'] = $service['price_default'] ?? null;
        }

        $this->logApiAccess('/api/v1/services/' . $id, 'PUT', $data);

        $this->successResponse($service, 'Service updated successfully');
    }

    public function destroy(int $id): void
    {
        $this->requireApiAuth();

        $service = $this->serviceModel->find($id);
        $this->validateOwnership($service);

        // Check if service has appointments
        $appointmentModel = new \App\Models\Appointment();
        $appointments = $appointmentModel->findAll([
            'service_id' => $id,
            'user_id' => $this->currentUser['id']
        ]);

        if (!empty($appointments)) {
            // If there are appointments, just deactivate the service
            $this->serviceModel->update($id, ['is_active' => 0]);
            $this->successResponse(null, 'Service deactivated (has existing appointments)');
        } else {
            // If no appointments, delete the service
            $success = $this->serviceModel->delete($id);

            if (!$success) {
                $this->errorResponse('Failed to delete service', 500);
            }

            $this->logApiAccess('/api/v1/services/' . $id, 'DELETE', null);

            $this->successResponse(null, 'Service deleted successfully');
        }
    }

    public function statistics(int $id): void
    {
        $this->requireApiAuth();

        $service = $this->serviceModel->find($id);
        $this->validateOwnership($service);

        // Get appointment statistics for this service
        $appointmentModel = new \App\Models\Appointment();
        $appointments = $appointmentModel->findAll([
            'service_id' => $id,
            'user_id' => $this->currentUser['id']
        ]);

        $stats = [
            'total_appointments' => count($appointments),
            'completed' => 0,
            'canceled' => 0,
            'no_show' => 0,
            'scheduled' => 0,
            'total_revenue' => 0,
            'average_revenue' => 0
        ];

        foreach ($appointments as $appointment) {
            $stats[$appointment['status']]++;
            if ($appointment['status'] === 'completed') {
                $stats['total_revenue'] += $appointment['price'];
            }
        }

        if ($stats['completed'] > 0) {
            $stats['average_revenue'] = $stats['total_revenue'] / $stats['completed'];
        }

        $this->successResponse([
            'service' => $service,
            'statistics' => $stats
        ], 'Service statistics retrieved');
    }
}
