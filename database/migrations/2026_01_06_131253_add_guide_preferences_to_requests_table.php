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
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'guide_gender')) {
                $table->enum('guide_gender', ['none', 'male', 'female'])->nullable()->after('formatted_notes')->comment('希望するガイドの性別');
            }
            if (!Schema::hasColumn('requests', 'guide_age')) {
                $table->enum('guide_age', ['none', '20s', '30s', '40s', '50s', '60s'])->nullable()->after('guide_gender')->comment('希望するガイドの年代');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['guide_gender', 'guide_age']);
        });
    }
};
