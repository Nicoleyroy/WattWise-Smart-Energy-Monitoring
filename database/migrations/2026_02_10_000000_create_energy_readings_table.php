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
        Schema::create('energy_readings', function (Blueprint $table) {
            $table->id();
            $table->decimal('voltage', 8, 2)->nullable();
            $table->decimal('current', 8, 2)->nullable();
            $table->decimal('power', 10, 2)->nullable();
            $table->decimal('energy', 10, 4)->nullable();
            $table->decimal('frequency', 6, 2)->nullable();
            $table->decimal('power_factor', 5, 3)->nullable();
            $table->string('device_id')->nullable();
            $table->timestamps();
            
            // Add indexes for common queries
            $table->index('device_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_readings');
    }
};
