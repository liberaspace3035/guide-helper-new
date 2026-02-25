<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ガイド提案機能の利用可否と、提案画面での氏名表示設定を user_profiles に追加
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->boolean('accept_guide_proposals')->default(true)->after('introduction')
                ->comment('ガイドの提案機能で提案を受け取るか');
            $table->boolean('show_name_in_proposals')->default(false)->after('accept_guide_proposals')
                ->comment('ガイドの提案画面で利用者氏名を表示するか');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['accept_guide_proposals', 'show_name_in_proposals']);
        });
    }
};
