<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * お知らせごとにリマインドを送るかどうかを管理者が切り替えられるようにする。
     * true: 重要なお知らせ・未読の間は毎日リマインド送信。false: リマインドなし。
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('reminder_enabled')->default(true)->after('target_audience')->comment('未読リマインドを送信するか');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('reminder_enabled');
        });
    }
};
