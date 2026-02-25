@extends('layouts.app')

@section('content')
<div class="profile-container" x-data="profileForm()" x-init="init()">
    <h1>プロフィール編集</h1>
    <form method="POST" action="{{ route('profile.update') }}" @submit.prevent="handleSubmit" class="profile-form" aria-label="プロフィール編集フォーム">
        @csrf
        @method('PUT')
        
        <div x-show="message" :class="message.includes('失敗') ? 'error-message' : 'success-message'" class="message" role="alert" x-text="message"></div>
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
                <label for="introduction">自己PR（自己紹介） <span class="required" aria-label="必須">*</span></label>
                <textarea
                    id="introduction"
                    name="introduction"
                    x-model="formData.introduction"
                    rows="4"
                    placeholder="依頼を作成するには入力が必須です。ガイドに伝えたいことなどを記入してください。"
                    required
                    aria-required="true"
                >{{ $user->userProfile->introduction ?? '' }}</textarea>
                <small class="form-help-text">依頼を作成するには、ここへの入力が必要です。</small>
            </div>

            <div class="form-group proposal-preference-group">
                <fieldset aria-describedby="proposal-preference-desc">
                    <legend>ガイドの支援提案機能</legend>
                    <p id="proposal-preference-desc" class="form-help-text">ガイドから支援内容の提案を受け取るかどうか、および提案画面で氏名を表示するかを選択してください。</p>
                    <div class="form-group">
                        <label>提案機能を利用する</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="accept_guide_proposals" value="1" x-model="formData.accept_guide_proposals" {{ ($user->userProfile->accept_guide_proposals ?? true) ? 'checked' : '' }} />
                                利用する
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="accept_guide_proposals" value="0" x-model="formData.accept_guide_proposals" {{ ($user->userProfile->accept_guide_proposals ?? true) ? '' : 'checked' }} />
                                利用しない
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ガイドの提案画面で氏名を表示する <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="show_name_in_proposals" value="1" x-model="formData.show_name_in_proposals" {{ ($user->userProfile->show_name_in_proposals ?? false) ? 'checked' : '' }} required aria-required="true" />
                                表示する
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="show_name_in_proposals" value="0" x-model="formData.show_name_in_proposals" {{ ($user->userProfile->show_name_in_proposals ?? false) ? '' : 'checked' }} />
                                表示しない
                            </label>
                        </div>
                        <p class="form-help-text form-help-text--note" x-show="formData.show_name_in_proposals == 1">選択時：ガイドが提案を行う画面において、利用者の氏名が表示されます。</p>
                        <p class="form-help-text form-help-text--note" x-show="formData.show_name_in_proposals == 0 || formData.show_name_in_proposals === false">選択時：氏名は表示されません。個別の提案に限らず、<strong>全体向けの一斉提案も通知でお知らせします</strong>。</p>
                    </div>
                </fieldset>
            </div>
        @endif

        @if($user->isGuide())
            <div class="form-group">
                <label for="introduction">自己PR（自己紹介） <span class="required" aria-label="必須">*</span></label>
                <textarea
                    id="introduction"
                    name="introduction"
                    x-model="formData.introduction"
                    rows="4"
                    placeholder="依頼に応募するには入力が必須です。ユーザーに伝えたいことなどを記入してください。"
                    required
                    aria-required="true"
                >{{ $user->guideProfile->introduction ?? '' }}</textarea>
                <small class="form-help-text">依頼に応募するには、ここへの入力が必要です。</small>
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

{{-- スタイルは layouts/app の @vite(['resources/css/app.scss']) で SCSS からビルドされたものを読み込み --}}

@push('scripts')
<script>
function profileForm() {
    return {
        formData: {
            name: '{{ $user->name }}',
            phone: '{{ $user->phone ?? '' }}',
            address: '{{ $user->address ?? '' }}',
            notes: '{{ $user->userProfile->notes ?? '' }}',
            introduction: '{{ $user->userProfile->introduction ?? ($user->guideProfile->introduction ?? '') }}',
            accept_guide_proposals: {{ ($user->userProfile->accept_guide_proposals ?? true) ? 'true' : 'false' }},
            show_name_in_proposals: {{ ($user->userProfile->show_name_in_proposals ?? false) ? 'true' : 'false' }},
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

