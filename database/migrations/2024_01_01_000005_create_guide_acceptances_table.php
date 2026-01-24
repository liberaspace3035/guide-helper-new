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
        Schema::create('guide_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->onDelete('cascade');
            $table->foreignId('guide_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'declined', 'matched', 'rejected'])->default('pending');
            $table->enum('admin_decision', ['auto', 'approved', 'rejected', 'pending'])->default('pending');
            $table->boolean('user_selected')->default(false)->comment('ユーザーが選択したガイド');
            $table->timestamps();
            
            $table->index('request_id');
            $table->index('guide_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_acceptances');
    }
};

