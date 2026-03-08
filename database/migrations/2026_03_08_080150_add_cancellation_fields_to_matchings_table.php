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
        Schema::table('matchings', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('status')->comment('キャンセル日時');
            $table->enum('cancelled_by', ['user', 'guide', 'admin'])->nullable()->after('cancelled_at')->comment('キャンセルした側');
            $table->index('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matchings', function (Blueprint $table) {
            $table->dropIndex(['cancelled_at']);
            $table->dropColumn(['cancelled_at', 'cancelled_by']);
        });
    }
};
