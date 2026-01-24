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
        // 既存の管理者アカウントをチェック
        $adminExists = User::where('email', 'admin@example.com')->exists();
        
        if (!$adminExists) {
            User::create([
                'email' => 'admin@example.com',
                'password_hash' => Hash::make('admin123'),
                'name' => '管理者',
                'role' => 'admin',
                'is_allowed' => true,
            ]);
            
            $this->command->info('管理者アカウントを作成しました:');
            $this->command->info('メールアドレス: admin@example.com');
            $this->command->info('パスワード: admin123');
        } else {
            $this->command->warn('管理者アカウントは既に存在します。');
        }
    }
}
