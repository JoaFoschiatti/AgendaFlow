<?php

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected string $table = 'settings';
    
    protected array $fillable = [
        'user_id',
        'day_of_week',
        'open_time',
        'close_time',
        'slot_minutes',
        'allow_overlaps',
        'closed'
    ];
    
    public function getByUser(int $userId): array
    {
        return $this->all(['user_id' => $userId], ['day_of_week' => 'ASC']);
    }
    
    public function getByUserAndDay(int $userId, int $dayOfWeek): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND day_of_week = :day";
        $stmt = \App\Core\DB::query($sql, [
            'user_id' => $userId,
            'day' => $dayOfWeek
        ]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function updateOrCreate(int $userId, int $dayOfWeek, array $data): bool
    {
        $existing = $this->getByUserAndDay($userId, $dayOfWeek);
        
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['user_id'] = $userId;
            $data['day_of_week'] = $dayOfWeek;
            return $this->create($data) !== null;
        }
    }
    
    public function allowsOverlaps(int $userId): bool
    {
        $settings = $this->getByUser($userId);
        
        if (empty($settings)) {
            return false;
        }
        
        // Return the allow_overlaps setting from any day (should be consistent)
        return (bool) $settings[0]['allow_overlaps'];
    }
}