<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Client;

class ReportController extends Controller
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
        
        // Get filter parameters
        $startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of month
        $endDate = $_GET['end_date'] ?? date('Y-m-t'); // Last day of month
        $serviceId = $_GET['service_id'] ?? null;
        
        // Get services for filter
        $services = $this->serviceModel->getAllByUser($this->user['id']);
        
        // Get appointments for period
        $appointments = $this->getFilteredAppointments($startDate, $endDate, $serviceId);
        
        // Calculate metrics
        $metrics = $this->calculateMetrics($appointments);
        
        // Get monthly revenue for chart (last 12 months)
        $monthlyRevenue = $this->appointmentModel->getMonthlyRevenue($this->user['id'], 12);
        
        // Get service distribution
        $serviceStats = $this->getServiceStats($startDate, $endDate);
        
        // Prepare chart data
        $chartData = $this->prepareChartData($monthlyRevenue, $serviceStats);
        
        $this->render('reports/index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'services' => $services,
            'selectedService' => $serviceId,
            'metrics' => $metrics,
            'appointments' => $appointments,
            'chartData' => $chartData,
            'includeChartJs' => true
        ]);
    }
    
    public function export(): void
    {
        $this->requireAuth();
        
        // Get filter parameters
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $serviceId = $_GET['service_id'] ?? null;
        
        // Get appointments
        $appointments = $this->getFilteredAppointments($startDate, $endDate, $serviceId);
        
        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.csv"');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, [
            'Fecha',
            'Hora',
            'Cliente',
            'Servicio',
            'Precio',
            'Estado',
            'Teléfono',
            'Notas'
        ], ';');
        
        // Write data
        foreach ($appointments as $appointment) {
            fputcsv($output, [
                date('d/m/Y', strtotime($appointment['starts_at'])),
                date('H:i', strtotime($appointment['starts_at'])),
                $appointment['client_name'],
                $appointment['service_name'] ?? 'N/A',
                number_format($appointment['price'], 2, ',', '.'),
                $this->getStatusLabel($appointment['status']),
                $appointment['phone'] ?? '',
                $appointment['notes'] ?? ''
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    private function getFilteredAppointments(string $startDate, string $endDate, ?string $serviceId): array
    {
        $sql = "SELECT a.*, s.name as service_name, s.color as service_color 
                FROM appointments a
                LEFT JOIN services s ON a.service_id = s.id
                WHERE a.user_id = :user_id 
                AND a.starts_at >= :start_date 
                AND a.starts_at <= :end_date";
        
        $params = [
            'user_id' => $this->user['id'],
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59'
        ];
        
        if ($serviceId) {
            $sql .= " AND a.service_id = :service_id";
            $params['service_id'] = $serviceId;
        }
        
        $sql .= " ORDER BY a.starts_at DESC";
        
        $stmt = \App\Core\DB::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    private function calculateMetrics(array $appointments): array
    {
        $totalRevenue = 0;
        $completedRevenue = 0;
        $completedCount = 0;
        $canceledCount = 0;
        $noShowCount = 0;
        
        foreach ($appointments as $appointment) {
            if ($appointment['status'] === 'completed') {
                $completedRevenue += $appointment['price'];
                $completedCount++;
            } elseif ($appointment['status'] === 'canceled') {
                $canceledCount++;
            } elseif ($appointment['status'] === 'no_show') {
                $noShowCount++;
            }
            
            if (in_array($appointment['status'], ['scheduled', 'completed'])) {
                $totalRevenue += $appointment['price'];
            }
        }
        
        return [
            'total_appointments' => count($appointments),
            'completed_appointments' => $completedCount,
            'canceled_appointments' => $canceledCount,
            'no_show_appointments' => $noShowCount,
            'total_revenue' => $totalRevenue,
            'completed_revenue' => $completedRevenue,
            'average_ticket' => $completedCount > 0 ? $completedRevenue / $completedCount : 0,
            'completion_rate' => count($appointments) > 0 ? ($completedCount / count($appointments)) * 100 : 0
        ];
    }
    
    private function getServiceStats(string $startDate, string $endDate): array
    {
        $sql = "SELECT s.name, s.color, COUNT(a.id) as count, SUM(a.price) as revenue
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                WHERE a.user_id = :user_id 
                AND a.starts_at >= :start_date 
                AND a.starts_at <= :end_date
                AND a.status IN ('scheduled', 'completed')
                GROUP BY s.id, s.name, s.color
                ORDER BY revenue DESC";
        
        $stmt = \App\Core\DB::query($sql, [
            'user_id' => $this->user['id'],
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59'
        ]);
        
        return $stmt->fetchAll();
    }
    
    private function prepareChartData(array $monthlyRevenue, array $serviceStats): array
    {
        // Prepare monthly revenue chart data
        $months = [];
        $revenues = [];
        
        // Data already comes in correct order from the model
        foreach ($monthlyRevenue as $month) {
            $monthName = \App\Core\Helpers::getMonthName((int)date('m', strtotime($month['month'] . '-01')));
            $year = date('Y', strtotime($month['month'] . '-01'));
            $months[] = $monthName . ' ' . $year;
            $revenues[] = (float)$month['revenue'];
        }
        
        // Prepare service distribution chart data
        $serviceNames = [];
        $serviceRevenues = [];
        $serviceColors = [];
        
        foreach ($serviceStats as $service) {
            $serviceNames[] = $service['name'];
            $serviceRevenues[] = $service['revenue'];
            $serviceColors[] = $service['color'] ?? '#6c757d';
        }
        
        return [
            'monthly' => [
                'labels' => $months,
                'data' => $revenues
            ],
            'services' => [
                'labels' => $serviceNames,
                'data' => $serviceRevenues,
                'colors' => $serviceColors
            ]
        ];
    }
    
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'scheduled' => 'Programado',
            'completed' => 'Completado',
            'canceled' => 'Cancelado',
            'no_show' => 'No asistió'
        ];
        
        return $labels[$status] ?? $status;
    }
}