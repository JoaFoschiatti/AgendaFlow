<?php

namespace App\Models;

use App\Core\Model;
use App\Core\DB;

class Client extends Model
{
    protected string $table = 'clients';
    
    protected array $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'notes',
        'birth_date'
    ];
    
    public function getByUser(int $userId): array
    {
        return parent::all(['user_id' => $userId]);
    }

    // Method to support findAll calls from controllers
    public function findAll(array $conditions = [], array $orderBy = []): array
    {
        return parent::all($conditions, $orderBy);
    }
    
    public function search(int $userId, string $query): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                AND (name LIKE :query OR phone LIKE :query2 OR email LIKE :query3)
                ORDER BY name ASC";
        
        $stmt = DB::query($sql, [
            'user_id' => $userId,
            'query' => "%{$query}%",
            'query2' => "%{$query}%",
            'query3' => "%{$query}%"
        ]);
        
        return $stmt->fetchAll();
    }
    
    public function findOrCreate(int $userId, string $name, ?string $phone = null): int
    {
        // Try to find existing client
        $sql = "SELECT id FROM {$this->table} 
                WHERE user_id = :user_id AND name = :name";
        
        $stmt = DB::query($sql, [
            'user_id' => $userId,
            'name' => $name
        ]);
        
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update phone if provided and different
            if ($phone !== null) {
                $this->update($existing['id'], ['phone' => $phone]);
            }
            return $existing['id'];
        }
        
        // Create new client
        return $this->create([
            'user_id' => $userId,
            'name' => $name,
            'phone' => $phone
        ]);
    }
    
    public function getWithAppointmentCount(int $userId): array
    {
        $sql = "SELECT c.*, COUNT(a.id) as appointment_count
                FROM {$this->table} c
                LEFT JOIN appointments a ON c.id = a.client_id
                WHERE c.user_id = :user_id
                GROUP BY c.id
                ORDER BY c.name ASC";
        
        $stmt = DB::query($sql, ['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
