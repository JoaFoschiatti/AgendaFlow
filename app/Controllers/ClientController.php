<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Client;
use App\Models\Appointment;

class ClientController extends Controller
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
        $this->requireAuth();
        
        $search = $_GET['search'] ?? '';
        
        if (!empty($search)) {
            $clients = $this->clientModel->search($this->user['id'], $search);
        } else {
            $clients = $this->clientModel->getWithAppointmentCount($this->user['id']);
        }
        
        $this->render('clients/index', [
            'clients' => $clients,
            'search' => $search
        ]);
    }
    
    public function store(): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Token de seguridad inválido.'], 403);
        }
        
        // Validate input
        $errors = $this->validate($_POST, [
            'name' => 'required|min:2|max:100'
        ]);
        
        if (!empty($errors)) {
            $this->json(['errors' => $errors], 400);
        }
        
        // Create client
        $clientId = $this->clientModel->create([
            'user_id' => $this->user['id'],
            'name' => $_POST['name'],
            'phone' => $_POST['phone'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ]);
        
        if (!$clientId) {
            $this->json(['error' => 'Error al crear el cliente.'], 500);
        }
        
        $this->auditLog('client_created', 'client', $clientId, $_POST);
        
        $this->setFlash('success', 'Cliente creado exitosamente.');
        $this->redirect('/clients');
    }
    
    public function edit(string $id): void
    {
        $this->requireAuth();
        
        $client = $this->clientModel->find((int)$id);
        
        if (!$client || $client['user_id'] != $this->user['id']) {
            $this->setFlash('error', 'Cliente no encontrado.');
            $this->redirect('/clients');
        }
        
        // Get client appointments
        $appointments = $this->appointmentModel->getByClient($this->user['id'], (int)$id);
        
        $this->render('clients/edit', [
            'client' => $client,
            'appointments' => $appointments
        ]);
    }
    
    public function update(string $id): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/clients/' . $id . '/edit');
        }
        
        $client = $this->clientModel->find((int)$id);
        
        if (!$client || $client['user_id'] != $this->user['id']) {
            $this->setFlash('error', 'Cliente no encontrado.');
            $this->redirect('/clients');
        }
        
        // Validate input
        $errors = $this->validate($_POST, [
            'name' => 'required|min:2|max:100'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/clients/' . $id . '/edit');
        }
        
        // Update client
        $updated = $this->clientModel->update((int)$id, [
            'name' => $_POST['name'],
            'phone' => $_POST['phone'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ]);
        
        if (!$updated) {
            $this->setFlash('error', 'Error al actualizar el cliente.');
            $this->redirect('/clients/' . $id . '/edit');
        }
        
        $this->auditLog('client_updated', 'client', (int)$id, $_POST);
        
        $this->setFlash('success', 'Cliente actualizado exitosamente.');
        $this->redirect('/clients');
    }
    
    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->json(['error' => 'Token de seguridad inválido.'], 403);
        }
        
        $client = $this->clientModel->find((int)$id);
        
        if (!$client || $client['user_id'] != $this->user['id']) {
            $this->json(['error' => 'Cliente no encontrado.'], 404);
        }
        
        // Check if client has appointments
        $hasAppointments = $this->appointmentModel->count([
            'user_id' => $this->user['id'],
            'client_id' => $id
        ]) > 0;
        
        if ($hasAppointments) {
            $this->setFlash('warning', 'No se puede eliminar el cliente porque tiene turnos asociados.');
        } else {
            $this->clientModel->delete((int)$id);
            $this->auditLog('client_deleted', 'client', (int)$id);
            $this->setFlash('success', 'Cliente eliminado exitosamente.');
        }
        
        $this->redirect('/clients');
    }
}