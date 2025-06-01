<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'wifi_network_id',
        'started_at',
        'ended_at',
        'wifi_changes_count',
        'dns_requests_count',
        'blocked_domains_count',
        'session_summary',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'wifi_changes_count' => 'integer',
        'dns_requests_count' => 'integer',
        'blocked_domains_count' => 'integer',
        'session_summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function wifiNetwork(): BelongsTo
    {
        return $this->belongsTo(WifiNetwork::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('ended_at');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByWifiNetwork($query, $wifiNetworkId)
    {
        return $query->where('wifi_network_id', $wifiNetworkId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }
} 