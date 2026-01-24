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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->timestamp('interview_date_1')->nullable()->after('introduction')->comment('面談希望日時（第1希望）');
            $table->timestamp('interview_date_2')->nullable()->after('interview_date_1')->comment('面談希望日時（第2希望）');
            $table->timestamp('interview_date_3')->nullable()->after('interview_date_2')->comment('面談希望日時（第3希望）');
            $table->text('application_reason')->nullable()->after('interview_date_3')->comment('応募のきっかけ');
            $table->text('visual_disability_status')->nullable()->after('application_reason')->comment('視覚障害の状況');
            $table->string('disability_support_level', 10)->nullable()->after('visual_disability_status')->comment('障害支援区分');
            $table->text('daily_life_situation')->nullable()->after('disability_support_level')->comment('普段の生活状況');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'interview_date_1',
                'interview_date_2',
                'interview_date_3',
                'application_reason',
                'visual_disability_status',
                'disability_support_level',
                'daily_life_situation',
            ]);
        });
    }
};
