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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique()->comment('テンプレートキー');
            $table->string('subject')->comment('件名');
            $table->text('body')->comment('本文');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();
            
            $table->index('template_key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
