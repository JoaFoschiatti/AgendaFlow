<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    private Service $serviceModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->serviceModel = new Service();
    }
    
    public function index(): void
    {
        $this->requireAuth();
        
        $services = $this->serviceModel->getAllByUser($this->user['id']);
        
        $this->render('services/index', [
            'services' => $services
        ]);
    }
    
    public function create(): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        $this->render('services/create');
    }
    
    public function store(): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/services/create');
        }
        
        // Validate input
        $errors = $this->validate($_POST, [
            'name' => 'required|min:2|max:100',
            'price_default' => 'required|numeric',
            'duration_min' => 'numeric'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/services/create');
        }
        
        // Create service
        $serviceId = $this->serviceModel->create([
            'user_id' => $this->user['id'],
            'name' => $_POST['name'],
            'price_default' => (float) $_POST['price_default'],
            'duration_min' => !empty($_POST['duration_min']) ? (int) $_POST['duration_min'] : null,
            'color' => !empty($_POST['color']) ? $_POST['color'] : '#6c757d',
            'is_active' => 1
        ]);
        
        if (!$serviceId) {
            $this->setFlash('error', 'Error al crear el servicio.');
            $this->redirect('/services/create');
        }
        
        $this->auditLog('service_created', 'service', $serviceId, $_POST);
        
        $this->setFlash('success', 'Servicio creado exitosamente.');
        $this->redirect('/services');
    }
    
    public function edit(string $id): void
    {
        $this->requireAuth();
        
        $service = $this->serviceModel->find((int)$id);
        $this->validateOwnership($service, '/services');
        
        $this->render('services/edit', [
            'service' => $service
        ]);
    }
    
    public function update(string $id): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/services/' . $id . '/edit');
        }
        
        $service = $this->serviceModel->find((int)$id);
        $this->validateOwnership($service, '/services');
        
        // Validate input
        $errors = $this->validate($_POST, [
            'name' => 'required|min:2|max:100',
            'price_default' => 'required|numeric',
            'duration_min' => 'numeric'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/services/' . $id . '/edit');
        }
        
        // Update service
        $updated = $this->serviceModel->update((int)$id, [
            'name' => $_POST['name'],
            'price_default' => (float) $_POST['price_default'],
            'duration_min' => !empty($_POST['duration_min']) ? (int) $_POST['duration_min'] : null,
            'color' => !empty($_POST['color']) ? $_POST['color'] : '#6c757d',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ]);
        
        if (!$updated) {
            $this->setFlash('error', 'Error al actualizar el servicio.');
            $this->redirect('/services/' . $id . '/edit');
        }
        
        $this->auditLog('service_updated', 'service', (int)$id, $_POST);
        
        $this->setFlash('success', 'Servicio actualizado exitosamente.');
        $this->redirect('/services');
    }
    
    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Token de seguridad inválido.'], 403);
        }
        
        $service = $this->serviceModel->find((int)$id);
        
        if (!$service || $service['user_id'] != $this->user['id']) {
            $this->json(['error' => 'Servicio no encontrado.'], 404);
        }
        
        // Check if service has appointments
        $appointmentModel = new \App\Models\Appointment();
        $hasAppointments = $appointmentModel->count([
            'user_id' => $this->user['id'],
            'service_id' => $id
        ]) > 0;
        
        if ($hasAppointments) {
            // Soft delete (deactivate)
            $this->serviceModel->update((int)$id, ['is_active' => 0]);
            $this->auditLog('service_deactivated', 'service', (int)$id);
            $this->setFlash('warning', 'El servicio ha sido desactivado porque tiene turnos asociados.');
        } else {
            // Hard delete
            $this->serviceModel->delete((int)$id);
            $this->auditLog('service_deleted', 'service', (int)$id);
            $this->setFlash('success', 'Servicio eliminado exitosamente.');
        }
        
        $this->redirect('/services');
    }
    
    public function getPrice(string $id): void
    {
        $this->requireAuth();
        
        $service = $this->serviceModel->find((int)$id);
        
        if (!$service || $service['user_id'] != $this->user['id']) {
            $this->json(['error' => 'Servicio no encontrado.'], 404);
        }
        
        $this->json([
            'price' => $service['price_default'] ?? 0,
            'duration' => $service['duration_min'] ?? 30
        ]);
    }
}