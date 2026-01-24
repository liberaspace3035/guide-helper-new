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
        Schema::create('email_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type')->unique()->comment('通知タイプ');
            $table->boolean('is_enabled')->default(true)->comment('有効フラグ');
            $table->integer('reminder_days')->nullable()->comment('リマインダー日数');
            $table->timestamps();
            
            $table->index('notification_type');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_notification_settings');
    }
};
