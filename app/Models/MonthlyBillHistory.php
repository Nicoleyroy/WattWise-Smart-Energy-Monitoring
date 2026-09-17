<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MonthlyBillHistory extends Model
{
    protected $collection = 'monthly_bill_history';

    protected $fillable = [
        'device_id',
        'year',
        'month',
        'plug1_kwh',
        'plug2_kwh',
        'total_kwh',
        'rate_per_kwh',
        'estimated_cost',
        'daily_records',
        'generated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'plug1_kwh' => 'float',
        'plug2_kwh' => 'float',
        'total_kwh' => 'float',
        'rate_per_kwh' => 'float',
        'estimated_cost' => 'float',
        'daily_records' => 'array',
        'generated_at' => 'datetime',
    ];
}
