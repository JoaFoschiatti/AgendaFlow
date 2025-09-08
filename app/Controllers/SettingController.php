<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setting;
use App\Models\User;

class SettingController extends Controller
{
    private Setting $settingModel;
    private User $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->settingModel = new Setting();
        $this->userModel = new User();
    }
    
    public function index(): void
    {
        $this->requireAuth();
        
        $settings = $this->settingModel->getByUser($this->user['id']);
        
        // Organize settings by day
        $businessHours = [];
        foreach ($settings as $setting) {
            $businessHours[$setting['day_of_week']] = $setting;
        }
        
        // Ensure all days exist
        for ($day = 0; $day <= 6; $day++) {
            if (!isset($businessHours[$day])) {
                $businessHours[$day] = [
                    'day_of_week' => $day,
                    'open_time' => null,
                    'close_time' => null,
                    'closed' => 1,
                    'slot_minutes' => 15,
                    'allow_overlaps' => 0
                ];
            }
        }
        
        ksort($businessHours);
        
        $this->render('settings/index', [
            'businessHours' => $businessHours
        ]);
    }
    
    public function update(): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/settings');
        }
        
        // Validate input
        $errors = $this->validate($_POST, [
            'business_name' => 'required|min:3|max:150',
            'name' => 'required|min:3|max:100'
        ]);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $this->redirect('/settings');
        }
        
        // Update user info
        $updated = $this->userModel->update($this->user['id'], [
            'name' => $_POST['name'],
            'business_name' => $_POST['business_name'],
            'phone' => $_POST['phone'] ?? null,
            'timezone' => $_POST['timezone'] ?? 'America/Argentina/Cordoba',
            'currency' => $_POST['currency'] ?? 'ARS'
        ]);
        
        if (!$updated) {
            $this->setFlash('error', 'Error al actualizar la configuración.');
            $this->redirect('/settings');
        }
        
        $this->auditLog('settings_updated', 'user', $this->user['id'], $_POST);
        
        $this->setFlash('success', 'Configuración actualizada exitosamente.');
        $this->redirect('/settings');
    }
    
    public function updateHours(): void
    {
        $this->requireAuth();
        $this->requireActiveSubscription();
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Token de seguridad inválido.');
            $this->redirect('/settings');
        }
        
        $days = $_POST['days'] ?? [];
        $slotMinutes = $_POST['slot_minutes'] ?? 15;
        $allowOverlaps = isset($_POST['allow_overlaps']) ? 1 : 0;
        
        // Update each day
        for ($day = 0; $day <= 6; $day++) {
            $dayData = $days[$day] ?? [];
            
            $isClosed = isset($dayData['closed']) ? 1 : 0;
            $openTime = !$isClosed && !empty($dayData['open_time']) ? $dayData['open_time'] . ':00' : null;
            $closeTime = !$isClosed && !empty($dayData['close_time']) ? $dayData['close_time'] . ':00' : null;
            
            $this->settingModel->updateOrCreate($this->user['id'], $day, [
                'open_time' => $openTime,
                'close_time' => $closeTime,
                'closed' => $isClosed,
                'slot_minutes' => $slotMinutes,
                'allow_overlaps' => $allowOverlaps
            ]);
        }
        
        $this->auditLog('business_hours_updated', 'settings', null, $_POST);
        
        $this->setFlash('success', 'Horarios de atención actualizados exitosamente.');
        $this->redirect('/settings');
    }
}