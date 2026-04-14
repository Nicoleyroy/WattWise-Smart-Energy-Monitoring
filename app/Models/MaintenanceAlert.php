<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAlert extends Model
{
    protected $fillable = [
        'device_id',
        'plug_number',
        'message',
        'status',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'plug_number' => 'integer',
    ];

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
}
