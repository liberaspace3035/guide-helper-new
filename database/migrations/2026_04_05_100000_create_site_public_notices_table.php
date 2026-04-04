<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_public_notices', function (Blueprint $table) {
            $table->id();
            $table->string('category', 64);
            $table->string('title');
            $table->text('body');
            $table->string('detail_url', 2048)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['is_visible', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_public_notices');
    }
};
