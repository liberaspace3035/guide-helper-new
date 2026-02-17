<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 外出・自宅それぞれに限度時間を管理するため、request_type を追加する。
     */
    public function up(): void
    {
        Schema::table('user_monthly_limits', function (Blueprint $table) {
            $table->string('request_type', 10)->default('outing')->after('month')->comment('依頼種別: outing=外出, home=自宅');
        });

        // 旧ユニークを外して新ユニークを追加
        Schema::table('user_monthly_limits', function (Blueprint $table) {
            $table->dropUnique('user_monthly_limits_unique');
        });
        Schema::table('user_monthly_limits', function (Blueprint $table) {
            $table->unique(['user_id', 'year', 'month', 'request_type'], 'user_monthly_limits_user_year_month_type_unique');
        });

        // 既存の (user_id, year, month) ごとに自宅(home)用の行を追加
        $rows = DB::table('user_monthly_limits')->where('request_type', 'outing')->get();
        $now = now();
        foreach ($rows as $row) {
            DB::table('user_monthly_limits')->insert([
                'user_id' => $row->user_id,
                'year' => $row->year,
                'month' => $row->month,
                'request_type' => 'home',
                'limit_hours' => 0,
                'used_hours' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_monthly_limits', function (Blueprint $table) {
            $table->dropUnique('user_monthly_limits_user_year_month_type_unique');
        });

        // 自宅分の行を削除し、外出のみ残す
        DB::table('user_monthly_limits')->where('request_type', 'home')->delete();

        Schema::table('user_monthly_limits', function (Blueprint $table) {
            $table->dropColumn('request_type');
            $table->unique(['user_id', 'year', 'month'], 'user_monthly_limits_unique');
        });
    }
};
