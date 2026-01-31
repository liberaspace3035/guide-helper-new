@extends('layouts.app')

@section('content')
<div class="profile-container" x-data="profileForm()" x-init="init()">
    <h1>プロフィール編集</h1>
    <form method="POST" action="{{ route('profile.update') }}" @submit.prevent="handleSubmit" class="profile-form" aria-label="プロフィール編集フォーム">
        @csrf
        @method('PUT')
        
        <div x-show="message" :class="message.includes('失敗') ? 'error-message' : 'success-message'" class="message" role="alert" x-text="message"></div>
        @if(session('success'))
            <div class="success-message" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="error-message" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="form-group">
            <label for="name">お名前 
                <span class="required">*</span>
                @if(!$user->isAdmin())
                    <span class="readonly-label">（閲覧のみ）</span>
                @endif
            </label>
            <input
                type="text"
                id="name"
                name="name"
                x-model="formData.name"
                value="{{ $user->name }}"
                required
                aria-required="true"
                @if(!$user->isAdmin()) readonly disabled @endif
            />
        </div>

        <div class="form-group">
            <label for="phone">電話番号
                @if(!$user->isAdmin())
                    <span class="readonly-label">（閲覧のみ）</span>
                @endif
            </label>
            <input
                type="tel"
                id="phone"
                name="phone"
                x-model="formData.phone"
                value="{{ $user->phone }}"
                @if(!$user->isAdmin()) readonly disabled @endif
            />
        </div>

        <div class="form-group">
            <label for="address">住所
                @if(!$user->isAdmin())
                    <span class="readonly-label">（閲覧のみ）</span>
                @endif
            </label>
            <textarea
                id="address"
                name="address"
                x-model="formData.address"
                rows="2"
                @if(!$user->isAdmin()) readonly disabled @endif
            >{{ $user->address }}</textarea>
        </div>

        <div class="form-group">
            <label for="age">年齢（表示のみ）</label>
            <input
                type="text"
                id="age"
                name="age"
                value="{{ $user->age ?? '' }}"
                readonly
                disabled
            />
        </div>

        <div class="form-group">
            <label for="birth_date">生年月日（表示のみ）</label>
            <input
                type="date"
                id="birth_date"
                name="birth_date"
                value="{{ $user->birth_date ? $user->birth_date->format('Y-m-d') : '' }}"
                readonly
                disabled
            />
        </div>

        @if($user->isUser())
            <div class="form-group">
                <label for="contact_method">連絡手段</label>
                <input
                    type="text"
                    id="contact_method"
                    name="contact_method"
                    x-model="formData.contact_method"
                    value="{{ $user->userProfile->contact_method ?? '' }}"
                    placeholder="例: 電話、メール、LINE等"
                />
            </div>
            <div class="form-group">
                <label for="notes">備考</label>
                <textarea
                    id="notes"
                    name="notes"
                    x-model="formData.notes"
                    rows="4"
                >{{ $user->userProfile->notes ?? '' }}</textarea>
            </div>
            @if(!$user->isAdmin())
                <div class="form-group">
                    <label>受給者証番号（閲覧のみ）</label>
                    <input
                        type="text"
                        value="{{ $user->userProfile->recipient_number ?? '' }}"
                        disabled
                        aria-readonly="true"
                    />
                </div>
            @endif
            <div class="form-group">
                <label for="introduction">自己紹介</label>
                <textarea
                    id="introduction"
                    name="introduction"
                    x-model="formData.introduction"
                    rows="4"
                    placeholder="自己紹介を記入してください"
                >{{ $user->userProfile->introduction ?? '' }}</textarea>
            </div>
        @endif

        @if($user->isGuide())
            <div class="form-group">
                <label for="introduction">自己紹介</label>
                <textarea
                    id="introduction"
                    name="introduction"
                    x-model="formData.introduction"
                    rows="4"
                    placeholder="自己紹介を記入してください"
                >{{ $user->guideProfile->introduction ?? '' }}</textarea>
            </div>

            <div class="form-group">
                <label>対応可能エリア</label>
                <p class="form-help-text">対応可能な都道府県を選択してください。複数選択可能です。</p>
                <div class="checkbox-group">
                    @foreach([
                        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
                        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
                        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
                        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
                        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
                        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
                        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
                    ] as $area)
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="available_areas[]"
                                value="{{ $area }}"
                                x-model="formData.available_areas"
                                @if($user->guideProfile && is_array($user->guideProfile->available_areas) && in_array($area, $user->guideProfile->available_areas)) checked @endif
                            />
                            {{ $area }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>対応可能日</label>
                <div class="checkbox-group">
                    @foreach(['平日', '土日', '祝日'] as $day)
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="available_days[]"
                                value="{{ $day }}"
                                x-model="formData.available_days"
                                @if($user->guideProfile && is_array($user->guideProfile->available_days) && in_array($day, $user->guideProfile->available_days)) checked @endif
                            />
                            {{ $day }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>対応可能時間帯</label>
                <div class="checkbox-group">
                    @foreach(['午前', '午後', '夜間'] as $time)
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="available_times[]"
                                value="{{ $time }}"
                                x-model="formData.available_times"
                                @if($user->guideProfile && is_array($user->guideProfile->available_times) && in_array($time, $user->guideProfile->available_times)) checked @endif
                            />
                            {{ $time }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>従業員番号（閲覧のみ）</label>
                <input
                    type="text"
                    value="{{ $user->guideProfile->employee_number ?? '' }}"
                    disabled
                    aria-readonly="true"
                />
            </div>
            @if(!$user->isAdmin())
                <div class="form-group">
                    <label>運営側からのコメント（閲覧のみ）</label>
                    <p class="form-help-text">管理者が記入するコメント欄です。ガイドの方は閲覧のみ可能です。運営からの連絡事項や注意事項などが記載される場合があります。</p>
                    <textarea
                        value="{{ $user->guideProfile->admin_comment ?? '' }}"
                        readOnly
                        disabled
                        aria-readonly="true"
                        rows="3"
                        class="readonly-textarea"
                    >{{ $user->guideProfile->admin_comment ?? '' }}</textarea>
                </div>
            @endif
        @endif

        <div class="form-actions">
            <button
                type="submit"
                class="btn-primary"
                :disabled="saving"
                aria-label="プロフィールを保存"
            >
                <span x-show="!saving">保存</span>
                <span x-show="saving">保存中...</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Profile.css') }}">
@endpush

@push('scripts')
<script>
function profileForm() {
    return {
        formData: {
            name: '{{ $user->name }}',
            phone: '{{ $user->phone ?? '' }}',
            address: '{{ $user->address ?? '' }}',
            contact_method: '{{ $user->userProfile->contact_method ?? '' }}',
            notes: '{{ $user->userProfile->notes ?? '' }}',
            introduction: '{{ $user->userProfile->introduction ?? ($user->guideProfile->introduction ?? '') }}',
            available_areas: @json($user->guideProfile ? ($user->guideProfile->available_areas ?? []) : []),
            available_days: @json($user->guideProfile ? ($user->guideProfile->available_days ?? []) : []),
            available_times: @json($user->guideProfile ? ($user->guideProfile->available_times ?? []) : []),
        },
        message: '',
        saving: false,
        init() {
            // プロフィール編集画面では統計情報の取得は不要
        },
        handleSubmit() {
            this.saving = true;
            this.message = '';
            this.$el.submit();
        }
    }
}
</script>
@endpush

