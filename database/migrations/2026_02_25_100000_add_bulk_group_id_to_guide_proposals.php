<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 一斉提案を1件にまとめて表示するためのグループID
     */
    public function up(): void
    {
        Schema::table('guide_proposals', function (Blueprint $table) {
            $table->string('bulk_group_id', 36)->nullable()->after('guide_id')
                ->comment('一斉提案時に同一グループを識別するUUID');
            $table->index('bulk_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('guide_proposals', function (Blueprint $table) {
            $table->dropIndex(['bulk_group_id']);
            $table->dropColumn('bulk_group_id');
        });
    }
};
