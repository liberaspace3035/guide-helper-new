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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('name', 100);
            $table->string('last_name', 50)->nullable()->comment('姓');
            $table->string('first_name', 50)->nullable()->comment('名');
            $table->string('last_name_kana', 50)->nullable()->comment('姓カナ');
            $table->string('first_name_kana', 50)->nullable()->comment('名カナ');
            $table->integer('age')->nullable()->comment('年齢');
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->comment('性別');
            $table->text('address')->nullable()->comment('住所');
            $table->string('phone', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('role', ['user', 'guide', 'admin'])->default('user');
            $table->boolean('is_allowed')->default(false);
            $table->timestamps();
            
            $table->index('email');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};






