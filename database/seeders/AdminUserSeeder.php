<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 環境変数から管理者情報を取得（デフォルト値あり）
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123456');
        $adminName = env('ADMIN_NAME', '管理者');
        
        // 既存の管理者アカウントをチェック
        $adminExists = User::where('email', $adminEmail)->exists();
        
        if (!$adminExists) {
            User::create([
                'email' => $adminEmail,
                'password_hash' => Hash::make($adminPassword),
                'name' => $adminName,
                'role' => 'admin',
                'is_allowed' => true,
            ]);
            
            $this->command->info('管理者アカウントを作成しました:');
            $this->command->info('メールアドレス: ' . $adminEmail);
            $this->command->info('パスワード: ' . $adminPassword);
            $this->command->info('名前: ' . $adminName);
        } else {
            $this->command->warn('管理者アカウント（' . $adminEmail . '）は既に存在します。');
        }
    }
}
