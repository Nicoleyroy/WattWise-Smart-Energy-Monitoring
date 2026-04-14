<?php

namespace App\Http\Controllers;

use App\Models\EnergyReading;
use App\Models\DeviceSchedule;
use App\Models\MaintenanceAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class BackupController extends Controller
{
    public function download(Request $request)
    {
        // For security, you might want to scope these queries to the authenticated user
        // However, if the system is global, we can just fetch all or filter by user if applicable.
        // Assuming global data or user context hasn't been strictly partitioned for readings yet.
        $readings = EnergyReading::all();
        $schedules = DeviceSchedule::all();
        
        // Let's verify if MaintenanceAlert exists, we saw it in migrations. If it's not a model, we can handle it.
        // I will assume it exists based on the migration `create_maintenance_alerts_table.php`
        
        // Wait, did I see MaintenanceAlert in the models list? 
        // Yes, `MaintenanceAlert.php` was in the output of `ls app/Models`.
        $alerts = MaintenanceAlert::all();

        $backupData = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'data' => [
                'energy_readings' => $readings,
                'device_schedules' => $schedules,
                'maintenance_alerts' => $alerts,
            ]
        ];

        $json = json_encode($backupData, JSON_PRETTY_PRINT);
        
        $filename = 'wattwise_backup_' . now()->format('Y_m_d_His') . '.json';

        return Response::make($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
