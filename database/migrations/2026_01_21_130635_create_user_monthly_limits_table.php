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
        Schema::create('user_monthly_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('year')->comment('年');
            $table->integer('month')->comment('月');
            $table->decimal('limit_hours', 10, 2)->default(0)->comment('限度時間（時間）');
            $table->decimal('used_hours', 10, 2)->default(0)->comment('使用時間（時間）');
            $table->timestamps();
            
            $table->unique(['user_id', 'year', 'month'], 'user_monthly_limits_unique');
            $table->index('user_id');
            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_monthly_limits');
    }
};
