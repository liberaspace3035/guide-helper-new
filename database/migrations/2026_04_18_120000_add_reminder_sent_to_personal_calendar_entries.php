<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_calendar_entries', function (Blueprint $table) {
            $table->timestamp('reminder_30min_sent_at')->nullable()->after('description');
            $table->timestamp('reminder_day_before_sent_at')->nullable()->after('reminder_30min_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('personal_calendar_entries', function (Blueprint $table) {
            $table->dropColumn(['reminder_30min_sent_at', 'reminder_day_before_sent_at']);
        });
    }
};
