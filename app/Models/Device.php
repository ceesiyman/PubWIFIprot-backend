<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Device",
 *     title="Device",
 *     description="Device model",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="user_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="device_type", type="string", enum={"mobile", "tablet", "laptop"}, example="mobile"),
 *     @OA\Property(property="os_type", type="string", enum={"ios", "android", "windows", "macos"}, example="android"),
 *     @OA\Property(property="os_version", type="string", example="13.0"),
 *     @OA\Property(property="app_version", type="string", example="1.0.0"),
 *     @OA\Property(property="device_identifier", type="string", example="unique-device-id-123"),
 *     @OA\Property(property="device_name", type="string", nullable=true, example="John's iPhone"),
 *     @OA\Property(property="last_active_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class Device extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'user_id',
        'device_type',
        'os_type',
        'os_version',
        'app_version',
        'device_identifier',
        'device_name',
        'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dnsLookupLogs(): HasMany
    {
        return $this->hasMany(DnsLookupLog::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }
} 