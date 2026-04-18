@extends('layouts.app')

@section('content')
<div class="profile-container" x-data="profileForm()" x-init="init()">
    <h1>プロフィール編集</h1>
    <div class="form-group">
        <p class="form-help-text">登録情報のうち「表示のみ」の項目は、運営承認後にのみ修正されます。</p>
        <a
            class="btn-secondary"
            href="mailto:{{ config('mail.from.address') }}?subject={{ rawurlencode('プロフィール修正申請') }}&body={{ rawurlencode('【ユーザーID】' . $user->id . '\n【お名前】' . $user->name . '\n【修正希望項目】\n【修正理由】') }}"
            aria-label="プロフィール修正申請をメールで送る"
        >
            プロフィール修正申請・お問い合わせ
        </a>
    </div>
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
            <label for="name">お名前 @if(!$user->isAdmin())<span class="readonly-label">（表示のみ）</span>@endif</label>
            @if($user->isAdmin())
                <input
                    type="text"
                    id="name"
                    name="name"
                    x-model="formData.name"
                    value="{{ $user->name }}"
                    required
                    aria-required="true"
                />
            @else
                <div class="readonly-value" role="note">{{ $user->name }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="phone">電話番号
                @if(!$user->isAdmin())
                    <span class="readonly-label">（表示のみ）</span>
                @endif
            </label>
            @if($user->isAdmin())
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    x-model="formData.phone"
                    value="{{ $user->phone }}"
                />
            @else
                <div class="readonly-value" role="note">{{ $user->phone ?: '未登録' }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="address">住所
                @if(!$user->isAdmin())
                    <span class="readonly-label">（表示のみ）</span>
                @endif
            </label>
            @if($user->isAdmin())
                <textarea
                    id="address"
                    name="address"
                    x-model="formData.address"
                    rows="2"
                >{{ $user->address }}</textarea>
            @else
                <div class="readonly-value" role="note">{{ $user->address ?: '未登録' }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="age">年齢（表示のみ）</label>
            <div class="readonly-value" role="note">{{ $user->age ?? '未登録' }}</div>
        </div>

        <div class="form-group">
            <label for="birth_date">生年月日（表示のみ）</label>
            <div class="readonly-value" role="note">{{ $user->birth_date ? $user->birth_date->format('Y-m-d') : '未登録' }}</div>
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
                    <label>受給者証番号（表示のみ）</label>
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
                    placeholder="ガイドに表示されます。配慮事項・重視していること・趣味などを記載してください。"
                    required
                    aria-required="true"
                >{{ $user->userProfile->introduction ?? '' }}</textarea>
                <small class="form-help-text">ガイドに表示される内容ですので、配慮事項やガイドを実施する上で重視していることを記載してください。趣味なども記載してください。</small>
            </div>

            <div class="form-group priority-points-group">
                <fieldset>
                    <legend>重視するポイント</legend>
                    <p class="form-help-text">ガイドに求めることを最大2つまで選択できます。</p>
                    <div class="checkbox-group priority-points-checkboxes">
                        @foreach(\App\Models\User::PRIORITY_POINT_OPTIONS as $key => $label)
                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="priority_points[]"
                                    value="{{ $key }}"
                                    x-model="formData.priority_points"
                                    @click="limitPriorityPoints($event, '{{ $key }}')"
                                    @if($user->userProfile && is_array($user->userProfile->priority_points) && in_array($key, $user->userProfile->priority_points)) checked @endif
                                />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <div class="form-group priority-points-other">
                        <label for="priority_points_other">その他</label>
                        <input
                            type="text"
                            id="priority_points_other"
                            name="priority_points_other"
                            x-model="formData.priority_points_other"
                            value="{{ $user->userProfile->priority_points_other ?? '' }}"
                            maxlength="255"
                            placeholder="その他に重視することがあれば記入"
                        />
                    </div>
                </fieldset>
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
                    placeholder="利用者に表示される内容ですので、ガイドとして心掛けていることや得意分野などを記載してください。趣味も記載してください。"
                    required
                    aria-required="true"
                >{{ optional($user->guideProfile)->introduction ?? '' }}</textarea>
                <small class="form-help-text">利用者に表示される内容ですので、ガイドとして心掛けていることや得意分野などを記載してください。趣味も記載してください。</small>
            </div>

            <div class="form-group">
                <label>保有資格</label>
                <p class="form-help-text">該当する資格をすべて選択してください。外出支援・自宅支援を行うには各種資格が必要です。</p>
                
                <div class="qualification-category">
                    <h4 class="qualification-category-title">外出支援（同行援護）に必要な資格</h4>
                    <p class="qualification-category-note">外出支援を行うには、以下のいずれかの資格が必要です。</p>
                    <div class="checkbox-group qualification-checkboxes">
                        @foreach(\App\Models\GuideProfile::OUTING_REQUIRED_QUALIFICATIONS as $key)
                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="qualifications[]"
                                    value="{{ $key }}"
                                    x-model="formData.qualifications"
                                    @if($user->guideProfile && is_array($user->guideProfile->getQualificationKeys()) && in_array($key, $user->guideProfile->getQualificationKeys())) checked @endif
                                />
                                {{ \App\Models\GuideProfile::QUALIFICATION_OPTIONS[$key] }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="qualification-category">
                    <h4 class="qualification-category-title">自宅支援に必要な資格</h4>
                    <p class="qualification-category-note">自宅支援を行うには、以下のいずれかの資格が必要です。</p>
                    <div class="checkbox-group qualification-checkboxes">
                        @foreach(\App\Models\GuideProfile::HOME_REQUIRED_QUALIFICATIONS as $key)
                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="qualifications[]"
                                    value="{{ $key }}"
                                    x-model="formData.qualifications"
                                    @if($user->guideProfile && is_array($user->guideProfile->getQualificationKeys()) && in_array($key, $user->guideProfile->getQualificationKeys())) checked @endif
                                />
                                {{ \App\Models\GuideProfile::QUALIFICATION_OPTIONS[$key] }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="qualification-summary" x-show="formData.qualifications.length > 0">
                    <strong>対応可能な支援:</strong>
                    <span x-show="canSupportOuting()" class="support-badge support-outing">外出支援</span>
                    <span x-show="canSupportHome()" class="support-badge support-home">自宅支援</span>
                </div>
            </div>

            <div class="form-group priority-points-group">
                <fieldset>
                    <legend>重視するポイント</legend>
                    <p class="form-help-text">ガイドとして大切にしていることを最大2つまで選択できます。</p>
                    <div class="checkbox-group priority-points-checkboxes">
                        @foreach(\App\Models\User::PRIORITY_POINT_OPTIONS as $key => $label)
                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    name="priority_points[]"
                                    value="{{ $key }}"
                                    x-model="formData.priority_points"
                                    @click="limitPriorityPoints($event, '{{ $key }}')"
                                    @if($user->guideProfile && is_array($user->guideProfile->priority_points) && in_array($key, $user->guideProfile->priority_points)) checked @endif
                                />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <div class="form-group priority-points-other">
                        <label for="priority_points_other">その他</label>
                        <input
                            type="text"
                            id="priority_points_other"
                            name="priority_points_other"
                            x-model="formData.priority_points_other"
                            value="{{ optional($user->guideProfile)->priority_points_other ?? '' }}"
                            maxlength="255"
                            placeholder="その他に重視することがあれば記入"
                        />
                    </div>
                </fieldset>
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
                    @foreach(['月','火','水','木','金','土','日','祝日'] as $day)
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
                    @foreach(['午前から可', '午後から可', '1日フリー可', 'その都度調整'] as $time)
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
                <fieldset aria-describedby="filter-availability-desc">
                    <legend>依頼の通知・一覧表示</legend>
                    <p id="filter-availability-desc" class="form-help-text">
                        オンにすると、<a href="{{ route('guide.availability.index') }}">対応可能枠</a>に登録した日時と重なる依頼だけが通知され、依頼一覧にも表示されます。枠が1件もない間は、指名以外の依頼は通知・一覧に出ません。指名依頼は従来どおり対象外です。
                    </p>
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            name="filter_requests_by_availability"
                            value="1"
                            x-model="formData.filter_requests_by_availability"
                            @if(old('filter_requests_by_availability', optional($user->guideProfile)->filter_requests_by_availability ?? false)) checked @endif
                        />
                        枠に合う依頼のみ通知・一覧表示する
                    </label>
                </fieldset>
            </div>

            <div class="form-group">
                <label>従業員番号（表示のみ）</label>
                <input
                    type="text"
                    value="{{ optional($user->guideProfile)->employee_number ?? '' }}"
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

    @if($user->isUser() || $user->isGuide())
    <!-- 評価セクション -->
    <section class="rating-display-section">
        <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            評価・実績
        </h2>

        <div class="rating-summary">
            @php
                $avgRating = $user->getAverageRating();
                $ratingCount = $user->getRatingCount();
                $latestComments = $user->getLatestRatingComments(3);
                $cancelRate = $user->getLateCancellationRate();
                $priorityLabels = $user->getPriorityPointLabels();
            @endphp

            <div class="rating-average-container">
                <div class="rating-average">
                    @if($avgRating)
                        <span class="rating-score-large">{{ number_format($avgRating, 1) }}</span>
                        <span class="rating-max">/3</span>
                    @else
                        <span class="rating-no-data">—</span>
                    @endif
                </div>
                <div class="rating-meta">
                    <span class="rating-count">{{ $ratingCount }}件の評価</span>
                    @if($avgRating)
                        <div class="rating-stars">
                            @for($i = 1; $i <= 3; $i++)
                                @if($i <= round($avgRating))
                                    <svg class="star-filled" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                @else
                                    <svg class="star-empty" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                @endif
                            @endfor
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- キャンセル率 --}}
        <div class="cancel-rate-section">
            <h3 class="subsection-title">直前キャンセル率</h3>
            <div class="cancel-rate-display">
                @if($cancelRate['total'] > 0)
                    <span class="cancel-rate-value {{ $cancelRate['rate'] > 20 ? 'high' : ($cancelRate['rate'] > 10 ? 'medium' : 'low') }}">
                        {{ number_format($cancelRate['rate'], 1) }}%
                    </span>
                    <span class="cancel-rate-meta">
                        （{{ $cancelRate['total'] }}件中{{ $cancelRate['late_cancels'] }}件）
                    </span>
                @else
                    <span class="cancel-rate-no-data">—</span>
                @endif
            </div>
            <p class="cancel-rate-note">※ 依頼日3日前以降のキャンセル割合</p>
        </div>

        {{-- 重視ポイント --}}
        @if(count($priorityLabels) > 0)
            <div class="priority-points-display">
                <h3 class="subsection-title">重視するポイント</h3>
                <ul class="priority-points-list">
                    @foreach($priorityLabels as $label)
                        <li class="priority-point-tag">{{ $label }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($latestComments->count() > 0)
            <div class="rating-comments-section">
                <h3 class="comments-title">
                    @if($user->isUser())
                        ガイドからの評価コメント（最新3件）
                    @else
                        利用者からの評価コメント（最新3件）
                    @endif
                </h3>
                <ul class="rating-comments-list">
                    @foreach($latestComments as $rating)
                        <li class="rating-comment-item">
                            <div class="comment-header">
                                <span class="comment-score score-{{ $rating->score }}">
                                    {{ $rating->score_label }}
                                </span>
                                <span class="comment-date">
                                    {{ $rating->created_at->format('Y/m/d') }}
                                </span>
                            </div>
                            <p class="comment-text">{{ $rating->comment }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <p class="empty-message">まだ評価がありません</p>
        @endif
    </section>

    <!-- ブロック管理セクション -->
    <section class="block-management-section" x-data="blockManagement()" x-init="init()">
        <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
            </svg>
            @if($user->isUser())
                ガイドのブロック管理
            @else
                利用者のブロック管理
            @endif
        </h2>
        <p class="section-description">
            @if($user->isUser())
                ブロックしたガイドは、あなたの依頼を見ることができなくなり、提案を送ることもできなくなります。
            @else
                ブロックした利用者の依頼は表示されなくなり、提案を送ることもできなくなります。
            @endif
        </p>

        <template x-if="loading">
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <span>読み込み中...</span>
            </div>
        </template>

        <template x-if="!loading">
            <div class="block-list-container">
                <template x-if="blocks.length === 0">
                    <p class="empty-message">
                        @if($user->isUser())
                            ブロックしているガイドはいません
                        @else
                            ブロックしている利用者はいません
                        @endif
                    </p>
                </template>

                <template x-if="blocks.length > 0">
                    <ul class="block-list">
                        <template x-for="block in blocks" :key="block.id">
                            <li class="block-item">
                                <div class="block-info">
                                    <span class="block-name" x-text="block.blocked_user.name"></span>
                                    <span class="block-date" x-text="formatDate(block.created_at)"></span>
                                    <template x-if="block.reason">
                                        <span class="block-reason" x-text="'理由: ' + block.reason"></span>
                                    </template>
                                    <template x-if="block.is_admin_block">
                                        <span class="admin-block-badge">管理者設定</span>
                                    </template>
                                </div>
                                <template x-if="!block.is_admin_block">
                                    <button type="button" @click="unblock(block.blocked_user.id)" class="btn-unblock" :disabled="processing">
                                        ブロック解除
                                    </button>
                                </template>
                            </li>
                        </template>
                    </ul>
                </template>

                <template x-if="message">
                    <div class="block-message" :class="messageType" x-text="message"></div>
                </template>
            </div>
        </template>
    </section>
    @endif
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
            introduction: '{{ optional($user->userProfile)->introduction ?? optional($user->guideProfile)->introduction ?? '' }}',
            accept_guide_proposals: {{ ($user->userProfile->accept_guide_proposals ?? true) ? 'true' : 'false' }},
            show_name_in_proposals: {{ ($user->userProfile->show_name_in_proposals ?? false) ? 'true' : 'false' }},
            available_areas: @json($user->guideProfile ? ($user->guideProfile->available_areas ?? []) : []),
            available_days: @json($user->guideProfile ? ($user->guideProfile->available_days ?? []) : []),
            available_times: @json($user->guideProfile ? ($user->guideProfile->available_times ?? []) : []),
            priority_points: @json($user->isGuide() ? (optional($user->guideProfile)->priority_points ?? []) : (optional($user->userProfile)->priority_points ?? [])),
            priority_points_other: '{{ $user->isGuide() ? (optional($user->guideProfile)->priority_points_other ?? '') : (optional($user->userProfile)->priority_points_other ?? '') }}',
            qualifications: @json($user->guideProfile ? $user->guideProfile->getQualificationKeys() : []),
            filter_requests_by_availability: {{ old('filter_requests_by_availability', optional($user->guideProfile)->filter_requests_by_availability ?? false) ? 'true' : 'false' }},
        },
        // 資格マスタ
        outingQualifications: @json(\App\Models\GuideProfile::OUTING_REQUIRED_QUALIFICATIONS),
        homeQualifications: @json(\App\Models\GuideProfile::HOME_REQUIRED_QUALIFICATIONS),
        message: '',
        saving: false,
        init() {
            // プロフィール編集画面では統計情報の取得は不要
        },
        canSupportOuting() {
            return this.formData.qualifications && this.formData.qualifications.some(q => this.outingQualifications.includes(q));
        },
        canSupportHome() {
            return this.formData.qualifications && this.formData.qualifications.some(q => this.homeQualifications.includes(q));
        },
        limitPriorityPoints(event, key) {
            // 最大2つまで選択可能
            if (this.formData.priority_points.length >= 2 && !this.formData.priority_points.includes(key)) {
                event.preventDefault();
                alert('重視するポイントは最大2つまで選択できます');
                return false;
            }
        },
        handleSubmit() {
            this.saving = true;
            this.message = '';
            this.$el.submit();
        }
    }
}

function blockManagement() {
    return {
        blocks: [],
        loading: true,
        processing: false,
        message: '',
        messageType: '',
        init() {
            this.fetchBlocks();
        },
        async fetchBlocks() {
            this.loading = true;
            try {
                const res = await fetch('/api/blocks/my', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.blocks = data.blocks || [];
                }
            } catch (e) {
                console.error('ブロック一覧取得エラー:', e);
            } finally {
                this.loading = false;
            }
        },
        async unblock(userId) {
            if (!confirm('ブロックを解除しますか？')) return;
            this.processing = true;
            this.message = '';
            try {
                const res = await fetch('/api/blocks', {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ user_id: userId })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.message = 'ブロックを解除しました';
                    this.messageType = 'success-message';
                    this.fetchBlocks();
                } else {
                    this.message = data.error || 'ブロック解除に失敗しました';
                    this.messageType = 'error-message';
                }
            } catch (e) {
                this.message = 'ブロック解除に失敗しました';
                this.messageType = 'error-message';
            } finally {
                this.processing = false;
            }
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long', day: 'numeric' });
        }
    }
}
</script>
@endpush

