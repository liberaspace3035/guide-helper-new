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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade')->comment('関連する報告書');
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade')->comment('評価者');
            $table->foreignId('rated_id')->constrained('users')->onDelete('cascade')->comment('評価される側');
            $table->enum('rater_type', ['guide', 'user'])->comment('評価者のタイプ（ガイド or 利用者）');
            $table->tinyInteger('score')->comment('評価スコア: 1=改善が必要, 2=普通, 3=良い');
            $table->text('comment')->comment('評価コメント');
            $table->timestamps();
            
            // 1つの報告書で評価者は1回のみ評価可能
            $table->unique(['report_id', 'rater_id'], 'ratings_report_rater_unique');
            $table->index('rater_id');
            $table->index('rated_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
