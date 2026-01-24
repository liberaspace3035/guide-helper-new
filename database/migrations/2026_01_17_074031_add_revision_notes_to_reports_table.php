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
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'revision_notes')) {
                $table->text('revision_notes')->nullable()->comment('修正依頼内容');
            }
        });
        
        // PostgreSQL用: コメントを追加
        if (Schema::hasColumn('reports', 'revision_notes')) {
            \Illuminate\Support\Facades\DB::statement("COMMENT ON COLUMN reports.revision_notes IS '修正依頼内容'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('revision_notes');
        });
    }
};
