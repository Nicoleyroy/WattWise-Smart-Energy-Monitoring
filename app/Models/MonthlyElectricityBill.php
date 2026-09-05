<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyElectricityBill extends Model
{
    protected $fillable = [
        'device_id',
        'billing_month',
        'baseline_kwh',
        'rate_per_kwh',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'baseline_kwh' => 'float',
        'rate_per_kwh' => 'float',
    ];
}
