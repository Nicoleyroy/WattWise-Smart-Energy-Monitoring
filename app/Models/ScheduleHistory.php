<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class ScheduleHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'schedule_id',
        'event',
        'name',
        'start_time',
        'end_time',
        'status',
        'logged_at',
        'archived',
        'archived_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'logged_at' => 'datetime',
        'archived' => 'boolean',
        'archived_at' => 'datetime',
    ];
}
