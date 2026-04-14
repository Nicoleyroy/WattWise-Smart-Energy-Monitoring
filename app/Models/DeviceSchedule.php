<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSchedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'name',
        'start_time',
        'end_time',
        'status',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the device that owns the schedule.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
