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
        // PostgreSQLではtext型からjson型への変換にUSING句が必要
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE guide_profiles ALTER COLUMN available_areas TYPE json USING available_areas::json');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE guide_profiles ALTER COLUMN available_days TYPE json USING available_days::json');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE guide_profiles ALTER COLUMN available_times TYPE json USING available_times::json');
        
        Schema::table('guide_profiles', function (Blueprint $table) {
            // 新しいカラムを追加
            $table->text('application_reason')->nullable()->after('employee_number')->comment('応募理由');
            $table->text('goal')->nullable()->after('application_reason')->comment('実現したいこと');
            $table->json('qualifications')->nullable()->after('goal')->comment('保有資格（JSON形式）');
            $table->text('preferred_work_hours')->nullable()->after('qualifications')->comment('希望勤務時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guide_profiles', function (Blueprint $table) {
            // 追加したカラムを削除
            $table->dropColumn([
                'application_reason',
                'goal',
                'qualifications',
                'preferred_work_hours',
            ]);
        });
        
        // json型をtext型に戻す
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE guide_profiles ALTER COLUMN available_areas TYPE text USING available_areas::text');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE guide_profiles ALTER COLUMN available_days TYPE text USING available_days::text');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE guide_profiles ALTER COLUMN available_times TYPE text USING available_times::text');
    }
};
