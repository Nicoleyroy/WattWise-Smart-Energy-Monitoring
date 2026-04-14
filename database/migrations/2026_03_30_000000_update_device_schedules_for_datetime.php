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
        Schema::table('device_schedules', function (Blueprint $table) {
            // Add new columns
            $table->dateTime('scheduled_at')->nullable()->after('name');
            $table->string('action')->default('off')->after('scheduled_at'); // 'on' or 'off'

            // Drop old columns
            $table->dropColumn(['description', 'start_time', 'end_time', 'days']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_schedules', function (Blueprint $table) {
            // Restore old columns
            $table->string('description')->nullable()->after('name');
            $table->string('start_time')->after('description');
            $table->string('end_time')->after('start_time');
            $table->json('days')->after('end_time');

            // Drop new columns
            $table->dropColumn(['scheduled_at', 'action']);
        });
    }
};
