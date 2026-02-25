<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 継続ルール: 「○年○月から」毎月この限度時間をデフォルトで適用する。
     * 月別に user_monthly_limits で上書きされていなければこの値が使われる。
     */
    public function up(): void
    {
        Schema::create('user_monthly_limit_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('request_type', 10)->default('outing')->comment('依頼種別: outing=外出, home=自宅');
            $table->date('effective_from')->comment('適用開始日（月初の日付）');
            $table->decimal('limit_hours', 10, 2)->default(0)->comment('限度時間（時間）');
            $table->timestamps();

            $table->unique(['user_id', 'request_type', 'effective_from'], 'user_limit_rules_user_type_from_unique');
            $table->index(['user_id', 'request_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_monthly_limit_rules');
    }
};
