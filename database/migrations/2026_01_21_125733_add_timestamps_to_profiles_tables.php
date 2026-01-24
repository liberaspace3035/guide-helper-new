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
            $table->timestamps();
        });
        
        Schema::table('guide_profiles', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_profiles', 'created_at')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->dropTimestamps();
            });
        }
        
        if (Schema::hasColumn('guide_profiles', 'created_at')) {
            Schema::table('guide_profiles', function (Blueprint $table) {
                $table->dropTimestamps();
            });
        }
    }
};
