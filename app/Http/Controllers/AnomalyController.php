<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AnomalyController extends Controller
{

    public function detect(Request $request)
    {

        $powerValues = $request->input('power_data');

        $count = count($powerValues);

        if($count == 0){
            return response()->json(['error' => 'No data']);
        }

        // Mean
        $mean = array_sum($powerValues) / $count;

        // Variance
        $variance = 0;

        foreach($powerValues as $value){
            $variance += pow($value - $mean, 2);
        }

        $variance = $variance / $count;

        // Standard deviation
        $stdDev = sqrt($variance);

        // Latest reading
        $latest = end($powerValues);

        $zScore = ($latest - $mean) / $stdDev;

        $anomaly = abs($zScore) > 3;

        return response()->json([
            'mean' => $mean,
            'std_dev' => $stdDev,
            'latest_power' => $latest,
            'z_score' => $zScore,
            'anomaly' => $anomaly
        ]);

    }

}