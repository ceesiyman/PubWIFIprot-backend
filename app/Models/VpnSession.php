<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnSession extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'client_ip',
        'server_address',
        'server_port',
        'client_public_key',
        'client_private_key',
        'bytes_sent',
        'bytes_received',
        'disconnected_at',
    ];

    protected $casts = [
        'bytes_sent' => 'integer',
        'bytes_received' => 'integer',
        'disconnected_at' => 'datetime',
    ];

    /**
     * Get the user that owns the VPN session
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->disconnected_at;
    }
} 


