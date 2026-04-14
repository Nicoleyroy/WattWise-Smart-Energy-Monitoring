<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->integer('plug_number');
            $table->text('message');
            $table->enum('status', ['warning', 'critical'])->default('warning');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_alerts');
    }
};
