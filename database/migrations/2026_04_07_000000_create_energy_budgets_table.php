<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('energy_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->index(); // e.g. "1" or "PLUG1"
            $table->float('threshold_value'); // The limit (in whatever unit of 'energy' typically kWh)
            $table->enum('threshold_type', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_budgets');
    }
};
