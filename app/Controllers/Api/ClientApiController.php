<?php

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\RateLimiter;
use App\Models\Client;
use App\Models\Appointment;

class ClientApiController extends ApiController
{
    private Client $clientModel;
    private Appointment $appointmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
        $this->appointmentModel = new Appointment();
    }

    public function index(): void
    {
        $this->requireApiAuth();

        // Apply rate limiting
        RateLimiter::middleware($_SERVER['REMOTE_ADDR'], 100, 1);

        // Get query parameters
        $page = (int)($this->requestData['page'] ?? 1);
        $perPage = min((int)($this->requestData['per_page'] ?? 20), 100);
        $search = $this->requestData['search'] ?? null;

        // Get clients
        $clients = $this->clientModel->findAll(['user_id' => $this->currentUser['id']]);

        // Search filter
        if ($search) {
            $search = strtolower($search);
            $clients = array_filter($clients, function($client) use ($search) {
                return strpos(strtolower($client['name']), $search) !== false ||
                       strpos(strtolower($client['email'] ?? ''), $search) !== false ||
                       strpos(strtolower($client['phone'] ?? ''), $search) !== false;
            });
        }

        // Sort by name
        usort($clients, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Add appointment count for each client
        foreach ($clients as &$client) {
            $appointments = $this->appointmentModel->findAll([
                'client_id' => $client['id'],
                'user_id' => $this->currentUser['id']
            ]);
            $client['total_appointments'] = count($appointments);
            $client['last_appointment'] = null;

            if (!empty($appointments)) {
                usort($appointments, function($a, $b) {
                    return strtotime($b['starts_at']) - strtotime($a['starts_at']);
                });
                $client['last_appointment'] = $appointments[0]['starts_at'];
            }
        }

        // Paginate results
        $total = count($clients);
        $offset = ($page - 1) * $perPage;
        $paginatedClients = array_slice($clients, $offset, $perPage);

        $this->paginatedResponse($paginatedClients, $total, $page, $perPage, 'Clients retrieved');
    }

    public function show(int $id): void
    {
        $this->requireApiAuth();

        $client = $this->clientModel->find($id);

        $this->validateOwnership($client);

        // Get client appointments
        $appointments = $this->appointmentModel->findAll([
            'client_id' => $id,
            'user_id' => $this->currentUser['id']
        ]);

        // Sort appointments by date
        usort($appointments, function($a, $b) {
            return strtotime($b['starts_at']) - strtotime($a['starts_at']);
        });

        $client['appointments'] = $appointments;
        $client['total_appointments'] = count($appointments);

        // Calculate statistics
        $stats = [
            'total_spent' => 0,
            'completed_appointments' => 0,
            'canceled_appointments' => 0,
            'no_shows' => 0
        ];

        foreach ($appointments as $appointment) {
            if ($appointment['status'] === 'completed') {
                $stats['total_spent'] += $appointment['price'];
                $stats['completed_appointments']++;
            } elseif ($appointment['status'] === 'canceled') {
                $stats['canceled_appointments']++;
            } elseif ($appointment['status'] === 'no_show') {
                $stats['no_shows']++;
            }
        }

        $client['statistics'] = $stats;

        $this->successResponse($client, 'Client retrieved with details');
    }

    public function store(): void
    {
        $this->requireApiAuth();

        // Apply rate limiting
        RateLimiter::middleware($_SERVER['REMOTE_ADDR'], 30, 1);

        // Validate required fields
        $this->validateRequired(['name']);

        $data = [
            'user_id' => $this->currentUser['id'],
            'name' => htmlspecialchars($this->requestData['name']),
            'email' => filter_var($this->requestData['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'phone' => htmlspecialchars($this->requestData['phone'] ?? ''),
            'address' => htmlspecialchars($this->requestData['address'] ?? ''),
            'notes' => htmlspecialchars($this->requestData['notes'] ?? ''),
            'birth_date' => $this->requestData['birth_date'] ?? null
        ];

        // Validate email if provided
        if (isset($this->requestData['email']) && $this->requestData['email'] !== '' && !$data['email']) {
            $this->errorResponse('Invalid email format', 422);
        }

        // Check for duplicate client (by email or phone)
        if ($data['email']) {
            $existing = $this->clientModel->findBy('email', $data['email']);
            if ($existing && $existing['user_id'] == $this->currentUser['id']) {
                $this->errorResponse('Client with this email already exists', 409);
            }
        }

        // Create client
        $clientId = $this->clientModel->create($data);

        if (!$clientId) {
            $this->errorResponse('Failed to create client', 500);
        }

        $client = $this->clientModel->find($clientId);

        $this->logApiAccess('/api/v1/clients', 'POST', ['client_id' => $clientId]);

        $this->successResponse($client, 'Client created successfully', 201);
    }

    public function update(int $id): void
    {
        $this->requireApiAuth();

        $client = $this->clientModel->find($id);
        $this->validateOwnership($client);

        $data = [];

        // Update allowed fields
        if (isset($this->requestData['name'])) {
            $data['name'] = htmlspecialchars($this->requestData['name']);
        }

        if (isset($this->requestData['email'])) {
            $email = filter_var($this->requestData['email'], FILTER_VALIDATE_EMAIL);
            if ($this->requestData['email'] !== '' && !$email) {
                $this->errorResponse('Invalid email format', 422);
            }
            $data['email'] = $email ?: null;

            // Check for duplicate email
            if ($data['email']) {
                $existing = $this->clientModel->findBy('email', $data['email']);
                if ($existing && $existing['id'] != $id && $existing['user_id'] == $this->currentUser['id']) {
                    $this->errorResponse('Client with this email already exists', 409);
                }
            }
        }

        if (isset($this->requestData['phone'])) {
            $data['phone'] = htmlspecialchars($this->requestData['phone']);
        }

        if (isset($this->requestData['address'])) {
            $data['address'] = htmlspecialchars($this->requestData['address']);
        }

        if (isset($this->requestData['notes'])) {
            $data['notes'] = htmlspecialchars($this->requestData['notes']);
        }

        if (isset($this->requestData['birth_date'])) {
            $data['birth_date'] = $this->requestData['birth_date'];
        }

        if (empty($data)) {
            $this->errorResponse('No fields to update', 422);
        }

        $success = $this->clientModel->update($id, $data);

        if (!$success) {
            $this->errorResponse('Failed to update client', 500);
        }

        $client = $this->clientModel->find($id);

        $this->logApiAccess('/api/v1/clients/' . $id, 'PUT', $data);

        $this->successResponse($client, 'Client updated successfully');
    }

    public function destroy(int $id): void
    {
        $this->requireApiAuth();

        $client = $this->clientModel->find($id);
        $this->validateOwnership($client);

        // Check if client has appointments
        $appointments = $this->appointmentModel->findAll([
            'client_id' => $id,
            'user_id' => $this->currentUser['id']
        ]);

        if (!empty($appointments)) {
            $this->errorResponse('Cannot delete client with existing appointments', 409);
        }

        $success = $this->clientModel->delete($id);

        if (!$success) {
            $this->errorResponse('Failed to delete client', 500);
        }

        $this->logApiAccess('/api/v1/clients/' . $id, 'DELETE', null);

        $this->successResponse(null, 'Client deleted successfully');
    }

    public function appointments(int $id): void
    {
        $this->requireApiAuth();

        $client = $this->clientModel->find($id);
        $this->validateOwnership($client);

        // Get query parameters
        $page = (int)($this->requestData['page'] ?? 1);
        $perPage = min((int)($this->requestData['per_page'] ?? 20), 100);
        $status = $this->requestData['status'] ?? null;

        // Get client appointments
        $conditions = [
            'client_id' => $id,
            'user_id' => $this->currentUser['id']
        ];

        if ($status) {
            $conditions['status'] = $status;
        }

        $appointments = $this->appointmentModel->findAll($conditions);

        // Sort by date
        usort($appointments, function($a, $b) {
            return strtotime($b['starts_at']) - strtotime($a['starts_at']);
        });

        // Add service details
        $serviceModel = new \App\Models\Service();
        foreach ($appointments as &$appointment) {
            $appointment['service'] = $serviceModel->find($appointment['service_id']);
        }

        // Paginate results
        $total = count($appointments);
        $offset = ($page - 1) * $perPage;
        $paginatedAppointments = array_slice($appointments, $offset, $perPage);

        $this->paginatedResponse(
            $paginatedAppointments,
            $total,
            $page,
            $perPage,
            'Client appointments retrieved'
        );
    }
}