<?php

namespace App\Models;

use App\Core\Model;
use App\Core\DB;

class Appointment extends Model
{
    protected string $table = 'appointments';
    
    protected array $fillable = [
        'user_id',
        'client_id',
        'client_name',
        'service_id',
        'price',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'phone'
    ];
    
    public function getByDateRange(int $userId, string $startDate, string $endDate, ?string $status = null): array
    {
        $sql = "SELECT a.*, s.name as service_name, s.color as service_color, c.name as saved_client_name 
                FROM {$this->table} a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN clients c ON a.client_id = c.id
                WHERE a.user_id = :user_id 
                AND a.starts_at >= :start_date 
                AND a.starts_at <= :end_date";
        
        $params = [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        
        if ($status !== null) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY a.starts_at ASC";
        
        $stmt = DB::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function getUpcoming(int $userId, int $days = 7, ?int $limit = null): array
    {
        $sql = "SELECT a.*, s.name as service_name, s.color as service_color, c.name as saved_client_name 
                FROM {$this->table} a
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN clients c ON a.client_id = c.id
                WHERE a.user_id = :user_id 
                AND a.starts_at >= NOW()
                AND a.starts_at <= DATE_ADD(NOW(), INTERVAL :days DAY)
                AND a.status = 'scheduled'
                ORDER BY a.starts_at ASC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = DB::query($sql, [
            'user_id' => $userId,
            'days' => $days
        ]);
        
        return $stmt->fetchAll();
    }
    
    public function getTopServices(int $userId, string $startDate, string $endDate, int $limit = 5): array
    {
        $sql = "SELECT s.name, s.color, COUNT(a.id) as count, SUM(a.price) as revenue
                FROM {$this->table} a
                JOIN services s ON a.service_id = s.id
                WHERE a.user_id = :user_id 
                AND a.starts_at >= :start_date 
                AND a.starts_at <= :end_date
                AND a.status IN ('scheduled', 'completed')
                GROUP BY s.id, s.name, s.color
                ORDER BY count DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function checkOverlap(int $userId, string $startsAt, string $endsAt, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE user_id = :user_id
                AND status IN ('scheduled')
                AND (
                    (starts_at < :ends_at AND ends_at > :starts_at)
                    OR (starts_at >= :starts_at2 AND starts_at < :ends_at2)
                )";
        
        $params = [
            'user_id' => $userId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'starts_at2' => $startsAt,
            'ends_at2' => $endsAt
        ];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = DB::query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    public function getByClient(int $userId, int $clientId): array
    {
        $sql = "SELECT a.*, s.name as service_name, s.color as service_color 
                FROM {$this->table} a
                LEFT JOIN services s ON a.service_id = s.id
                WHERE a.user_id = :user_id AND a.client_id = :client_id
                ORDER BY a.starts_at DESC";
        
        $stmt = DB::query($sql, [
            'user_id' => $userId,
            'client_id' => $clientId
        ]);
        
        return $stmt->fetchAll();
    }
    
    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }
    
    public function getMonthlyRevenue(int $userId, int $months = 12): array
    {
        // Generate list of last N months
        $monthsList = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthsList[] = date('Y-m', strtotime("-$i months"));
        }
        
        // Get actual revenue data
        $sql = "SELECT 
                    DATE_FORMAT(starts_at, '%Y-%m') as month,
                    COALESCE(SUM(price), 0) as revenue,
                    COUNT(*) as appointments
                FROM {$this->table}
                WHERE user_id = :user_id
                AND status IN ('completed', 'scheduled')
                AND starts_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(starts_at, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = DB::query($sql, [
            'user_id' => $userId,
            'months' => $months
        ]);
        
        $revenueData = $stmt->fetchAll();
        
        // Create indexed array for easy lookup
        $indexedRevenue = [];
        foreach ($revenueData as $data) {
            $indexedRevenue[$data['month']] = $data;
        }
        
        // Fill in missing months with zero revenue
        $result = [];
        foreach ($monthsList as $month) {
            if (isset($indexedRevenue[$month])) {
                $result[] = $indexedRevenue[$month];
            } else {
                $result[] = [
                    'month' => $month,
                    'revenue' => 0,
                    'appointments' => 0
                ];
            }
        }
        
        return $result;
    }
}