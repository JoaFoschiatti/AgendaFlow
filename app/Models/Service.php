<?php

namespace App\Models;

use App\Core\Model;

class Service extends Model
{
    protected string $table = 'services';

    protected array $fillable = [
        'user_id',
        'name',
        'description',
        'price_default',
        'duration_min',
        'color',
        'is_active'
    ];

    public function getActiveByUser(int $userId): array
    {
        return parent::all([
            'user_id' => $userId,
            'is_active' => 1
        ]);
    }

    public function getAllByUser(int $userId): array
    {
        return parent::all(['user_id' => $userId]);
    }

    // Method to support findAll calls from controllers
    public function findAll(array $conditions = [], array $orderBy = []): array
    {
        return parent::all($conditions, $orderBy);
    }
}
