<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminResetPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password
                            {--email= : 管理者のメールアドレス（省略時は最初の管理者を対象）}
                            {--password= : 新しいパスワード（省略時は対話または ADMIN_NEW_PASSWORD 環境変数）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '本番環境などで管理者のパスワードを変更する（Railway 等シェルなし環境では ADMIN_NEW_PASSWORD を設定して Run Command で実行）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password');

        $admin = $email
            ? User::where('role', 'admin')->where('email', $email)->first()
            : User::where('role', 'admin')->first();

        if (!$admin) {
            $this->error('管理者が見つかりません。' . ($email ? "メールアドレス: {$email}" : '（管理者ユーザーが存在しません）'));
            return self::FAILURE;
        }

        if (!$password) {
            // シェルがない環境（Railway 等）: 環境変数 ADMIN_NEW_PASSWORD から取得
            $password = env('ADMIN_NEW_PASSWORD');
            if ($password !== null && $password !== '') {
                $this->comment('ADMIN_NEW_PASSWORD 環境変数を使用します。実行後は変数を削除してください。');
            } elseif ($this->input->isInteractive()) {
                $password = $this->secret('新しいパスワードを入力（6文字以上）');
                $confirm = $this->secret('新しいパスワードを再入力');
                if ($password !== $confirm) {
                    $this->error('パスワードが一致しません。');
                    return self::FAILURE;
                }
            } else {
                $this->error('パスワードを指定してください。');
                $this->line('  --password=新しいパスワード');
                $this->line('  または Railway の Variables に ADMIN_NEW_PASSWORD を設定してから Run Command で実行し、完了後に変数を削除してください。');
                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => 'required|min:6']
        );
        if ($validator->fails()) {
            $this->error('パスワードは6文字以上で入力してください。');
            return self::FAILURE;
        }

        $admin->password_hash = Hash::make($password);
        $admin->save();

        $this->info('管理者のパスワードを変更しました: ' . $admin->email);
        return self::SUCCESS;
    }
}
