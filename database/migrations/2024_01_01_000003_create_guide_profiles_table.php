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
        Schema::create('guide_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('introduction')->nullable();
            $table->text('available_areas')->nullable()->comment('JSON形式で保存');
            $table->text('available_days')->nullable()->comment('JSON形式で保存');
            $table->text('available_times')->nullable()->comment('JSON形式で保存');
            $table->string('employee_number', 100)->nullable();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_profiles');
    }
};






