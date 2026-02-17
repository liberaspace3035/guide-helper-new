<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\GuideProfile;
use App\Models\Notification;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // 共通バリデーション
        $rules = [
            'email' => 'required|email|unique:users,email',
            'email_confirmation' => 'required|email|same:email',
            'password' => 'required|min:6',
            'confirmPassword' => 'required|same:password',
            'last_name' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'last_name_kana' => 'required|string|max:50|regex:/^[ァ-ヶー\s]+$/u',
            'first_name_kana' => 'required|string|max:50|regex:/^[ァ-ヶー\s]+$/u',
            'postal_code' => 'required|string|regex:/^\d{3}-\d{4}$/',
            'address' => 'required|string',
            'phone' => 'required|string|regex:/^[\d\-\+\(\)\s]+$/',
            'gender' => 'required|in:male,female,other,prefer_not_to_say',
            'birth_date' => [
                'required',
                'date',
                'before:today',
                function ($attribute, $value, $fail) {
                    $age = Carbon::parse($value)->age;
                    if ($age < 18) {
                        $fail('生年月日から計算した年齢は18歳以上である必要があります。');
                    }
                    if ($age > 120) {
                        $fail('生年月日から計算した年齢は120歳以下である必要があります。');
                    }
                },
            ],
            'role' => 'required|in:user,guide',
        ];

        // ロール別の追加バリデーション
        if ($request->role === 'user') {
            $rules = array_merge($rules, [
                'interview_date_1' => 'required|date',
                'interview_date_2' => 'nullable|date',
                'interview_date_3' => 'nullable|date',
                'application_reason' => 'required|string',
                'visual_disability_status' => 'required|string',
                'disability_support_level' => 'required|string|in:１,２,３,４,５,６,なし',
                'daily_life_situation' => 'required|string',
            ]);
        } else if ($request->role === 'guide') {
            $rules = array_merge($rules, [
                'application_reason' => 'required|string',
                'goal' => 'required|string',
                'qualifications' => 'required|array|max:3',
                'qualifications.*.name' => 'required|string',
                'qualifications.*.obtained_date' => 'required|date',
                'preferred_work_hours' => 'required|string',
            ]);
        }

        $validator = Validator::make($request->all(), $rules, [
            'last_name_kana.regex' => '姓（カナ）は全角カタカナで入力してください。名前の読み方の部分です。',
            'first_name_kana.regex' => '名（カナ）は全角カタカナで入力してください。名前の読み方の部分です。',
            'birth_date.required' => '生年月日を入力してください。',
            'birth_date.date' => '生年月日は正しい日付を入力してください。',
            'birth_date.before' => '生年月日は未来の日付にはできません。今日より前の日付を入力してください。',
            'disability_support_level.required' => '障害支援区分を選択してください。',
            'disability_support_level.in' => '障害支援区分は選択肢から選んでください。',
        ]);

        if ($validator->fails()) {
            // APIリクエストの場合はJSONレスポンス
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $fullName = $request->name ?? trim($request->last_name . ' ' . $request->first_name);

        // 生年月日から年齢を算出
        $age = null;
        if ($request->birth_date) {
            $today = new \DateTime();
            $birthDate = new \DateTime($request->birth_date);
            $age = $today->diff($birthDate)->y;
        }

        $user = User::create([
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'name' => $fullName,
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'last_name_kana' => $request->last_name_kana,
            'first_name_kana' => $request->first_name_kana,
            'birth_date' => $request->birth_date,
            'age' => $age ?? $request->age,
            'gender' => $request->gender,
            'address' => $request->address,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'role' => $request->role,
            'is_allowed' => false,
            'email_confirmed' => false,
        ]);

        if ($request->role === 'user') {
            UserProfile::create([
                'user_id' => $user->id,
                'interview_date_1' => $request->interview_date_1 ? new \DateTime($request->interview_date_1) : null,
                'interview_date_2' => $request->interview_date_2 ? new \DateTime($request->interview_date_2) : null,
                'interview_date_3' => $request->interview_date_3 ? new \DateTime($request->interview_date_3) : null,
                'application_reason' => $request->application_reason,
                'visual_disability_status' => $request->visual_disability_status,
                'disability_support_level' => $request->disability_support_level,
                'daily_life_situation' => $request->daily_life_situation,
            ]);
        } else if ($request->role === 'guide') {
            // qualificationsを配列からJSONに変換
            $qualifications = [];
            if ($request->has('qualifications') && is_array($request->qualifications)) {
                foreach ($request->qualifications as $qual) {
                    if (!empty($qual['name']) && !empty($qual['obtained_date'])) {
                        $qualifications[] = [
                            'name' => $qual['name'],
                            'obtained_date' => $qual['obtained_date'],
                        ];
                    }
                }
            }
            
            GuideProfile::create([
                'user_id' => $user->id,
                'application_reason' => $request->application_reason,
                'goal' => $request->goal,
                'qualifications' => $qualifications,
                'preferred_work_hours' => $request->preferred_work_hours,
            ]);
        }

        // 管理者全員に新規登録の通知（画面上＋メール）を送る
        $admins = User::where('role', 'admin')->get();
        $isGuide = $request->role === 'guide';
        $title = $isGuide ? '新規ガイドが登録されました' : '新規ユーザーが登録されました';
        $message = $fullName . ' さんが新規登録しました。' . ($isGuide ? 'ガイド管理' : 'ユーザー管理') . 'の承認待ち一覧で確認してください。';
        $emailService = app(EmailNotificationService::class);
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => $isGuide ? 'guide_registration' : 'user_registration',
                'title' => $title,
                'message' => $message,
                'related_id' => $user->id,
                'created_at' => now(),
            ]);
            $emailService->sendAdminNewRegistrationNotification($admin, $fullName, $isGuide);
        }

        // APIリクエストの場合はJSONレスポンスを返す
        if ($request->expectsJson() || $request->is('api/*')) {
            // APIリクエストの場合はログインしてJSONレスポンスを返す
            auth()->login($user);
            return response()->json([
                'message' => 'ユーザー登録が完了しました。審査完了後、運営からご連絡いたします。',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                ]
            ], 201);
        }

        // Webリクエストの場合はログインせずにログインページにリダイレクト
        return redirect()->route('login')
            ->with('success', 'ユーザー登録が完了しました。審査完了後、運営からご連絡いたします。');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            // APIリクエストの場合はJSONレスポンス
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::where('email', $request->email)->first();
        // dump($user);

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            // APIリクエストの場合はJSONレスポンス
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'メールアドレスまたはパスワードが正しくありません'
                ], 401);
            }
            return redirect()->back()
                ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません'])
                ->withInput();
        }

        if (!$user->is_allowed) {
            // APIリクエストの場合はJSONレスポンス
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'ユーザーは承認されていません'
                ], 401);
            }
            return redirect()->back()
                ->withErrors(['email' => 'ユーザーは承認されていません'])
                ->withInput();
        }

        // セッション認証でログイン
        auth()->login($user);
        $request->session()->regenerate();
        // dd($request->session()->all());
        
        // APIリクエストの場合はJSONレスポンスを返す
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'ログインに成功しました',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                ]
            ]);
        }

        // ロールに応じてリダイレクト
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('dashboard');
    }

    public function user(Request $request)
    {
        // セッション認証を使用
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => '認証が必要です'], 401);
        }
        
        if ($user->role === 'user') {
            $user->load('userProfile');
            $user->profile = $user->userProfile;
        } else if ($user->role === 'guide') {
            $user->load('guideProfile');
            $user->profile = $user->guideProfile;
            // GuideProfileモデルで'array'キャストが設定されているため、既に配列になっている
            // json_decode()は不要
            if ($user->profile) {
                $user->profile->available_areas = $user->profile->available_areas ?? [];
                $user->profile->available_days = $user->profile->available_days ?? [];
                $user->profile->available_times = $user->profile->available_times ?? [];
            }
        }

        return response()->json(['user' => $user]);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function apiLogout(Request $request)
    {
        try {
            // JWT認証の場合は、トークンを無効化する必要はない（ステートレス）
            // セッション認証の場合のみセッションを無効化
            if (auth()->check()) {
                auth()->logout();
            }
            
            // APIリクエストの場合はJSONレスポンスを返す
            return response()->json([
                'message' => 'ログアウトに成功しました'
            ]);
        } catch (\Exception $e) {
            \Log::error('ログアウトエラー: ' . $e->getMessage());
            return response()->json(['error' => 'ログアウト中にエラーが発生しました'], 500);
        }
    }

    /**
     * パスワードリセットリクエスト画面を表示
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * パスワードリセットリンクを送信
     */
    public function sendPasswordResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::where('email', $request->email)->first();

        // セキュリティのため、存在しないメールアドレスでも「送信しました」と表示
        if (!$user) {
            return redirect()->back()->with('status', 'パスワードリセットリンクを送信しました。メールをご確認ください。');
        }

        // リセットトークンを生成
        $token = Str::random(64);
        $hashedToken = Hash::make($token);

        // 既存のトークンを削除
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // 新しいトークンを保存
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $hashedToken,
            'created_at' => Carbon::now(),
        ]);

        // リセットリンクを生成
        $resetUrl = url('/password/reset/' . $token . '?email=' . urlencode($request->email));

        // メール送信
        try {
            $emailService = new EmailNotificationService();
            $emailService->sendPasswordResetNotification($user, $resetUrl);
        } catch (\Exception $e) {
            \Log::error('パスワードリセットメール送信エラー: ' . $e->getMessage());
            // エラーが発生してもユーザーには送信したと表示（セキュリティのため）
        }

        return redirect()->back()->with('status', 'パスワードリセットリンクを送信しました。メールをご確認ください。');
    }

    /**
     * パスワードリセット画面を表示
     */
    public function showResetPasswordForm(Request $request, $token = null)
    {
        $email = $request->query('email');

        if (!$token || !$email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => '無効なリセットリンクです。']);
        }

        // トークンを検証
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'このリセットリンクは無効または期限切れです。']);
        }

        // トークンの有効期限をチェック（60分）
        $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(60);
        if (Carbon::now()->gt($expiresAt)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['email' => 'このリセットリンクは期限切れです。再度リセットリンクをリクエストしてください。']);
        }

        // トークンを検証
        if (!Hash::check($token, $resetRecord->token)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => '無効なリセットリンクです。']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * パスワードをリセット
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // トークンを検証
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'このリセットリンクは無効または期限切れです。']);
        }

        // トークンの有効期限をチェック（60分）
        $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(60);
        if (Carbon::now()->gt($expiresAt)) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['email' => 'このリセットリンクは期限切れです。再度リセットリンクをリクエストしてください。']);
        }

        // トークンを検証
        if (!Hash::check($request->token, $resetRecord->token)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => '無効なリセットリンクです。']);
        }

        // ユーザーを取得
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'ユーザーが見つかりません。']);
        }

        // 元のパスワードと同じかチェック
        if (Hash::check($request->password, $user->password_hash)) {
            return redirect()->back()
                ->withErrors(['password' => '新しいパスワードは現在のパスワードと異なるものを入力してください。'])
                ->withInput();
        }

        // パスワードを更新
        $user->password_hash = Hash::make($request->password);
        $user->save();

        // 使用済みトークンを削除
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')
            ->with('success', 'パスワードをリセットしました。新しいパスワードでログインしてください。');
    }
}

