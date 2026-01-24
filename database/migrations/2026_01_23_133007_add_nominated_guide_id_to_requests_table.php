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
            $table->foreignId('nominated_guide_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null')->comment('指名ガイドID（任意）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['nominated_guide_id']);
            $table->dropColumn('nominated_guide_id');
        });
    }
};
