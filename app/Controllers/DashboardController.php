<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Client;

class DashboardController extends Controller
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
        $this->requireAuth();
        
        $userId = $this->user['id'];
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        
        // Get today's appointments
        $todayAppointments = $this->appointmentModel->getByDateRange(
            $userId,
            $today . ' 00:00:00',
            $today . ' 23:59:59'
        );
        
        // Get this month's completed appointments for revenue
        $monthAppointments = $this->appointmentModel->getByDateRange(
            $userId,
            $startOfMonth . ' 00:00:00',
            $endOfMonth . ' 23:59:59',
            'completed'
        );
        
        // Calculate month revenue
        $monthRevenue = array_reduce($monthAppointments, function($total, $appointment) {
            return $total + $appointment['price'];
        }, 0);
        
        // Get upcoming appointments (next 7 days)
        $upcomingAppointments = $this->appointmentModel->getUpcoming($userId, 7, 5);
        
        // Get services count
        $servicesCount = $this->serviceModel->count(['user_id' => $userId, 'is_active' => 1]);
        
        // Get clients count
        $clientsCount = $this->clientModel->count(['user_id' => $userId]);
        
        // Get most used services this month
        $topServices = $this->appointmentModel->getTopServices(
            $userId,
            $startOfMonth . ' 00:00:00',
            $endOfMonth . ' 23:59:59',
            3
        );
        
        // Trial info
        $trialDaysRemaining = null;
        if ($this->user['subscription_status'] === 'trialing') {
            $trialDaysRemaining = \App\Core\Helpers::getTrialDaysRemaining($this->user['trial_ends_at']);
        }
        
        $this->render('dashboard/index', [
            'todayAppointments' => $todayAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'monthRevenue' => $monthRevenue,
            'servicesCount' => $servicesCount,
            'clientsCount' => $clientsCount,
            'topServices' => $topServices,
            'trialDaysRemaining' => $trialDaysRemaining
        ]);
    }
}