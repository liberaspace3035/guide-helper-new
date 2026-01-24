<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\EmailNotificationSetting;
use Illuminate\Support\Facades\Auth;

class EmailTemplateController extends Controller
{
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
     * 通知設定一覧取得
     */
    public function settings()
    {
        $settings = EmailNotificationSetting::orderBy('notification_type')->get();
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
        ]);

        $setting = EmailNotificationSetting::findOrFail($id);
        $setting->update([
            'is_enabled' => $request->input('is_enabled'),
            'reminder_days' => $request->input('reminder_days'),
        ]);

        return response()->json(['message' => '通知設定を更新しました', 'setting' => $setting]);
    }
}

