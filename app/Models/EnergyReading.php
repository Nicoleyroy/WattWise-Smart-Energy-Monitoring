<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyReading extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'voltage',
        'current',
        'power',
        'energy',
        'frequency',
        'power_factor',
        'device_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'voltage' => 'decimal:2',
        'current' => 'decimal:2',
        'power' => 'decimal:2',
        'energy' => 'decimal:4',
        'frequency' => 'decimal:2',
        'power_factor' => 'decimal:3',
    ];
}
