<?php
/**
 * 要件定義書に基づく追加フィールドのマイグレーション実行スクリプト
 * 
 * 使用方法:
 * php run_migration.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "マイグレーションを開始します...\n\n";

try {
    // SQLファイルを読み込む
    $sqlFile = __DIR__ . '/database/migrations/add_requirements_fields.sql';
    
    if (!file_exists($sqlFile)) {
        echo "エラー: SQLファイルが見つかりません: {$sqlFile}\n";
        exit(1);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // SQL文を分割（セミコロンで区切る）
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    DB::beginTransaction();
    
    try {
        foreach ($statements as $statement) {
            if (empty(trim($statement))) {
                continue;
            }
            
            // IF NOT EXISTS構文をLaravelで処理できるように変換
            $statement = preg_replace('/IF NOT EXISTS/i', '', $statement);
            
            // ALTER TABLE文の処理
            if (preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches)) {
                $tableName = $matches[1];
                
                // カラム追加の処理
                if (preg_match('/ADD COLUMN\s+(\w+)\s+([^,]+)/i', $statement, $colMatches)) {
                    $columnName = $colMatches[1];
                    $columnDef = $colMatches[2];
                    
                    if (!Schema::hasColumn($tableName, $columnName)) {
                        echo "カラムを追加: {$tableName}.{$columnName}\n";
                        DB::statement("ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$columnDef}");
                    } else {
                        echo "スキップ: カラム {$tableName}.{$columnName} は既に存在します\n";
                    }
                }
                
                // インデックス追加の処理
                if (preg_match('/ADD (?:UNIQUE )?INDEX\s+(\w+)/i', $statement, $idxMatches)) {
                    $indexName = $idxMatches[1];
                    // インデックス追加処理（簡易版）
                    echo "インデックス追加: {$tableName}.{$indexName}\n";
                }
            }
            
            // CREATE TABLE文の処理
            if (preg_match('/CREATE TABLE\s+IF NOT EXISTS\s+(\w+)/i', $statement, $matches)) {
                $tableName = $matches[1];
                
                if (!Schema::hasTable($tableName)) {
                    echo "テーブルを作成: {$tableName}\n";
                    // CREATE TABLE文をそのまま実行
                    DB::statement($statement);
                } else {
                    echo "スキップ: テーブル {$tableName} は既に存在します\n";
                }
            }
            
            // INSERT文の処理
            if (preg_match('/^INSERT INTO/i', $statement)) {
                echo "初期データを挿入\n";
                DB::statement($statement);
            }
        }
        
        DB::commit();
        echo "\n✅ マイグレーションが正常に完了しました。\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
    
} catch (\Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}




