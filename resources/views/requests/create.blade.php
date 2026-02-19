@extends('layouts.app')

@section('content')
<div class="request-form-container" x-data="requestForm()" x-init="init()">
    <h1>依頼作成</h1>
    <form method="POST" action="{{ route('requests.store') }}" @submit.prevent="handleSubmit($event)" x-ref="requestForm" class="request-form" aria-label="依頼作成フォーム">
        @csrf
        <div x-show="error" class="error-message full-width" id="request-form-error-client" role="alert" aria-live="polite" aria-atomic="true" x-text="error"></div>
        @if($errors->any())
            <div class="error-message full-width" id="request-form-error-summary" role="alert" aria-live="polite" aria-atomic="true">
                <span class="sr-only">入力内容に誤りがあります。</span>{{ $errors->first() }}
            </div>
        @endif

        <div class="form-group">
            <label for="request_type">依頼タイプ <span class="required" aria-label="必須項目">*</span></label>
            <select
                id="request_type"
                name="request_type"
                x-model="formData.request_type"
                required
                aria-required="true"
                class="@if($errors->has('request_type')) is-invalid @endif"
                @if($errors->has('request_type')) aria-invalid="true" aria-describedby="request_type-error" @endif
            >
                <option value="outing">外出</option>
                <option value="home">自宅</option>
            </select>
            @if($errors->has('request_type'))
                <div id="request_type-error" class="field-error" role="alert" aria-live="polite">{{ $errors->first('request_type') }}</div>
            @endif
        </div>

        <div class="form-group guide-nomination-group">
            <label id="guide-nomination-label">指名ガイド（任意）</label>
            <p class="guide-nomination-desc">特定のガイドを指名して依頼を投稿できます。地域・性別・年齢・自己PRのキーワードで検索して選択してください。</p>

            <template x-if="formData.nominated_guide_id">
                <div class="nominated-guide-selected" aria-live="polite">
                    <span class="nominated-guide-name" x-text="selectedGuide ? selectedGuide.name : '—'"></span>
                    <button type="button" class="btn-change-guide" @click="clearNominatedGuide()" aria-label="指名ガイドを変更する">変更</button>
                </div>
            </template>

            <template x-if="!formData.nominated_guide_id">
                <div class="guide-search-box" aria-labelledby="guide-nomination-label">
                    <div class="guide-search-filters">
                        <div class="guide-filter-item">
                            <label for="guide_filter_area">地域</label>
                            <select id="guide_filter_area" x-model="guideFilter.area" aria-label="地域で絞り込み">
                                <option value="">指定なし</option>
                                <option value="北海道">北海道</option>
                                <option value="青森県">青森県</option>
                                <option value="岩手県">岩手県</option>
                                <option value="宮城県">宮城県</option>
                                <option value="秋田県">秋田県</option>
                                <option value="山形県">山形県</option>
                                <option value="福島県">福島県</option>
                                <option value="茨城県">茨城県</option>
                                <option value="栃木県">栃木県</option>
                                <option value="群馬県">群馬県</option>
                                <option value="埼玉県">埼玉県</option>
                                <option value="千葉県">千葉県</option>
                                <option value="東京都">東京都</option>
                                <option value="神奈川県">神奈川県</option>
                                <option value="新潟県">新潟県</option>
                                <option value="富山県">富山県</option>
                                <option value="石川県">石川県</option>
                                <option value="福井県">福井県</option>
                                <option value="山梨県">山梨県</option>
                                <option value="長野県">長野県</option>
                                <option value="岐阜県">岐阜県</option>
                                <option value="静岡県">静岡県</option>
                                <option value="愛知県">愛知県</option>
                                <option value="三重県">三重県</option>
                                <option value="滋賀県">滋賀県</option>
                                <option value="京都府">京都府</option>
                                <option value="大阪府">大阪府</option>
                                <option value="兵庫県">兵庫県</option>
                                <option value="奈良県">奈良県</option>
                                <option value="和歌山県">和歌山県</option>
                                <option value="鳥取県">鳥取県</option>
                                <option value="島根県">島根県</option>
                                <option value="岡山県">岡山県</option>
                                <option value="広島県">広島県</option>
                                <option value="山口県">山口県</option>
                                <option value="徳島県">徳島県</option>
                                <option value="香川県">香川県</option>
                                <option value="愛媛県">愛媛県</option>
                                <option value="高知県">高知県</option>
                                <option value="福岡県">福岡県</option>
                                <option value="佐賀県">佐賀県</option>
                                <option value="長崎県">長崎県</option>
                                <option value="熊本県">熊本県</option>
                                <option value="大分県">大分県</option>
                                <option value="宮崎県">宮崎県</option>
                                <option value="鹿児島県">鹿児島県</option>
                                <option value="沖縄県">沖縄県</option>
                            </select>
                        </div>
                        <div class="guide-filter-item">
                            <label for="guide_filter_gender">性別</label>
                            <select id="guide_filter_gender" x-model="guideFilter.gender" aria-label="性別で絞り込み">
                                <option value="">指定なし</option>
                                <option value="male">男性</option>
                                <option value="female">女性</option>
                                <option value="other">その他</option>
                            </select>
                        </div>
                        <div class="guide-filter-item">
                            <label for="guide_filter_age">年齢</label>
                            <select id="guide_filter_age" x-model="guideFilter.age_range" aria-label="年齢で絞り込み">
                                <option value="">指定なし</option>
                                <option value="20-29">20〜29歳</option>
                                <option value="30-39">30〜39歳</option>
                                <option value="40-49">40〜49歳</option>
                                <option value="50-59">50〜59歳</option>
                                <option value="60-">60歳以上</option>
                            </select>
                        </div>
                        <div class="guide-filter-item guide-filter-keyword">
                            <label for="guide_filter_keyword">キーワード（自己PR）</label>
                            <div class="input-with-voice">
                                <input type="text" id="guide_filter_keyword" x-model="guideFilter.keyword" placeholder="例: 買い物 代筆" aria-label="自己PRのキーワードで検索">
                                <button
                                    type="button"
                                    class="voice-input-btn"
                                    :class="{ 'recording': isRecording && voiceTargetField === 'guide_filter_keyword' }"
                                    @click="toggleVoiceInput('guide_filter_keyword')"
                                    :disabled="!isVoiceInputSupported"
                                    title="音声入力"
                                    aria-label="キーワードを音声入力"
                                >
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                        <line x1="12" y1="19" x2="12" y2="23"></line>
                                        <line x1="8" y1="23" x2="16" y2="23"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="guide-filter-actions">
                            <button type="button" class="btn-search-guide" @click="fetchGuidesSearch(1)" :disabled="guideSearchLoading" aria-label="ガイドを検索">
                                <span x-show="!guideSearchLoading">検索</span>
                                <span x-show="guideSearchLoading">検索中...</span>
                            </button>
                        </div>
                    </div>

                    <template x-if="guideSearchResults.length > 0">
                        <div class="guide-search-results" role="list">
                            <p class="guide-search-summary" x-text="`${guideSearchTotal}件中 ${guideSearchResults.length}件を表示`"></p>
                            <template x-for="guide in guideSearchResults" :key="guide.id">
                                <div class="guide-result-card" role="listitem">
                                    <div class="guide-result-main">
                                        <span class="guide-result-name" x-text="guide.name"></span>
                                        <span class="guide-result-meta">
                                            <span x-text="getGenderLabel(guide.gender)"></span>
                                            <template x-if="guide.age !== null"><span x-text="`${guide.age}歳`"></span></template>
                                            <template x-if="guide.available_areas && guide.available_areas.length"><span x-text="guide.available_areas.join('・')"></span></template>
                                        </span>
                                        <template x-if="guide.introduction">
                                            <p class="guide-result-intro" x-text="(guide.introduction || '').substring(0, 80) + ((guide.introduction || '').length > 80 ? '...' : '')"></p>
                                        </template>
                                    </div>
                                    <button type="button" class="btn-select-guide" @click="selectGuide(guide)" aria-label="このガイドを指名する">選択</button>
                                </div>
                            </template>
                            <template x-if="guideSearchPage < guideSearchLastPage">
                                <button type="button" class="btn-load-more-guides" @click="fetchGuidesSearch(guideSearchPage + 1)">もっと見る</button>
                            </template>
                        </div>
                    </template>

                    <template x-if="guideSearchDone && guideSearchResults.length === 0">
                        <p class="guide-search-empty">条件に合うガイドがいません。条件を変えて検索してください。</p>
                    </template>
                </div>
            </template>

            <input type="hidden" name="nominated_guide_id" :value="formData.nominated_guide_id">
        </div>

        <template x-if="formData.request_type === 'outing'">
            <div>
                <div class="form-group">
                    <label for="prefecture">都道府県 <span class="required">*</span></label>
                    <select
                        id="prefecture"
                        name="prefecture"
                        x-model="formData.prefecture"
                        required
                        aria-required="true"
                        class="@if($errors->has('prefecture')) is-invalid @endif"
                    >
                        <option value="">選択してください</option>
                        <option value="北海道">北海道</option>
                        <option value="青森県">青森県</option>
                        <option value="岩手県">岩手県</option>
                        <option value="宮城県">宮城県</option>
                        <option value="秋田県">秋田県</option>
                        <option value="山形県">山形県</option>
                        <option value="福島県">福島県</option>
                        <option value="茨城県">茨城県</option>
                        <option value="栃木県">栃木県</option>
                        <option value="群馬県">群馬県</option>
                        <option value="埼玉県">埼玉県</option>
                        <option value="千葉県">千葉県</option>
                        <option value="東京都">東京都</option>
                        <option value="神奈川県">神奈川県</option>
                        <option value="新潟県">新潟県</option>
                        <option value="富山県">富山県</option>
                        <option value="石川県">石川県</option>
                        <option value="福井県">福井県</option>
                        <option value="山梨県">山梨県</option>
                        <option value="長野県">長野県</option>
                        <option value="岐阜県">岐阜県</option>
                        <option value="静岡県">静岡県</option>
                        <option value="愛知県">愛知県</option>
                        <option value="三重県">三重県</option>
                        <option value="滋賀県">滋賀県</option>
                        <option value="京都府">京都府</option>
                        <option value="大阪府">大阪府</option>
                        <option value="兵庫県">兵庫県</option>
                        <option value="奈良県">奈良県</option>
                        <option value="和歌山県">和歌山県</option>
                        <option value="鳥取県">鳥取県</option>
                        <option value="島根県">島根県</option>
                        <option value="岡山県">岡山県</option>
                        <option value="広島県">広島県</option>
                        <option value="山口県">山口県</option>
                        <option value="徳島県">徳島県</option>
                        <option value="香川県">香川県</option>
                        <option value="愛媛県">愛媛県</option>
                        <option value="高知県">高知県</option>
                        <option value="福岡県">福岡県</option>
                        <option value="佐賀県">佐賀県</option>
                        <option value="長崎県">長崎県</option>
                        <option value="熊本県">熊本県</option>
                        <option value="大分県">大分県</option>
                        <option value="宮崎県">宮崎県</option>
                        <option value="鹿児島県">鹿児島県</option>
                        <option value="沖縄県">沖縄県</option>
                    </select>
                    @if($errors->has('prefecture'))
                        <div class="field-error" role="alert">{{ $errors->first('prefecture') }}</div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="destination_address">市区町村・番地 <span class="required">*</span></label>
                    <div class="input-with-voice">
                        <input
                            type="text"
                            id="destination_address"
                            name="destination_address"
                            x-model="formData.destination_address"
                            required
                            placeholder="例: 渋谷区青山１－１－１"
                            aria-required="true"
                            class="@if($errors->has('destination_address')) is-invalid @endif"
                        />
                        <button
                            type="button"
                            class="voice-input-btn"
                            :class="{ 'recording': isRecording && voiceTargetField === 'destination_address' }"
                            @click="toggleVoiceInput('destination_address')"
                            :disabled="!isVoiceInputSupported"
                            :title="isRecording ? '音声入力を停止' : '音声入力'"
                            aria-label="市区町村・番地を音声入力"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                <line x1="12" y1="19" x2="12" y2="23"></line>
                                <line x1="8" y1="23" x2="16" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <small>市区町村・番地を入力してください（ガイドには大まかな地域のみ表示されます）</small>
                </div>
                <div class="form-group">
                    <label for="meeting_place">待ち合わせ場所 <span class="required">*</span></label>
                    <div class="input-with-voice">
                        <input
                            type="text"
                            id="meeting_place"
                            name="meeting_place"
                            x-model="formData.meeting_place"
                            required
                            placeholder="例: 渋谷駅ハチ公前"
                            aria-required="true"
                            class="@if($errors->has('meeting_place')) is-invalid @endif"
                            @if($errors->has('meeting_place')) aria-invalid="true" aria-describedby="meeting_place-error" @endif
                        />
                        <button
                            type="button"
                            class="voice-input-btn"
                            :class="{ 'recording': isRecording && voiceTargetField === 'meeting_place' }"
                            @click="toggleVoiceInput('meeting_place')"
                            :disabled="!isVoiceInputSupported"
                            :title="isRecording ? '音声入力を停止' : '音声入力'"
                            aria-label="待ち合わせ場所を音声入力"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                <line x1="12" y1="19" x2="12" y2="23"></line>
                                <line x1="8" y1="23" x2="16" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <small>ガイドとの待ち合わせ場所を入力してください</small>
                </div>
            </div>
        </template>
        
        {{-- エラーメッセージはtemplateの外に配置 --}}
        @if($errors->has('prefecture'))
            <div class="form-group" x-show="formData.request_type === 'outing' || formData.request_type === 'home'">
                <div id="prefecture-error" class="field-error" role="alert" aria-live="polite">{{ $errors->first('prefecture') }}</div>
            </div>
        @endif
        @if($errors->has('destination_address'))
            <div class="form-group" x-show="formData.request_type === 'outing' || formData.request_type === 'home'">
                <div id="destination_address-error" class="field-error" role="alert" aria-live="polite">{{ $errors->first('destination_address') }}</div>
            </div>
        @endif
        @if($errors->has('meeting_place'))
            <div class="form-group" x-show="formData.request_type === 'outing'">
                <div id="meeting_place-error" class="field-error" role="alert" aria-live="polite">{{ $errors->first('meeting_place') }}</div>
            </div>
        @endif

        <template x-if="formData.request_type === 'home'">
            <div>
                <div class="form-group">
                    <label for="prefecture">都道府県 <span class="required">*</span></label>
                    <select
                        id="prefecture"
                        name="prefecture"
                        x-model="formData.prefecture"
                        required
                        aria-required="true"
                        class="@if($errors->has('prefecture')) is-invalid @endif"
                    >
                        <option value="">選択してください</option>
                        <option value="北海道">北海道</option>
                        <option value="青森県">青森県</option>
                        <option value="岩手県">岩手県</option>
                        <option value="宮城県">宮城県</option>
                        <option value="秋田県">秋田県</option>
                        <option value="山形県">山形県</option>
                        <option value="福島県">福島県</option>
                        <option value="茨城県">茨城県</option>
                        <option value="栃木県">栃木県</option>
                        <option value="群馬県">群馬県</option>
                        <option value="埼玉県">埼玉県</option>
                        <option value="千葉県">千葉県</option>
                        <option value="東京都">東京都</option>
                        <option value="神奈川県">神奈川県</option>
                        <option value="新潟県">新潟県</option>
                        <option value="富山県">富山県</option>
                        <option value="石川県">石川県</option>
                        <option value="福井県">福井県</option>
                        <option value="山梨県">山梨県</option>
                        <option value="長野県">長野県</option>
                        <option value="岐阜県">岐阜県</option>
                        <option value="静岡県">静岡県</option>
                        <option value="愛知県">愛知県</option>
                        <option value="三重県">三重県</option>
                        <option value="滋賀県">滋賀県</option>
                        <option value="京都府">京都府</option>
                        <option value="大阪府">大阪府</option>
                        <option value="兵庫県">兵庫県</option>
                        <option value="奈良県">奈良県</option>
                        <option value="和歌山県">和歌山県</option>
                        <option value="鳥取県">鳥取県</option>
                        <option value="島根県">島根県</option>
                        <option value="岡山県">岡山県</option>
                        <option value="広島県">広島県</option>
                        <option value="山口県">山口県</option>
                        <option value="徳島県">徳島県</option>
                        <option value="香川県">香川県</option>
                        <option value="愛媛県">愛媛県</option>
                        <option value="高知県">高知県</option>
                        <option value="福岡県">福岡県</option>
                        <option value="佐賀県">佐賀県</option>
                        <option value="長崎県">長崎県</option>
                        <option value="熊本県">熊本県</option>
                        <option value="大分県">大分県</option>
                        <option value="宮崎県">宮崎県</option>
                        <option value="鹿児島県">鹿児島県</option>
                        <option value="沖縄県">沖縄県</option>
                    </select>
                    @if($errors->has('prefecture'))
                        <div class="field-error" role="alert">{{ $errors->first('prefecture') }}</div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="destination_address_home">市区町村・番地 <span class="required">*</span></label>
                    <div class="input-with-voice">
                        <input
                            type="text"
                            id="destination_address_home"
                            name="destination_address"
                            x-model="formData.destination_address"
                            required
                            placeholder="例: 渋谷区青山１－１－１"
                            aria-required="true"
                            class="@if($errors->has('destination_address')) is-invalid @endif"
                        />
                        <button
                            type="button"
                            class="voice-input-btn"
                            :class="{ 'recording': isRecording && voiceTargetField === 'destination_address' }"
                            @click="toggleVoiceInput('destination_address')"
                            :disabled="!isVoiceInputSupported"
                            :title="isRecording ? '音声入力を停止' : '音声入力'"
                            aria-label="市区町村・番地を音声入力"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                <line x1="12" y1="19" x2="12" y2="23"></line>
                                <line x1="8" y1="23" x2="16" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <small>市区町村・番地を入力してください（ガイドには大まかな地域のみ表示されます）</small>
                </div>
                <div class="form-group">
                    <label for="meeting_place_home">集合場所 <span class="required">*</span></label>
                    <div class="input-with-voice">
                        <input
                            type="text"
                            id="meeting_place_home"
                            name="meeting_place"
                            x-model="formData.meeting_place"
                            required
                            placeholder="例: 玄関前"
                            aria-required="true"
                            class="@if($errors->has('meeting_place')) is-invalid @endif"
                        />
                        <button
                            type="button"
                            class="voice-input-btn"
                            :class="{ 'recording': isRecording && voiceTargetField === 'meeting_place' }"
                            @click="toggleVoiceInput('meeting_place')"
                            :disabled="!isVoiceInputSupported"
                            :title="isRecording ? '音声入力を停止' : '音声入力'"
                            aria-label="集合場所を音声入力"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                <line x1="12" y1="19" x2="12" y2="23"></line>
                                <line x1="8" y1="23" x2="16" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <small>ガイドとの集合場所を入力してください</small>
                </div>
            </div>
        </template>
        
        {{-- エラーメッセージはtemplateの外に配置（自宅タイプ用） --}}
        @if($errors->has('destination_address'))
            <div class="form-group" x-show="formData.request_type === 'home'">
                <div class="field-error" role="alert">{{ $errors->first('destination_address') }}</div>
            </div>
        @endif
        @if($errors->has('meeting_place'))
            <div class="form-group" x-show="formData.request_type === 'home'">
                <div class="field-error" role="alert">{{ $errors->first('meeting_place') }}</div>
            </div>
        @endif

        <div class="form-group full-width">
            <label for="service_content">サービス内容 <span class="required">*</span></label>
            <div class="textarea-with-voice">
                <textarea
                    id="service_content"
                    name="service_content"
                    x-model="formData.service_content"
                    required
                    rows="4"
                    placeholder="『買い物』『代筆』など、具体的なサービス内容を記載してください"
                    aria-required="true"
                    class="@if($errors->has('service_content')) is-invalid @endif"
                    @if($errors->has('service_content')) aria-invalid="true" aria-describedby="service_content-error" @endif
                ></textarea>
                <button
                    type="button"
                    class="voice-input-btn"
                    :class="{ 'recording': isRecording && voiceTargetField === 'service_content' }"
                    @click="toggleVoiceInput('service_content')"
                    :disabled="!isVoiceInputSupported"
                    :title="isRecording ? '音声入力を停止' : '音声入力'"
                    aria-label="サービス内容を音声入力"
                >
                    <template x-if="!isRecording">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                            <line x1="12" y1="19" x2="12" y2="23"></line>
                            <line x1="8" y1="23" x2="16" y2="23"></line>
                        </svg>
                    </template>
                    <template x-if="isRecording">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="6" y="6" width="12" height="12" rx="2"></rect>
                        </svg>
                    </template>
                </button>
            </div>
            <template x-if="isRecording">
                <div class="voice-recording-indicator">
                    <span class="recording-dot"></span>
                    <span>音声認識中...</span>
                </div>
            </template>
            @if($errors->has('service_content'))
                <div id="service_content-error" class="field-error" role="alert" aria-live="polite">{{ $errors->first('service_content') }}</div>
            @endif
        </div>

        <div class="form-group full-width">
            <h3 class="section-subtitle">希望するガイドについて（任意）</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="guide_gender">希望するガイドの性別</label>
                    <select
                        id="guide_gender"
                        name="guide_gender"
                        x-model="formData.guide_gender"
                    >
                        <option value="none">選択しない（どの性別でも構わない）</option>
                        <option value="male">男性</option>
                        <option value="female">女性</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guide_age">希望するガイドの年代</label>
                    <select
                        id="guide_age"
                        name="guide_age"
                        x-model="formData.guide_age"
                    >
                        <option value="none">選択しない（どの年代でも構わない）</option>
                        <option value="20s">20代</option>
                        <option value="30s">30代</option>
                        <option value="40s">40代</option>
                        <option value="50s">50代</option>
                        <option value="60s">60代</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="request_date">希望日 <span class="required" aria-label="必須項目">*</span></label>
                <div class="date-input-wrapper">
                    <input
                        type="date"
                        id="request_date"
                        name="request_date"
                        x-model="formData.request_date"
                        @input="formData.request_date = $event.target.value"
                        :value="formData.request_date"
                        required
                        :min="new Date().toISOString().split('T')[0]"
                        aria-required="true"
                        aria-describedby="date-picker-help"
                        x-ref="dateInput"
                        class="@if($errors->has('request_date')) is-invalid @endif"
                        @if($errors->has('request_date')) aria-invalid="true" aria-describedby="request_date-error" @endif
                    />
                    <button
                        type="button"
                        class="date-picker-button"
                        @click="openDatePicker()"
                        aria-label="カレンダーを開く"
                        aria-describedby="date-picker-help"
                        tabindex="0"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </button>
                </div>
                <small id="date-picker-help" class="form-help-text">カレンダーアイコンをクリックするか、キーボードで日付を入力してください</small>
                @if($errors->has('request_date'))
                    <div id="request_date-error" class="field-error" role="alert" aria-live="polite">{{ $errors->first('request_date') }}</div>
                @endif
            </div>

            <div class="form-group">
                <label for="start_time">希望時間 <span class="required" aria-label="必須項目">*</span></label>
                <div class="time-input-group">
                    <div class="time-input-item">
                        <label class="time-label" for="start_hour">開始時刻</label>
                        <div class="time-select-group" role="group" aria-label="開始時刻の入力">
                            <select
                                id="start_hour"
                                x-model="startHour"
                                @change="updateStartTime()"
                                class="time-select"
                                aria-label="開始時刻の時間"
                                aria-required="true"
                            >
                                <template x-for="h in 24" :key="h">
                                    <option :value="h - 1" x-text="String(h - 1).padStart(2, '0')"></option>
                                </template>
                            </select>
                            <span class="time-colon" aria-hidden="true">:</span>
                            <select
                                id="start_minute"
                                x-model="startMinute"
                                @change="updateStartTime()"
                                class="time-select"
                                aria-label="開始時刻の分"
                                aria-required="true"
                            >
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                    <div class="time-input-item">
                        <label class="time-label" for="end_hour">終了時刻</label>
                        <div class="time-select-group" role="group" aria-label="終了時刻の入力">
                            <select
                                id="end_hour"
                                x-model="endHour"
                                @change="updateEndTime()"
                                class="time-select"
                                aria-label="終了時刻の時間"
                                aria-required="true"
                            >
                                <template x-for="h in 24" :key="h">
                                    <option :value="h - 1" x-text="String(h - 1).padStart(2, '0')"></option>
                                </template>
                            </select>
                            <span class="time-colon" aria-hidden="true">:</span>
                            <select
                                id="end_minute"
                                x-model="endMinute"
                                @change="updateEndTime()"
                                class="time-select"
                                aria-label="終了時刻の分"
                                aria-required="true"
                            >
                                <option value="0">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden inputs for form submission -->
                <input type="hidden" name="start_time" :value="formData.start_time" required>
                <input type="hidden" name="end_time" :value="formData.end_time" required>
                
                <small class="time-help-text">時間と分を選択してください（15分単位）。</small>
                @if($errors->has('start_time'))
                    <div class="field-error" role="alert">{{ $errors->first('start_time') }}</div>
                @endif
                @if($errors->has('end_time'))
                    <div class="field-error" role="alert">{{ $errors->first('end_time') }}</div>
                @endif
            </div>
        </div>

        <input type="hidden" name="is_voice_input" :value="isVoiceInput ? 1 : 0">
        <input type="hidden" name="notes" :value="isVoiceInput ? (formData.service_content || '') : ''">
        <div class="form-actions full-width">
            <button
                type="submit"
                class="btn-primary"
                :disabled="loading"
                :aria-busy="loading"
                aria-label="依頼を送信"
            >
                <span x-show="!loading">依頼を送信</span>
                <span x-show="loading" aria-live="polite">
                    <span class="sr-only">送信中</span>
                    送信中...
                </span>
            </button>
            <a href="{{ route('requests.index') }}" class="btn-secondary" aria-label="キャンセル">
                キャンセル
            </a>
        </div>
    </form>
</div>
@endsection


@push('scripts')
<script>
function requestForm() {
    const getDefaultDateTime = () => {
        const now = new Date();
        console.log('now', now);
        
        // 日本時間（JST）で時刻を取得（getHours()とgetMinutes()はローカル時間を返す）
        const toHM = (d) => {
            const hours = d.getHours(); // ローカル時間の時間（0-23）
            const minutes = d.getMinutes(); // ローカル時間の分（0-59）
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
        };
        
        // 現在時刻から2時間後を開始時刻、その1時間後を終了時刻とする
        const start = new Date(now.getTime() + 2 * 60 * 60 * 1000);
        const end = new Date(start.getTime() + 60 * 60 * 1000);
        
        // 日本時間（JST）で日付を取得
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const request_date = `${year}-${month}-${day}`;
        
        return {
            request_date: request_date,
            start_time: toHM(start), // ローカル時間（日本時間）で取得
            end_time: toHM(end) // ローカル時間（日本時間）で取得
        };
    };

    const defaultDateTime = getDefaultDateTime();
    console.log('defaultDateTime', defaultDateTime);

    // デフォルト時刻を時間と分に分解
    const parseTime = (timeStr) => {
        if (!timeStr || !timeStr.includes(':')) {
            return { hour: 0, minute: 0 };
        }
        const [hour, minute] = timeStr.split(':').map(Number);
        return { hour: hour || 0, minute: minute || 0 };
    };

    const defaultStart = parseTime(defaultDateTime.start_time);
    const defaultEnd = parseTime(defaultDateTime.end_time);

    return {
        getDefaultDateTime, // メソッドとして公開
        formData: {
            request_type: 'outing',
            prefecture: '',
            destination_address: '',
            meeting_place: '',
            service_content: '',
            request_date: defaultDateTime.request_date,
            start_time: defaultDateTime.start_time,
            end_time: defaultDateTime.end_time,
            guide_gender: 'none',
            guide_age: 'none',
            nominated_guide_id: ''
        },
        // 時間と分のセレクトボックス用
        startHour: defaultStart.hour,
        startMinute: defaultStart.minute,
        endHour: defaultEnd.hour,
        endMinute: defaultEnd.minute,
        // 時刻を更新するメソッド
        updateStartTime() {
            const hour = String(this.startHour).padStart(2, '0');
            const minute = String(this.startMinute).padStart(2, '0');
            this.formData.start_time = `${hour}:${minute}`;
        },
        updateEndTime() {
            const hour = String(this.endHour).padStart(2, '0');
            const minute = String(this.endMinute).padStart(2, '0');
            this.formData.end_time = `${hour}:${minute}`;
        },
        error: '',
        loading: false,
        isVoiceInput: false,
        isRecording: false,
        isVoiceInputSupported: false,
        voiceTargetField: 'service_content', // 音声入力の反映先: service_content | destination_address | meeting_place
        recognition: null,
        processedResultIndices: new Set(), // 処理済みの結果インデックスを追跡
        interimText: '', // 中間結果を一時保存（表示用）
        // 指名ガイド検索
        guideFilter: { area: '', gender: '', age_range: '', keyword: '' },
        guideSearchResults: [],
        guideSearchTotal: 0,
        guideSearchPage: 1,
        guideSearchLastPage: 1,
        guideSearchLoading: false,
        guideSearchDone: false,
        selectedGuide: null,
        openDatePicker() {
            // 日付入力フィールドをクリックしてカレンダーを開く
            const dateInput = this.$refs.dateInput;
            if (dateInput) {
                // showPicker()メソッドが利用可能な場合（Chrome等）
                if (dateInput.showPicker && typeof dateInput.showPicker === 'function') {
                    try {
                        dateInput.showPicker();
                    } catch (err) {
                        // showPicker()が失敗した場合は通常のクリックを実行
                        dateInput.focus();
                        dateInput.click();
                    }
                } else {
                    // showPicker()が利用できない場合は通常のクリックを実行
                    dateInput.focus();
                    dateInput.click();
                }
            }
        },
        async init() {
            console.log('init');
            // 依頼作成ページが開かれるたびに、日付フィールドに最新の日付を設定
            const defaultDateTime = this.getDefaultDateTime();
            console.log('defaultDateTime', defaultDateTime);
            this.formData.request_date = defaultDateTime.request_date;
            console.log('this.formData.request_date', this.formData.request_date);
            
            // 時刻を15分刻みに丸める
            const roundToQuarter = (timeStr) => {
                const [hour, minute] = timeStr.split(':').map(Number);
                const roundedMinute = Math.round(minute / 15) * 15;
                const finalMinute = roundedMinute >= 60 ? 0 : roundedMinute;
                const finalHour = roundedMinute >= 60 ? hour + 1 : hour;
                return {
                    hour: finalHour >= 24 ? 23 : finalHour,
                    minute: finalMinute
                };
            };
            
            const startRounded = roundToQuarter(defaultDateTime.start_time);
            const endRounded = roundToQuarter(defaultDateTime.end_time);
            
            this.startHour = startRounded.hour;
            this.startMinute = startRounded.minute;
            this.endHour = endRounded.hour;
            this.endMinute = endRounded.minute;
            
            // 時刻を更新
            this.updateStartTime();
            this.updateEndTime();
            
            // 音声認識のサポート確認
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                this.isVoiceInputSupported = true;
                this.initSpeechRecognition();
            }
        },
        getGenderLabel(gender) {
            const map = { male: '男性', female: '女性', other: 'その他', prefer_not_to_say: '回答しない' };
            return map[gender] || '—';
        },
        async fetchGuidesSearch(page) {
            this.guideSearchLoading = true;
            const isFirst = page === 1;
            if (isFirst) {
                this.guideSearchResults = [];
                this.guideSearchDone = false;
            }
            try {
                const q = new URLSearchParams();
                q.set('page', String(page));
                q.set('per_page', '20');
                if (this.guideFilter.area) q.set('area', this.guideFilter.area);
                if (this.guideFilter.gender) q.set('gender', this.guideFilter.gender);
                if (this.guideFilter.age_range) {
                    if (this.guideFilter.age_range === '60-') {
                        q.set('age_min', '60');
                    } else {
                        const [min, max] = this.guideFilter.age_range.split('-');
                        q.set('age_min', min);
                        if (max) q.set('age_max', max);
                    }
                }
                if (this.guideFilter.keyword && this.guideFilter.keyword.trim()) q.set('keyword', this.guideFilter.keyword.trim());
                const token = localStorage.getItem('token');
                const response = await fetch('/api/guides/available?' + q.toString(), {
                    headers: { 'Authorization': 'Bearer ' + (token || ''), 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    const guides = data.guides || [];
                    if (isFirst) {
                        this.guideSearchResults = guides;
                    } else {
                        this.guideSearchResults = [...this.guideSearchResults, ...guides];
                    }
                    this.guideSearchTotal = data.total ?? 0;
                    this.guideSearchPage = data.current_page ?? page;
                    this.guideSearchLastPage = data.last_page ?? 1;
                } else {
                    if (isFirst) this.guideSearchResults = [];
                    this.guideSearchTotal = 0;
                }
            } catch (err) {
                console.error('ガイド検索エラー:', err);
                if (isFirst) this.guideSearchResults = [];
            } finally {
                this.guideSearchLoading = false;
                this.guideSearchDone = true;
            }
        },
        selectGuide(guide) {
            this.formData.nominated_guide_id = String(guide.id);
            this.selectedGuide = guide;
        },
        clearNominatedGuide() {
            this.formData.nominated_guide_id = '';
            this.selectedGuide = null;
        },
        initSpeechRecognition() {
            if (!this.isVoiceInputSupported) return;
            
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'ja-JP';
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            
            this.recognition.onresult = (event) => {
                let finalTranscript = '';
                
                // 確定結果のみを処理（重複を防ぐ）
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const result = event.results[i];
                    const transcript = result[0].transcript;
                    
                    if (result.isFinal) {
                        // 確定結果は一度だけ追加（重複チェック）
                        if (!this.processedResultIndices.has(i)) {
                            finalTranscript += transcript;
                            this.processedResultIndices.add(i);
                        }
                    } else {
                        // 中間結果は表示用のみ（追加しない）
                        this.interimText = transcript;
                    }
                }
                
                // 確定結果のみを追加（スペースを適切に追加）
                if (finalTranscript) {
                    const field = this.voiceTargetField || 'service_content';
                    let currentText = '';
                    if (field === 'guide_filter_keyword') {
                        currentText = this.guideFilter.keyword || '';
                        const separator = currentText && !currentText.endsWith(' ') && !currentText.endsWith('、') ? ' ' : '';
                        this.guideFilter.keyword = currentText + separator + finalTranscript;
                    } else {
                        currentText = this.formData[field] || '';
                        const separator = currentText && 
                            !currentText.endsWith(' ') && 
                            !currentText.endsWith('\n') && 
                            !currentText.endsWith('。') && 
                            !currentText.endsWith('、') 
                            ? ' ' : '';
                        this.formData[field] = currentText + separator + finalTranscript;
                    }
                    this.interimText = '';
                }
            };
            
            this.recognition.onerror = (event) => {
                console.error('音声認識エラー:', event.error);
                this.isRecording = false;
                this.interimText = '';
                if (event.error === 'no-speech') {
                    alert('音声が検出されませんでした。もう一度お試しください。');
                } else if (event.error === 'not-allowed') {
                    alert('マイクの使用が許可されていません。ブラウザの設定を確認してください。');
                }
            };
            
            this.recognition.onend = () => {
                this.isRecording = false;
                this.interimText = '';
                // 処理済み結果をリセット（次回の録音に備える）
                this.processedResultIndices = new Set();
            };
            
            this.recognition.onstart = () => {
                // 開始時に処理済み結果をリセット
                this.processedResultIndices = new Set();
                this.interimText = '';
            };
        },
        toggleVoiceInput(field) {
            if (!this.isVoiceInputSupported) {
                alert('お使いのブラウザは音声認識に対応していません');
                return;
            }
            this.voiceTargetField = field || 'service_content';
            if (this.isRecording) {
                this.stopRecording();
            } else {
                this.startRecording();
            }
        },
        startRecording() {
            // 処理済み結果をリセット
            this.processedResultIndices = new Set();
            this.interimText = '';
            
            if (this.recognition) {
                try {
                    this.recognition.start();
                    this.isRecording = true;
                    this.isVoiceInput = true;
                } catch (error) {
                    console.error('音声認識開始エラー:', error);
                    alert('音声認識を開始できませんでした。もう一度お試しください。');
                }
            } else {
                this.initSpeechRecognition();
                if (this.recognition) {
                    try {
                        this.recognition.start();
                        this.isRecording = true;
                        this.isVoiceInput = true;
                    } catch (error) {
                        console.error('音声認識開始エラー:', error);
                        alert('音声認識を開始できませんでした。もう一度お試しください。');
                    }
                }
            }
        },
        stopRecording() {
            if (this.recognition && this.isRecording) {
                this.recognition.stop();
                this.isRecording = false;
                this.interimText = '';
            }
        },
        handleSubmit(event) {
            this.error = '';
            
            // バリデーション1: 開始時刻 < 終了時刻か（日付を考慮）
            if (this.formData.start_time && this.formData.end_time) {
                // 時刻を分単位に変換する関数
                const timeToMinutes = (timeStr) => {
                    if (!timeStr || !timeStr.includes(':')) return 0;
                    const [hours, minutes] = timeStr.split(':').map(Number);
                    if (isNaN(hours) || isNaN(minutes)) return 0;
                    return hours * 60 + minutes;
                };
                
                const startMinutes = timeToMinutes(this.formData.start_time);
                let endMinutes = timeToMinutes(this.formData.end_time);
                
                // 開始時刻または終了時刻が無効な場合
                if (startMinutes === 0 && this.formData.start_time !== '00:00') {
                    this.error = '開始時刻が無効です';
                    return;
                }
                if (endMinutes === 0 && this.formData.end_time !== '00:00') {
                    this.error = '終了時刻が無効です';
                    return;
                }
                
                // 終了時刻が開始時刻より小さい場合、翌日とみなす（24時間を加算）
                // 例: 23:55 → 1:05 の場合、1:05は翌日の1:05として扱う
                if (endMinutes < startMinutes) {
                    endMinutes += 24 * 60; // 24時間 = 1440分を加算
                } else if (endMinutes === startMinutes) {
                    // 開始時刻と終了時刻が同じ場合はエラー
                    this.error = '終了時刻は開始時刻より後である必要があります';
                    return;
                }
                
                // 実際の時間差を計算
                const durationMinutes = endMinutes - startMinutes;
                
                // 24時間（1440分）を超える場合はエラー
                if (durationMinutes > 24 * 60) {
                    this.error = '依頼時間は24時間以内である必要があります';
                    return;
                }
                
                // 時間差が0以下の場合はエラー（念のため）
                if (durationMinutes <= 0) {
                    this.error = '終了時刻は開始時刻より後である必要があります';
                    return;
                }
            }

            // バリデーション3: 外出タイプの場合、待ち合わせ場所が入力されているか
            if (this.formData.request_type === 'outing' && !this.formData.meeting_place) {
                this.error = '待ち合わせ場所を入力してください';
                return;
            }

            // バリデーションを通過したら送信
            console.log('クライアントサイドバリデーション通過、フォーム送信開始');
            console.log('フォームデータ:', this.formData);
            this.loading = true;
            
            // フォーム要素を取得して送信
            const form = this.$refs.requestForm || event.target.closest('form');
            if (form) {
                console.log('フォーム要素が見つかりました、送信します');
                // 少し遅延を入れて、ローディング状態がUIに反映されるようにする
                setTimeout(() => {
                    console.log('form.submit()を実行します');
                    form.submit();
                }, 100);
            } else {
                // フォームが見つからない場合のフォールバック
                console.error('フォーム要素が見つかりません');
                this.error = 'フォームの送信に失敗しました。ページをリロードしてください。';
                this.loading = false;
            }
        }
    }
}
</script>
@endpush

