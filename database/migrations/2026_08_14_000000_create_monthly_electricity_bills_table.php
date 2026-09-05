<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_electricity_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->date('billing_month');
            $table->decimal('baseline_kwh', 12, 3);
            $table->decimal('rate_per_kwh', 12, 4);
            $table->timestamps();
            $table->unique(['device_id', 'billing_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_electricity_bills');
    }
};
