<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_profiles', function (Blueprint $table) {
            $table->boolean('filter_requests_by_availability')
                ->default(false)
                ->after('preferred_work_hours')
                ->comment('trueのとき、対応可能枠と重なる依頼のみ通知・一覧表示');
        });

        Schema::create('guide_availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_availability_slots');

        Schema::table('guide_profiles', function (Blueprint $table) {
            $table->dropColumn('filter_requests_by_availability');
        });
    }
};
