<?php

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    protected string $table = 'audit_logs';
    
    protected array $fillable = [
        'user_id',
        'action',
        'entity',
        'entity_id',
        'data',
        'ip_address',
        'user_agent'
    ];
    
    public function log(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        array $payload = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'data' => !empty($payload) ? json_encode($payload) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];
        
        $this->create($data);
    }
    
    public function getByUser(int $userId, int $limit = 50): array
    {
        return $this->all(
            ['user_id' => $userId],
            ['created_at' => 'DESC'],
            $limit
        );
    }
}
