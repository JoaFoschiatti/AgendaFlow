<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Auth;

class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'name', 
        'business_name', 
        'email', 
        'phone',
        'password_hash',
        'timezone',
        'currency',
        'trial_starts_at',
        'trial_ends_at',
        'subscription_status',
        'mp_preapproval_id',
        'email_verified_at',
        'remember_token'
    ];
    
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }
    
    public function createWithTrial(array $data): ?int
    {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = Auth::hashPassword($data['password']);
            unset($data['password']);
        }
        
        // Set trial dates
        $now = new \DateTime();
        $data['trial_starts_at'] = $now->format('Y-m-d H:i:s');
        
        $trialEnd = clone $now;
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $trialEnd->add(new \DateInterval('P' . $config['business']['trial_days'] . 'D'));
        $data['trial_ends_at'] = $trialEnd->format('Y-m-d H:i:s');
        
        // Set default status
        $data['subscription_status'] = 'trialing';
        
        // Set default timezone and currency
        if (!isset($data['timezone'])) {
            $data['timezone'] = $config['app']['timezone'];
        }
        if (!isset($data['currency'])) {
            $data['currency'] = $config['app']['currency'];
        }
        
        return $this->create($data);
    }
    
    public function isInTrial(int $userId): bool
    {
        $user = $this->find($userId);
        
        if (!$user || $user['subscription_status'] !== 'trialing') {
            return false;
        }
        
        $now = new \DateTime();
        $trialEnd = new \DateTime($user['trial_ends_at']);
        
        return $now <= $trialEnd;
    }
    
    public function hasActiveSubscription(int $userId): bool
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return false;
        }
        
        // Check if in valid trial
        if ($this->isInTrial($userId)) {
            return true;
        }
        
        // Check if has active subscription
        return $user['subscription_status'] === 'active';
    }
    
    public function updateSubscriptionStatus(int $userId, string $status, ?string $preapprovalId = null): bool
    {
        $data = ['subscription_status' => $status];
        
        if ($preapprovalId !== null) {
            $data['mp_preapproval_id'] = $preapprovalId;
        }
        
        return $this->update($userId, $data);
    }
    
    public function getTrialDaysRemaining(int $userId): int
    {
        $user = $this->find($userId);
        
        if (!$user || !$user['trial_ends_at']) {
            return 0;
        }
        
        $now = new \DateTime();
        $trialEnd = new \DateTime($user['trial_ends_at']);
        
        if ($now >= $trialEnd) {
            return 0;
        }
        
        $diff = $now->diff($trialEnd);
        return $diff->days;
    }
}