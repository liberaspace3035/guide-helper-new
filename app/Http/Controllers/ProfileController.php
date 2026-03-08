<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
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
        
        return view('profile', [
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $rules = [
            'name' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'introduction' => ($user->isUser() || $user->isGuide()) ? 'required|string|max:2000' : 'nullable|string',
            'accept_guide_proposals' => 'nullable|boolean',
            'show_name_in_proposals' => 'nullable|boolean',
            'available_areas' => 'nullable|array',
            'available_days' => 'nullable|array',
            'available_times' => 'nullable|array',
            'priority_points' => 'nullable|array|max:2',
            'priority_points_other' => 'nullable|string|max:255',
            'qualifications' => 'nullable|array',
            'qualifications.*' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(\App\Models\GuideProfile::QUALIFICATION_OPTIONS))],
        ];
        $messages = [
            'introduction.required' => $user->isUser()
                ? '依頼を作成するには、自己PR（自己紹介）の入力が必須です。'
                : '依頼に応募するには、自己PR（自己紹介）の入力が必須です。',
        ];
        $validated = $request->validate($rules, $messages);

        // 管理者のみ氏名・電話・住所を更新可能
        if ($user->isAdmin() && isset($validated['name'])) {
            $user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? $user->phone,
                'address' => $validated['address'] ?? $user->address,
            ]);
        }

        // プロフィール更新
        if ($user->isUser()) {
            $profile = $user->userProfile ?? new \App\Models\UserProfile(['user_id' => $user->id]);
            $profile->notes = $validated['notes'] ?? $profile->notes;
            $profile->introduction = $validated['introduction'] ?? $profile->introduction;
            if (array_key_exists('accept_guide_proposals', $validated)) {
                $profile->accept_guide_proposals = (bool) $validated['accept_guide_proposals'];
            }
            if (array_key_exists('show_name_in_proposals', $validated)) {
                $profile->show_name_in_proposals = (bool) $validated['show_name_in_proposals'];
            }
            // 重視ポイント
            if (array_key_exists('priority_points', $validated)) {
                $profile->priority_points = $validated['priority_points'];
            }
            if (array_key_exists('priority_points_other', $validated)) {
                $profile->priority_points_other = $validated['priority_points_other'];
            }
            $profile->save();
        } else if ($user->isGuide()) {
            $profile = $user->guideProfile ?? new \App\Models\GuideProfile(['user_id' => $user->id]);
            $profile->introduction = $validated['introduction'] ?? $profile->introduction;
            // GuideProfileモデルで'array'キャストが設定されているため、配列を直接代入すれば自動的にJSONに変換される
            // json_encode()は不要
            $profile->available_areas = $validated['available_areas'] ?? $profile->available_areas;
            $profile->available_days = $validated['available_days'] ?? $profile->available_days;
            $profile->available_times = $validated['available_times'] ?? $profile->available_times;
            // 重視ポイント
            if (array_key_exists('priority_points', $validated)) {
                $profile->priority_points = $validated['priority_points'];
            }
            if (array_key_exists('priority_points_other', $validated)) {
                $profile->priority_points_other = $validated['priority_points_other'];
            }
            // 資格
            if (array_key_exists('qualifications', $validated)) {
                $profile->qualifications = $validated['qualifications'] ?? [];
            }
            $profile->save();
        }
        
        return redirect()->route('profile')->with('success', 'プロフィールが更新されました');
    }
}

