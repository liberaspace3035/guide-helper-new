<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('prefecture', 20)->nullable()->comment('都道府県（自動分割または手入力）');
            $table->string('place')->nullable()->comment('会場・住所（都道府県以外含む）');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('url', 2048)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submitter_email')->nullable()->comment('非会員登録時のメール');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_token', 64)->nullable()->index();
            $table->timestamp('verification_token_expires_at')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending, published, cancelled');
            $table->timestamps();

            $table->index(['status', 'start_at']);
        });

        Schema::create('personal_calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('title');
            $table->string('prefecture', 20)->nullable();
            $table->string('place')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('url', 2048)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_calendar_entries');
        Schema::dropIfExists('events');
    }
};
