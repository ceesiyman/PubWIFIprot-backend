<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\RateLimiter;

class NetworkReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'ssid',
        'bssid',
        'encryption_type',
        'signal_strength',
        'reason',
        'additional_info',
        'device_id',
        'ip_address',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'signal_strength' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Check if a device or IP has exceeded the rate limit
     */
    public static function checkRateLimit(string $identifier, string $type = 'ip'): bool
    {
        $key = "network_report:{$type}:{$identifier}";
        return RateLimiter::tooManyAttempts($key, 5); // 5 reports per hour
    }

    /**
     * Increment the rate limit counter for a device or IP
     */
    public static function incrementRateLimit(string $identifier, string $type = 'ip'): void
    {
        $key = "network_report:{$type}:{$identifier}";
        RateLimiter::hit($key, 3600); // 1 hour window
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }
} 