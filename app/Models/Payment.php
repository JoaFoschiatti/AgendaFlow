<?php

namespace App\Models;

use App\Core\Model;
use App\Core\DB;

class Payment extends Model
{
    protected string $table = 'payments';
    
    protected array $fillable = [
        'user_id',
        'mp_payment_id',
        'mp_preference_id',
        'mp_preapproval_id',
        'status',
        'amount',
        'currency',
        'payment_method',
        'payment_type',
        'payer_email',
        'payer_identification',
        'processed_at',
        'notification_received_at',
        'raw_data'
    ];
    
    /**
     * Find payment by MercadoPago payment ID
     */
    public function findByMpId(string $mpPaymentId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE mp_payment_id = :mp_payment_id LIMIT 1";
        $stmt = DB::query($sql, ['mp_payment_id' => $mpPaymentId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Find payment by preference ID
     */
    public function findByPreferenceId(string $preferenceId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE mp_preference_id = :mp_preference_id LIMIT 1";
        $stmt = DB::query($sql, ['mp_preference_id' => $preferenceId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get user's payment history
     */
    public function getUserPayments(int $userId, int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get approved payments for a user
     */
    public function getApprovedPayments(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND status = 'approved'
                ORDER BY processed_at DESC";
        
        $stmt = DB::query($sql, ['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last approved payment for user
     */
    public function getLastApprovedPayment(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND status = 'approved'
                ORDER BY processed_at DESC 
                LIMIT 1";
        
        $stmt = DB::query($sql, ['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Check if payment already exists (idempotency)
     */
    public function exists(string $mpPaymentId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE mp_payment_id = :mp_payment_id";
        $stmt = DB::query($sql, ['mp_payment_id' => $mpPaymentId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Log payment event
     */
    public function logEvent(int $paymentId, string $eventType, array $eventData, ?string $ip = null): bool
    {
        $sql = "INSERT INTO payment_logs (payment_id, event_type, event_data, ip_address, user_agent, headers) 
                VALUES (:payment_id, :event_type, :event_data, :ip_address, :user_agent, :headers)";
        
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        $stmt = DB::query($sql, [
            'payment_id' => $paymentId,
            'event_type' => $eventType,
            'event_data' => json_encode($eventData),
            'ip_address' => $ip ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'headers' => json_encode($headers)
        ]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get payment logs
     */
    public function getPaymentLogs(int $paymentId): array
    {
        $sql = "SELECT * FROM payment_logs 
                WHERE payment_id = :payment_id 
                ORDER BY created_at DESC";
        
        $stmt = DB::query($sql, ['payment_id' => $paymentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update payment status with validation
     */
    public function updateStatus(int $id, string $status, array $additionalData = []): bool
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'refunded', 'in_process'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid payment status: {$status}");
        }
        
        $data = array_merge(['status' => $status], $additionalData);
        
        // Log status change
        $this->logEvent($id, 'status_changed', [
            'new_status' => $status,
            'additional_data' => $additionalData
        ]);
        
        return $this->update($id, $data);
    }
    
    /**
     * Get payments summary for a date range
     */
    public function getSummary(int $userId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_amount,
                    AVG(CASE WHEN status = 'approved' THEN amount ELSE NULL END) as avg_amount
                FROM {$this->table}
                WHERE user_id = :user_id 
                AND created_at BETWEEN :start_date AND :end_date";
        
        $stmt = DB::query($sql, [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetch();
    }
}