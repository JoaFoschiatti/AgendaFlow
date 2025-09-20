<?php

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\RateLimiter;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Client;

class AppointmentApiController extends ApiController
{
    private Appointment $appointmentModel;
    private Service $serviceModel;
    private Client $clientModel;

    public function __construct()
    {
        parent::__construct();
        $this->appointmentModel = new Appointment();
        $this->serviceModel = new Service();
        $this->clientModel = new Client();
    }

    public function index(): void
    {
        $this->requireApiAuth();

        // Apply rate limiting
        RateLimiter::middleware($_SERVER['REMOTE_ADDR'], 100, 1);

        // Get query parameters
        $page = (int)($this->requestData['page'] ?? 1);
        $perPage = min((int)($this->requestData['per_page'] ?? 20), 100);
        $status = $this->requestData['status'] ?? null;
        $date = $this->requestData['date'] ?? null;
        $startDate = $this->requestData['start_date'] ?? null;
        $endDate = $this->requestData['end_date'] ?? null;

        // Build conditions
        $conditions = ['user_id' => $this->currentUser['id']];

        if ($status) {
            $conditions['status'] = $status;
        }

        // Get appointments
        $appointments = $this->appointmentModel->getByUserWithDetails(
            $this->currentUser['id'],
            $startDate,
            $endDate
        );
        $appointments = array_map(fn($apt) => $this->appendClientPhone($apt), $appointments);

        // Filter by specific date if provided
        if ($date) {
            $appointments = array_filter($appointments, function($apt) use ($date) {
                return date('Y-m-d', strtotime($apt['starts_at'])) === $date;
            });
        }

        // Paginate results
        $total = count($appointments);
        $offset = ($page - 1) * $perPage;
        $paginatedAppointments = array_slice($appointments, $offset, $perPage);

        $this->paginatedResponse($paginatedAppointments, $total, $page, $perPage, 'Appointments retrieved');
    }

    public function show(int $id): void
    {
        $this->requireApiAuth();

        $appointment = $this->appointmentModel->find($id);

        $this->validateOwnership($appointment);

        // Get full details
        $appointment['service'] = $this->serviceModel->find($appointment['service_id']);
        if ($appointment['client_id']) {
            $appointment['client'] = $this->clientModel->find($appointment['client_id']);
        }

        $appointment = $this->appendClientPhone($appointment);

        $this->successResponse($appointment, 'Appointment retrieved');
    }

    public function store(): void
    {
        $this->requireApiAuth();

        // Apply rate limiting
        RateLimiter::middleware($_SERVER['REMOTE_ADDR'], 30, 1);

        // Validate required fields
        $this->validateRequired(['service_id', 'starts_at']);

        $data = [
            'user_id' => $this->currentUser['id'],
            'service_id' => (int)$this->requestData['service_id'],
            'client_id' => isset($this->requestData['client_id']) ? (int)$this->requestData['client_id'] : null,
            'client_name' => htmlspecialchars($this->requestData['client_name'] ?? ''),
            'starts_at' => $this->requestData['starts_at'],
            'notes' => htmlspecialchars($this->requestData['notes'] ?? ''),
            'status' => 'scheduled'
        ];

        $rawPhone = trim($this->requestData['client_phone'] ?? $this->requestData['phone'] ?? '');
        $data['client_phone'] = $rawPhone !== '' ? $rawPhone : null;

        // Validate service ownership
        $service = $this->serviceModel->find($data['service_id']);
        $this->validateOwnership($service);

        // Check if service has duration
        if (!isset($service['duration_min']) || empty($service['duration_min'])) {
            $this->errorResponse('Service does not have a valid duration', 422);
        }

        // Calculate end time
        $startsAt = new \DateTime($data['starts_at']);
        $endsAt = clone $startsAt;
        $endsAt->add(new \DateInterval('PT' . $service['duration_min'] . 'M'));
        $data['ends_at'] = $endsAt->format('Y-m-d H:i:s');

        // Set price from service
        $data['price'] = $service['price_default'] ?? 0;

        // Check for overlapping appointments
        if ($this->appointmentModel->hasOverlap(
            $this->currentUser['id'],
            $data['starts_at'],
            $data['ends_at']
        )) {
            $this->errorResponse('Time slot is already booked', 409);
        }

        // Create appointment
        $appointmentId = $this->appointmentModel->create($data);

        if (!$appointmentId) {
            $this->errorResponse('Failed to create appointment', 500);
        }

        $appointment = $this->appendClientPhone($this->appointmentModel->find($appointmentId));

        $this->logApiAccess('/api/v1/appointments', 'POST', ['appointment_id' => $appointmentId]);

        $this->successResponse($appointment, 'Appointment created successfully', 201);
    }

    public function update(int $id): void
    {
        $this->requireApiAuth();

        $appointment = $this->appointmentModel->find($id);
        $this->validateOwnership($appointment);

        $data = [];

        // Update allowed fields
        if (isset($this->requestData['starts_at'])) {
            $data['starts_at'] = $this->requestData['starts_at'];

            // Recalculate end time if start time changes
            $service = $this->serviceModel->find($appointment['service_id']);
            $startsAt = new \DateTime($data['starts_at']);
            $endsAt = clone $startsAt;
            $endsAt->add(new \DateInterval('PT' . ($service['duration_min'] ?? 30) . 'M'));
            $data['ends_at'] = $endsAt->format('Y-m-d H:i:s');

            // Check for overlapping appointments
            if ($this->appointmentModel->hasOverlap(
                $this->currentUser['id'],
                $data['starts_at'],
                $data['ends_at'],
                $id
            )) {
                $this->errorResponse('Time slot is already booked', 409);
            }
        }

        if (isset($this->requestData['status'])) {
            $allowedStatuses = ['scheduled', 'completed', 'canceled', 'no_show'];
            if (!in_array($this->requestData['status'], $allowedStatuses)) {
                $this->errorResponse('Invalid status', 422);
            }
            $data['status'] = $this->requestData['status'];
        }

        if (isset($this->requestData['notes'])) {
            $data['notes'] = htmlspecialchars($this->requestData['notes']);
        }

        if (isset($this->requestData['client_name'])) {
            $data['client_name'] = htmlspecialchars($this->requestData['client_name']);
        }

        if (isset($this->requestData['client_phone']) || isset($this->requestData['phone'])) {
            $rawPhone = trim($this->requestData['client_phone'] ?? $this->requestData['phone']);
            $data['client_phone'] = $rawPhone !== '' ? $rawPhone : null;
        }

        if (empty($data)) {
            $this->errorResponse('No fields to update', 422);
        }

        $success = $this->appointmentModel->update($id, $data);

        if (!$success) {
            $this->errorResponse('Failed to update appointment', 500);
        }

        $appointment = $this->appointmentModel->find($id);
        $appointment = $this->appendClientPhone($appointment);

        $this->logApiAccess('/api/v1/appointments/' . $id, 'PUT', $data);

        $this->successResponse($appointment, 'Appointment updated successfully');
    }

    public function destroy(int $id): void
    {
        $this->requireApiAuth();

        $appointment = $this->appointmentModel->find($id);
        $this->validateOwnership($appointment);

        // Instead of deleting, we cancel the appointment
        $success = $this->appointmentModel->update($id, [
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s')
        ]);

        if (!$success) {
            $this->errorResponse('Failed to cancel appointment', 500);
        }

        $this->logApiAccess('/api/v1/appointments/' . $id, 'DELETE', null);

        $this->successResponse(null, 'Appointment canceled successfully');
    }

    private function appendClientPhone(?array $appointment): ?array
    {
        if ($appointment === null) {
            return null;
        }

        // Always expose client_phone while keeping legacy alias
        if (isset($appointment['client_phone'])) {
            $appointment['phone'] = $appointment['client_phone'];
        } elseif (isset($appointment['phone'])) {
            $appointment['client_phone'] = $appointment['phone'];
        } else {
            $appointment['client_phone'] = null;
        }
        
        return $appointment;
    }

    public function checkAvailability(): void
    {
        $this->requireApiAuth();

        $this->validateRequired(['date', 'service_id']);

        $date = $this->requestData['date'];
        $serviceId = (int)$this->requestData['service_id'];

        // Validate service ownership
        $service = $this->serviceModel->find($serviceId);
        $this->validateOwnership($service);

        // Get all appointments for the date
        $appointments = $this->appointmentModel->getByUserAndDate(
            $this->currentUser['id'],
            $date
        );

        // Generate available time slots
        $startHour = 8; // 8 AM
        $endHour = 20; // 8 PM
        $duration = $service['duration'];

        $availableSlots = [];
        $currentTime = strtotime($date . ' ' . $startHour . ':00:00');
        $endTime = strtotime($date . ' ' . $endHour . ':00:00');

        while ($currentTime < $endTime) {
            $slotEnd = $currentTime + ($duration * 60);

            $isAvailable = true;
            foreach ($appointments as $apt) {
                $aptStart = strtotime($apt['starts_at']);
                $aptEnd = strtotime($apt['ends_at']);

                if (($currentTime >= $aptStart && $currentTime < $aptEnd) ||
                    ($slotEnd > $aptStart && $slotEnd <= $aptEnd)) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable && $slotEnd <= $endTime) {
                $availableSlots[] = [
                    'start' => date('H:i', $currentTime),
                    'end' => date('H:i', $slotEnd),
                    'datetime' => date('Y-m-d H:i:s', $currentTime)
                ];
            }

            $currentTime += 1800; // Move 30 minutes
        }

        $this->successResponse([
            'date' => $date,
            'service_id' => $serviceId,
            'duration_minutes' => $duration,
            'available_slots' => $availableSlots
        ], 'Available slots retrieved');
    }
}
