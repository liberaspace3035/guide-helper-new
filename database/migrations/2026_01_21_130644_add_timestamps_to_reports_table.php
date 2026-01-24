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
        Schema::table('reports', function (Blueprint $table) {
            // タイムスタンプカラムを追加
            $table->timestamp('user_approved_at')->nullable()->after('approved_at')->comment('ユーザー承認日時');
            $table->timestamp('admin_approved_at')->nullable()->after('user_approved_at')->comment('管理者承認日時');
        });
        
        // statusカラムのCHECK制約を更新してuser_approvedとadmin_approvedを追加
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE reports 
            DROP CONSTRAINT IF EXISTS reports_status_check
        ");
        
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE reports 
            ADD CONSTRAINT reports_status_check 
            CHECK (status IN ('draft', 'submitted', 'user_approved', 'admin_approved', 'approved', 'revision_requested'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['user_approved_at', 'admin_approved_at']);
        });
        
        // CHECK制約を元に戻す
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE reports 
            DROP CONSTRAINT IF EXISTS reports_status_check
        ");
        
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE reports 
            ADD CONSTRAINT reports_status_check 
            CHECK (status IN ('draft', 'submitted', 'approved', 'revision_requested'))
        ");
    }
};
