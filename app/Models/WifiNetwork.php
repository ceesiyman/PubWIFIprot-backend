<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="WifiNetwork",
 *     title="WifiNetwork",
 *     description="WiFi Network model",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="ssid", type="string", example="Coffee Shop WiFi"),
 *     @OA\Property(property="bssid", type="string", example="00:11:22:33:44:55"),
 *     @OA\Property(property="encryption_type", type="string", nullable=true, example="WPA2"),
 *     @OA\Property(property="channel", type="string", nullable=true, example="6"),
 *     @OA\Property(property="signal_strength", type="integer", nullable=true, example="-65"),
 *     @OA\Property(property="is_trusted", type="boolean", example=false),
 *     @OA\Property(property="is_flagged", type="boolean", example=false),
 *     @OA\Property(property="report_count", type="integer", example=0),
 *     @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class WifiNetwork extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'ssid',
        'bssid',
        'encryption_type',
        'channel',
        'signal_strength',
        'is_trusted',
        'is_flagged',
        'report_count',
        'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'is_flagged' => 'boolean',
        'signal_strength' => 'integer',
        'report_count' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function networkReports(): HasMany
    {
        return $this->hasMany(NetworkReport::class);
    }

    public function dnsLookupLogs(): HasMany
    {
        return $this->hasMany(DnsLookupLog::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeRecentlySeen($query, $days = 7)
    {
        return $query->where('last_seen_at', '>=', now()->subDays($days));
    }
} 