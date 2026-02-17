<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SQLインジェクション攻撃のテストケース
     * 
     * このテストは、ログイン処理がSQLインジェクション攻撃に対して
     * 適切に防御されていることを確認します。
     */

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用のユーザーを作成
        User::create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'name' => 'Test User',
            'role' => 'user',
            'is_allowed' => true,
        ]);
    }

    /**
     * 基本的なSQLインジェクション攻撃（OR 1=1）
     */
    public function test_sql_injection_or_1_equals_1_in_email(): void
    {
        $response = $this->post('/login', [
            'email' => "test@example.com' OR '1'='1",
            'password' => 'password123',
        ]);

        // ログインに失敗することを確認（SQLインジェクションが防がれている）
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * SQLインジェクション攻撃（OR 1=1）パスワードフィールド
     */
    public function test_sql_injection_or_1_equals_1_in_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => "' OR '1'='1",
        ]);

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * SQLインジェクション攻撃（UNION SELECT）
     */
    public function test_sql_injection_union_select_in_email(): void
    {
        $response = $this->post('/login', [
            'email' => "test@example.com' UNION SELECT * FROM users--",
            'password' => 'password123',
        ]);

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * SQLインジェクション攻撃（コメントアウト）
     */
    public function test_sql_injection_comment_in_email(): void
    {
        $response = $this->post('/login', [
            'email' => "test@example.com'--",
            'password' => 'password123',
        ]);

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * SQLインジェクション攻撃（セミコロン）
     */
    public function test_sql_injection_semicolon_in_email(): void
    {
        $response = $this->post('/login', [
            'email' => "test@example.com'; DROP TABLE users--",
            'password' => 'password123',
        ]);

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        
        // テーブルが削除されていないことを確認
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * SQLインジェクション攻撃（AND 1=1）
     */
    public function test_sql_injection_and_1_equals_1_in_email(): void
    {
        $response = $this->post('/login', [
            'email' => "test@example.com' AND '1'='1",
            'password' => 'password123',
        ]);

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * SQLインジェクション攻撃（LIKE演算子）
     */
    public function test_sql_injection_like_in_email(): void
    {
        $response = $this->post('/login', [
            'email' => "test@example.com' OR email LIKE '%",
            'password' => 'password123',
        ]);

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * SQLインジェクション攻撃（時間ベースのブラインドSQLインジェクション）
     */
    public function test_sql_injection_time_based_blind(): void
    {
        $startTime = microtime(true);
        
        $response = $this->post('/login', [
            'email' => "test@example.com'; WAITFOR DELAY '00:00:05'--",
            'password' => 'password123',
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        
        // 実行時間が短いことを確認（SQLインジェクションが実行されていない）
        $this->assertLessThan(2, $executionTime, 'SQLインジェクションが実行された可能性があります');
    }

    /**
     * SQLインジェクション攻撃（エラーベースのSQLインジェクション）
     */
    public function test_sql_injection_error_based(): void
    {
        $response = $this->post('/login', [
            'email' => "test@example.com' AND (SELECT * FROM (SELECT COUNT(*),CONCAT(version(),FLOOR(RAND(0)*2))x FROM information_schema.tables GROUP BY x)a)--",
            'password' => 'password123',
        ]);

        // ログインに失敗することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * 正常なログインが動作することを確認
     */
    public function test_normal_login_works(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 正常なログインが成功することを確認
        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * 特殊文字を含む正常なメールアドレスでもログインできることを確認
     */
    public function test_login_with_special_characters_in_email(): void
    {
        // 特殊文字を含むメールアドレスのユーザーを作成
        User::create([
            'email' => 'test+user@example.com',
            'password_hash' => Hash::make('password123'),
            'name' => 'Test User',
            'role' => 'user',
            'is_allowed' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'test+user@example.com',
            'password' => 'password123',
        ]);

        // 正常なログインが成功することを確認
        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}

