<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\EmailNotificationSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class EmailTemplateController extends Controller
{
    /**
     * トリガー一覧取得（新規作成時の選択肢・送信タイミング説明・送信時刻設定可否）
     */
    public function triggers()
    {
        $triggerConfig = config('email_templates.triggers', []);
        $existingKeys = EmailTemplate::pluck('template_key')->all();

        $triggers = [];
        foreach ($triggerConfig as $templateKey => $def) {
            $triggers[] = [
                'template_key' => $templateKey,
                'label' => $def['label'] ?? $templateKey,
                'description' => $def['description'] ?? '',
                'recipient' => $def['recipient'] ?? 'both',
                'notification_type' => $def['notification_type'] ?? 'request',
                'uses_scheduled_time' => (bool) ($def['uses_scheduled_time'] ?? false),
                'already_has_template' => in_array($templateKey, $existingKeys, true),
            ];
        }

        return response()->json(['triggers' => $triggers], 200, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    /**
     * テンプレート一覧取得
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('template_key')->get();
        
        // 文字化けを防ぐため、UTF-8エンコーディングを明示的に設定
        return response()->json(['templates' => $templates], 200, [
            'Content-Type' => 'application/json; charset=utf-8'
        ]);
    }

    /**
     * テンプレート作成（トリガー＝template_key は定義済み一覧から選択。任意で送信時刻を設定可能）
     */
    public function store(Request $request)
    {
        $allowedKeys = array_keys(config('email_templates.triggers', []));
        $request->validate([
            'template_key' => [
                'required',
                'string',
                'max:255',
                'unique:email_templates,template_key',
                Rule::in($allowedKeys),
            ],
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
            'scheduled_time' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
        ], [
            'template_key.in' => 'トリガーは一覧から選択してください。',
            'scheduled_time.regex' => '送信時刻は HH:MM 形式で入力してください（例: 09:00）',
        ]);

        $templateKey = $request->input('template_key');
        $triggerConfig = config('email_templates.triggers.' . $templateKey, []);

        $template = EmailTemplate::create([
            'template_key' => $templateKey,
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
            'is_active' => $request->input('is_active', true),
        ]);

        // 送信時刻を設定可能なトリガーの場合、通知設定の scheduled_time を更新
        $scheduledTime = $request->input('scheduled_time') ? trim($request->input('scheduled_time')) : null;
        if (!empty($triggerConfig['uses_scheduled_time']) && $triggerConfig['notification_type']) {
            $setting = EmailNotificationSetting::where('notification_type', $triggerConfig['notification_type'])->first();
            if ($setting) {
                $setting->update(['scheduled_time' => $scheduledTime ?: $setting->scheduled_time]);
                Cache::forget('admin_email_notification_settings');
            }
        }

        return response()->json(['message' => 'テンプレートを作成しました', 'template' => $template], 201);
    }

    /**
     * テンプレート更新
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = EmailTemplate::findOrFail($id);
        $template->update([
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json(['message' => 'テンプレートを更新しました', 'template' => $template]);
    }

    /**
     * テンプレート削除
     */
    public function destroy(int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'テンプレートを削除しました']);
    }

    /**
     * 通知設定一覧取得（本番の読み込み遅延対策でキャッシュ利用）
     * パスワードリセットは管理者画面では不要なため一覧から除外する
     */
    public function settings()
    {
        $cacheKey = 'admin_email_notification_settings';
        $settings = Cache::remember($cacheKey, 300, function () {
            return EmailNotificationSetting::orderBy('notification_type')
                ->get()
                ->filter(fn ($s) => $s->notification_type !== 'password_reset')
                ->values();
        });
        return response()->json(['settings' => $settings]);
    }

    /**
     * 通知設定更新
     */
    public function updateSetting(Request $request, int $id)
    {
        $request->validate([
            'is_enabled' => 'required|boolean',
            'reminder_days' => 'nullable|integer|min:1',
            'scheduled_time' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
        ], [
            'scheduled_time.regex' => '送信時刻は HH:MM 形式で入力してください（例: 09:00）',
        ]);

        $setting = EmailNotificationSetting::findOrFail($id);
        $setting->update([
            'is_enabled' => $request->input('is_enabled'),
            'reminder_days' => $request->input('reminder_days'),
            'scheduled_time' => $request->input('scheduled_time') ?: null,
        ]);

        Cache::forget('admin_email_notification_settings');

        return response()->json(['message' => '通知設定を更新しました', 'setting' => $setting]);
    }
}

