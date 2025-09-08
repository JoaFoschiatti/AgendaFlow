<?php

namespace App\Models;

use App\Core\Model;

class Subscription extends Model
{
    protected string $table = 'subscriptions';
    
    protected array $fillable = [
        'user_id',
        'currency',
        'amount',
        'status',
        'mp_preapproval_id',
        'next_charge_at',
        'canceled_at',
        'raw_data'
    ];
    
    public function getByUser(int $userId): ?array
    {
        return $this->findBy('user_id', $userId);
    }
    
    public function getByPreapprovalId(string $preapprovalId): ?array
    {
        return $this->findBy('mp_preapproval_id', $preapprovalId);
    }
    
    public function createOrUpdate(int $userId, array $data): int
    {
        $existing = $this->getByUser($userId);
        
        if ($existing) {
            $this->update($existing['id'], $data);
            return $existing['id'];
        } else {
            $data['user_id'] = $userId;
            return $this->create($data);
        }
    }
    
    public function updateStatus(string $preapprovalId, string $status, ?array $rawData = null): bool
    {
        $subscription = $this->getByPreapprovalId($preapprovalId);
        
        if (!$subscription) {
            return false;
        }
        
        $updateData = ['status' => $status];
        
        if ($rawData) {
            $updateData['raw_data'] = json_encode($rawData);
        }
        
        if ($status === 'cancelled' || $status === 'canceled') {
            $updateData['canceled_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($subscription['id'], $updateData);
    }
}