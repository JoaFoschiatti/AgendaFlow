<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Client;
use App\Models\Setting;

class AppointmentController extends Controller
{
    private Appointment $appointmentModel;
    private Service $serviceModel;
    private Client $clientModel;
    private Setting $settingModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->appointmentModel = new Appointment();
        $this->serviceModel = new Service();
        $this->clientModel = new Client();
        $this->settingModel = new Setting();
    }
    
    public function index(): void
    {
        $this->requireAuth();
        
        // Get date from query or use today
        $date = $_GET['date'] ?? date('Y-m-d');
        $view = $_GET['view'] ?? 'day'; // day or week
        
        $appointments = [];
        $weekDates = [];
        
        if ($view === 'week') {
            // Get week start (Monday) and end (Sunday)
            $timestamp = strtotime($date);
            $weekStart = date('Y-m-d', strtotime('monday this week', $timestamp));
            $weekEnd = date('Y-m-d', strtotime('sunday this week', $timestamp));
            
            // Generate all days of the week
            for ($i = 0; $i < 7; $i++) {
                $currentDate = date('Y-m-d', strtotime($weekStart . ' +' . $i . ' days'));
                $weekDates[] = $currentDate;
                
                $dayAppointments = $this->appointmentModel->getByDateRange(
                    $this->user['id'],
                    $currentDate . ' 00:00:00',
                    $currentDate . ' 23:59:59'
                );
                
                $appointments[$currentDate] = $dayAppointments;
            }
        } else {
            // Day view
            $appointments = $this->appointmentModel->getByDateRange(
                $this->user['id'],
                $date . ' 00:00:00',
                $date . ' 23:59:59'
            );
        }
        
        $this->render('appointments/index', [
            'appointments' => $appointments,
            'date' => $date,
            'viewType' => $view,  // Cambiado de 'view' a 'viewType' para evitar conflicto
            'weekDates' => $weekDates,
            'services' => $this->serviceModel->getActiveByUser($this->user['id']),
            'clients' => $this->clientModel->getByUser($this->user['id'])
        ]);
    }
    
    public function create(): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        $services = $this->serviceModel->getActiveByUser($this->user['id']);
        $clients = $this->clientModel->getByUser($this->user['id']);
        
        // Pre-fill date/time if provided
        $defaultDate = $_GET['date'] ?? date('Y-m-d');
        $defaultTime = $_GET['time'] ?? date('H:00');
        
        $this->render('appointments/create', [
            'services' => $services,
            'clients' => $clients,
            'defaultDate' => $defaultDate,
            'defaultTime' => $defaultTime
        ]);
    }
    
    public function store(): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/appointments/create');
        }
        
        // Validate input
        $errors = $this->validate($_POST, [
            'client_name' => 'required|min:2|max:100',
            'date' => 'required',
            'time' => 'required',
            'service_id' => 'required|numeric',
            'price' => 'required|numeric'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/appointments/create');
        }
        
        // Calculate start and end times
        $startsAt = $_POST['date'] . ' ' . $_POST['time'] . ':00';
        
        // Get service and validate ownership
        $service = $this->serviceModel->find($_POST['service_id']);
        if (!$service || $service['user_id'] != $this->user['id']) {
            $this->setFlash('error', 'Servicio inválido.');
            $_SESSION['old'] = $_POST;
            $this->redirect('/appointments/create');
        }
        $duration = $service['duration'] ?? 30;
        
        $endsAt = date('Y-m-d H:i:s', strtotime($startsAt . ' +' . $duration . ' minutes'));
        
        // Check for overlaps if not allowed
        if (!$this->settingModel->allowsOverlaps($this->user['id'])) {
            if ($this->appointmentModel->checkOverlap($this->user['id'], $startsAt, $endsAt)) {
                $this->setFlash('error', 'Ya existe un turno en ese horario.');
                $_SESSION['old'] = $_POST;
                $this->redirect('/appointments/create');
            }
        }
        
        // Handle client (create if requested)
        $clientId = null;
        if (isset($_POST['save_client']) && $_POST['save_client'] == '1') {
            $clientId = $this->clientModel->findOrCreate(
                $this->user['id'],
                $_POST['client_name'],
                $_POST['phone'] ?? null
            );
        }
        
        // Create appointment
        $appointmentId = $this->appointmentModel->create([
            'user_id' => $this->user['id'],
            'client_id' => $clientId,
            'client_name' => $_POST['client_name'],
            'service_id' => $_POST['service_id'],
            'price' => $_POST['price'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'scheduled',
            'notes' => $_POST['notes'] ?? null,
            'phone' => $_POST['phone'] ?? null
        ]);
        
        if (!$appointmentId) {
            $this->setFlash('error', 'Error al crear el turno.');
            $this->redirect('/appointments/create');
        }
        
        $this->auditLog('appointment_created', 'appointment', $appointmentId, $_POST);
        
        $this->setFlash('success', 'Turno creado exitosamente.');
        $this->redirect('/appointments?date=' . $_POST['date']);
    }
    
    public function edit(string $id): void
    {
        $this->requireAuth();
        
        $appointment = $this->appointmentModel->find((int)$id);
        
        if (!$appointment || $appointment['user_id'] != $this->user['id']) {
            $this->setFlash('error', 'Turno no encontrado.');
            $this->redirect('/appointments');
        }
        
        $services = $this->serviceModel->getActiveByUser($this->user['id']);
        $clients = $this->clientModel->getByUser($this->user['id']);
        
        $this->render('appointments/edit', [
            'appointment' => $appointment,
            'services' => $services,
            'clients' => $clients
        ]);
    }
    
    public function update(string $id): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/appointments/' . $id . '/edit');
        }
        
        $appointment = $this->appointmentModel->find((int)$id);
        
        if (!$appointment || $appointment['user_id'] != $this->user['id']) {
            $this->setFlash('error', 'Turno no encontrado.');
            $this->redirect('/appointments');
        }
        
        // Validate input
        $errors = $this->validate($_POST, [
            'client_name' => 'required|min:2|max:100',
            'date' => 'required',
            'time' => 'required',
            'service_id' => 'required|numeric',
            'price' => 'required|numeric'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/appointments/' . $id . '/edit');
        }
        
        // Calculate times
        $startsAt = $_POST['date'] . ' ' . $_POST['time'] . ':00';
        $service = $this->serviceModel->find($_POST['service_id']);
        $duration = $service['duration'] ?? 30;
        $endsAt = date('Y-m-d H:i:s', strtotime($startsAt . ' +' . $duration . ' minutes'));
        
        // Check for overlaps
        if (!$this->settingModel->allowsOverlaps($this->user['id'])) {
            if ($this->appointmentModel->checkOverlap($this->user['id'], $startsAt, $endsAt, (int)$id)) {
                $this->setFlash('error', 'Ya existe un turno en ese horario.');
                $_SESSION['old'] = $_POST;
                $this->redirect('/appointments/' . $id . '/edit');
            }
        }
        
        // Handle client
        $clientId = null;
        if (isset($_POST['save_client']) && $_POST['save_client'] == '1') {
            $clientId = $this->clientModel->findOrCreate(
                $this->user['id'],
                $_POST['client_name'],
                $_POST['phone'] ?? null
            );
        }
        
        // Update appointment
        $updated = $this->appointmentModel->update((int)$id, [
            'client_id' => $clientId,
            'client_name' => $_POST['client_name'],
            'service_id' => $_POST['service_id'],
            'price' => $_POST['price'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $_POST['notes'] ?? null,
            'phone' => $_POST['phone'] ?? null
        ]);
        
        if (!$updated) {
            $this->setFlash('error', 'Error al actualizar el turno.');
            $this->redirect('/appointments/' . $id . '/edit');
        }
        
        $this->auditLog('appointment_updated', 'appointment', (int)$id, $_POST);
        
        $this->setFlash('success', 'Turno actualizado exitosamente.');
        $this->redirect('/appointments?date=' . $_POST['date']);
    }
    
    public function cancel(string $id): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Token de seguridad inválido.'], 403);
        }
        
        $appointment = $this->appointmentModel->find((int)$id);
        
        if (!$appointment || $appointment['user_id'] != $this->user['id']) {
            $this->json(['error' => 'Turno no encontrado.'], 404);
        }
        
        $this->appointmentModel->updateStatus((int)$id, 'canceled');
        $this->auditLog('appointment_canceled', 'appointment', (int)$id);
        
        $this->setFlash('success', 'Turno cancelado.');
        $this->redirect('/appointments');
    }
    
    public function complete(string $id): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Token de seguridad inválido.'], 403);
        }
        
        $appointment = $this->appointmentModel->find((int)$id);
        
        if (!$appointment || $appointment['user_id'] != $this->user['id']) {
            $this->json(['error' => 'Turno no encontrado.'], 404);
        }
        
        $this->appointmentModel->updateStatus((int)$id, 'completed');
        $this->auditLog('appointment_completed', 'appointment', (int)$id);
        
        $this->setFlash('success', 'Turno marcado como completado.');
        $this->redirect('/appointments');
    }
    
    public function whatsapp(string $id): void
    {
        $this->requireAuth();
        
        $appointment = $this->appointmentModel->find((int)$id);
        
        if (!$appointment || $appointment['user_id'] != $this->user['id']) {
            $this->setFlash('error', 'Turno no encontrado.');
            $this->redirect('/appointments');
        }
        
        if (!$appointment['phone']) {
            $this->setFlash('error', 'Este turno no tiene teléfono asociado.');
            $this->redirect('/appointments');
        }
        
        // Generate WhatsApp message
        $date = \App\Core\Helpers::formatDate($appointment['starts_at'], 'd/m/Y');
        $time = \App\Core\Helpers::formatDate($appointment['starts_at'], 'H:i');
        
        $message = "Hola {$appointment['client_name']}! 👋\n\n";
        $message .= "Te recordamos tu turno:\n";
        $message .= "📅 Fecha: {$date}\n";
        $message .= "⏰ Hora: {$time}\n";
        $message .= "✂️ Servicio: {$appointment['service_name']}\n\n";
        $message .= "Te esperamos!\n";
        $message .= "{$this->user['business_name']}";
        
        $whatsappUrl = \App\Core\Helpers::generateWhatsAppLink($appointment['phone'], $message);
        
        $this->redirect($whatsappUrl);
    }
    
    public function checkOverlap(): void
    {
        $this->requireAuth();
        
        $date = $_GET['date'] ?? '';
        $time = $_GET['time'] ?? '';
        $duration = $_GET['duration'] ?? 30;
        $excludeId = $_GET['exclude'] ?? null;
        
        if (empty($date) || empty($time)) {
            $this->json(['overlap' => false]);
        }
        
        $startsAt = $date . ' ' . $time . ':00';
        $endsAt = date('Y-m-d H:i:s', strtotime($startsAt . ' +' . $duration . ' minutes'));
        
        $hasOverlap = $this->appointmentModel->checkOverlap(
            $this->user['id'],
            $startsAt,
            $endsAt,
            $excludeId ? (int)$excludeId : null
        );
        
        $this->json(['overlap' => $hasOverlap]);
    }
}