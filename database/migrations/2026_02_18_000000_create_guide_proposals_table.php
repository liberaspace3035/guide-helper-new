<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ガイドが利用者に外出・自宅支援を提案するためのテーブル
     */
    public function up(): void
    {
        Schema::create('guide_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('request_type', 10)->comment('outing=外出支援, home=自宅支援');
            $table->date('proposed_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('service_content')->nullable()->comment('支援内容');
            $table->text('message')->nullable()->comment('利用者へのメッセージ');
            $table->string('prefecture', 10)->nullable();
            $table->text('destination_address')->nullable()->comment('住所（自宅の場合は任意）');
            $table->text('meeting_place')->nullable()->comment('待ち合わせ場所（外出の場合）');
            $table->string('status', 20)->default('pending')->comment('pending, accepted, rejected');
            $table->timestamps();

            $table->index(['guide_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_proposals');
    }
};
