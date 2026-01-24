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
        // PostgreSQL用: 既存データの変換（日本語 → 英語コード値）
        // まず一時カラムを追加してデータを変換
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests ADD COLUMN IF NOT EXISTS request_type_temp VARCHAR(10) NULL");
        \Illuminate\Support\Facades\DB::statement("UPDATE requests SET request_type_temp = CASE WHEN request_type = '外出' THEN 'outing' WHEN request_type = '自宅' THEN 'home' ELSE NULL END");
        
        // PostgreSQL用: ENUMをVARCHAR + CHECK制約に変更
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_request_type_check");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests ALTER COLUMN request_type TYPE VARCHAR(20)");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests ADD CONSTRAINT requests_request_type_check CHECK (request_type IN ('outing', 'home'))");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests ALTER COLUMN request_type SET NOT NULL");
        
        // 変換したデータをコピー
        \Illuminate\Support\Facades\DB::statement("UPDATE requests SET request_type = request_type_temp WHERE request_type_temp IS NOT NULL");
        
        // 一時カラムを削除
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests DROP COLUMN IF EXISTS request_type_temp");
        
        // コメントを追加
        \Illuminate\Support\Facades\DB::statement("COMMENT ON COLUMN requests.request_type IS '依頼タイプ'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PostgreSQL用: 既存データを英語コード値から日本語に戻す
        // まず一時カラムを追加してデータを変換
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests ADD COLUMN IF NOT EXISTS request_type_temp VARCHAR(10) NULL");
        \Illuminate\Support\Facades\DB::statement("UPDATE requests SET request_type_temp = CASE WHEN request_type = 'outing' THEN '外出' WHEN request_type = 'home' THEN '自宅' ELSE NULL END");
        
        // PostgreSQL用: CHECK制約を日本語値に変更
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests DROP CONSTRAINT IF EXISTS requests_request_type_check");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests ADD CONSTRAINT requests_request_type_check CHECK (request_type IN ('外出', '自宅'))");
        
        // 変換したデータをコピー
        \Illuminate\Support\Facades\DB::statement("UPDATE requests SET request_type = request_type_temp WHERE request_type_temp IS NOT NULL");
        
        // 一時カラムを削除
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests DROP COLUMN IF EXISTS request_type_temp");
    }
};
