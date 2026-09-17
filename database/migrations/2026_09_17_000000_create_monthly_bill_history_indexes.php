<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $energyCollection = DB::connection('mongodb')->getMongoDB()->selectCollection('energy_readings');
        $energyCollection->createIndex(
            ['device_id' => 1, 'created_at' => 1],
            ['name' => 'energy_readings_device_created_at'],
        );
        $energyCollection->createIndex(
            ['device_id' => 1, 'sampled_at' => 1],
            ['name' => 'energy_readings_device_sampled_at'],
        );

        $collection = DB::connection('mongodb')->getMongoDB()->selectCollection('monthly_bill_history');
        $collection->createIndex(
            ['device_id' => 1, 'year' => -1, 'month' => -1],
            ['name' => 'monthly_bill_history_device_period_unique', 'unique' => true],
        );
        $collection->createIndex(
            ['year' => -1, 'month' => -1],
            ['name' => 'monthly_bill_history_period'],
        );
    }

    public function down(): void
    {
        $energyCollection = DB::connection('mongodb')->getMongoDB()->selectCollection('energy_readings');
        $energyCollection->dropIndex('energy_readings_device_created_at');
        $energyCollection->dropIndex('energy_readings_device_sampled_at');

        $collection = DB::connection('mongodb')->getMongoDB()->selectCollection('monthly_bill_history');
        $collection->dropIndex('monthly_bill_history_device_period_unique');
        $collection->dropIndex('monthly_bill_history_period');
    }
};
