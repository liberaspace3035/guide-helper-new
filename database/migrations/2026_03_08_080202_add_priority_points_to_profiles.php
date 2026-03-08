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
        // ユーザープロフィールに重視ポイントを追加
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->json('priority_points')->nullable()->after('introduction')->comment('ガイド中に重視するポイント（最大2つ）');
            $table->string('priority_points_other', 255)->nullable()->after('priority_points')->comment('その他（自由記入）');
        });

        // ガイドプロフィールに重視ポイントを追加
        Schema::table('guide_profiles', function (Blueprint $table) {
            $table->json('priority_points')->nullable()->after('introduction')->comment('ガイド中に重視するポイント（最大2つ）');
            $table->string('priority_points_other', 255)->nullable()->after('priority_points')->comment('その他（自由記入）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['priority_points', 'priority_points_other']);
        });

        Schema::table('guide_profiles', function (Blueprint $table) {
            $table->dropColumn(['priority_points', 'priority_points_other']);
        });
    }
};
