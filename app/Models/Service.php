<?php

namespace App\Models;

use App\Core\Model;

class Service extends Model
{
    protected string $table = 'services';
    
    protected array $fillable = [
        'user_id',
        'name',
        'price_default',
        'duration_min',
        'color',
        'active'
    ];
    
    public function getActiveByUser(int $userId): array
    {
        return $this->all([
            'user_id' => $userId,
            'active' => 1
        ], ['name' => 'ASC']);
    }
    
    public function getAllByUser(int $userId): array
    {
        return $this->all(['user_id' => $userId], ['name' => 'ASC']);
    }
}