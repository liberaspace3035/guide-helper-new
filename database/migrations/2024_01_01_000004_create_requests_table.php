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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('request_type', ['外出', '自宅'])->default('外出');
            $table->text('destination_address')->comment('詳細な住所（マスキング前）');
            $table->string('masked_address', 255)->nullable()->comment('マスキング後の住所');
            $table->text('meeting_place')->nullable()->comment('待ち合わせ場所（外出依頼の場合のみ）');
            $table->text('service_content');
            $table->date('request_date');
            $table->time('request_time');
            $table->time('start_time')->nullable()->comment('開始時刻');
            $table->time('end_time')->nullable()->comment('終了時刻');
            $table->integer('duration')->nullable()->comment('所要時間（分）');
            $table->text('notes')->nullable()->comment('音声入力やAI整形前のテキスト');
            $table->text('formatted_notes')->nullable()->comment('AI整形後のテキスト');
            $table->enum('guide_gender', ['none', 'male', 'female'])->nullable()->comment('希望するガイドの性別');
            $table->enum('guide_age', ['none', '20s', '30s', '40s', '50s', '60s'])->nullable()->comment('希望するガイドの年代');
            $table->enum('status', ['pending', 'guide_accepted', 'matched', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('request_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};

