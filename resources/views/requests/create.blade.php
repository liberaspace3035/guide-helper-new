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
            <template x-if="formData.nominated_guide_id">
                <div class="nominated-guide-selected-block" aria-live="polite">
                    <p class="guide-nomination-desc">現在選択中のガイドです。変更するには「変更」ボタンを押してください。</p>
                    <div class="nominated-guide-selected">
                        <span class="nominated-guide-name" x-text="selectedGuide ? selectedGuide.name : '—'"></span>
                        <button type="button" class="btn-change-guide" @click="openGuideSearchModal()" aria-label="指名ガイドを変更する">変更</button>
                    </div>
                </div>
            </template>
            <template x-if="!formData.nominated_guide_id">
                <div>
                    <p class="guide-nomination-desc">特定のガイドを指名して依頼を投稿できます。地域・性別・年齢・自己PRのキーワードで検索して選択してください。</p>
                    <button type="button" class="btn-open-guide-search" @click="openGuideSearchModal()" aria-label="指名ガイドを選択する（モーダルを開く）">ガイドを選択</button>
                </div>
            </template>

            <input type="hidden" name="nominated_guide_id" :value="formData.nominated_guide_id">
        </div>

        {{-- 指名ガイド選択モーダル --}}
        <div
            class="modal-backdrop"
            x-show="showGuideSearchModal"
            x-cloak
            @click.self="closeGuideSearchModal()"
            @keydown.escape.window="closeGuideSearchModal()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="guide-search-modal-title"
        >
            <div class="modal-content guide-search-modal-content">
                <div class="modal-header">
                    <h2 id="guide-search-modal-title">指名ガイドを選択</h2>
                    <button type="button" class="modal-close-btn" @click="closeGuideSearchModal()" aria-label="閉じる">&times;</button>
                </div>
                <div class="guide-search-box" aria-labelledby="guide-search-modal-title">
                    <div class="guide-search-notice">
                        <template x-if="formData.request_type === 'outing'">
                            <p class="notice-outing">外出支援に必要な資格（同行援護）を持つガイドのみ表示されます</p>
                        </template>
                        <template x-if="formData.request_type === 'home'">
                            <p class="notice-home">自宅支援に必要な資格（介護系）を持つガイドのみ表示されます</p>
                        </template>
                    </div>
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
                            <input type="text" id="guide_filter_keyword" x-model="guideFilter.keyword" placeholder="例: 買い物 代筆" aria-label="自己PRのキーワードで検索">
                        </div>
                        <div class="guide-filter-item">
                            <label for="guide_filter_sort">並び順</label>
                            <select id="guide_filter_sort" x-model="guideSearchSort" aria-label="検索結果の並び順">
                                <option value="name_asc">名前（昇順）</option>
                                <option value="name_desc">名前（降順）</option>
                                <option value="age_asc">年齢（若い順）</option>
                                <option value="age_desc">年齢（年上順）</option>
                            </select>
                        </div>
                        <div class="guide-filter-actions">
                            <button type="button" class="btn-search-guide" @click="fetchGuidesSearch(1)" :disabled="guideSearchLoading" aria-label="ガイドを検索">
                                <span x-show="!guideSearchLoading">検索</span>
                                <span x-show="guideSearchLoading">検索中...</span>
                            </button>
                        </div>
                    </div>

                    <template x-if="guideSearchResults.length > 0">
                        <div class="guide-search-results" role="list" x-ref="guideSearchResultsContainer">
                            <h2 id="guide-search-results-heading" class="guide-search-results-heading" x-ref="guideSearchResultsHeading" tabindex="-1" x-text="`全${guideSearchTotal}件中${guideSearchResults.length}件を表示`"></h2>
                            <template x-for="guide in guideSearchResults" :key="guide.id">
                                <div class="guide-result-card" role="listitem">
                                    <div class="guide-result-main">
                                        <span class="guide-result-name" x-text="guide.name"></span>
                                        <span class="guide-result-meta">
                                            <span x-text="getGenderLabel(guide.gender)"></span>
                                            <template x-if="guide.age !== null"><span x-text="`${guide.age}歳`"></span></template>
                                            <template x-if="guide.available_areas && guide.available_areas.length"><span x-text="guide.available_areas.join('・')"></span></template>
                                        </span>
                                        <div class="guide-support-types">
                                            <template x-if="guide.can_support_outing"><span class="support-badge support-outing-sm">外出</span></template>
                                            <template x-if="guide.can_support_home"><span class="support-badge support-home-sm">自宅</span></template>
                                        </div>
                                        <div class="guide-result-stats">
                                            <template x-if="guide.average_rating">
                                                <span class="stat-rating">
                                                    <svg class="star-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                    <span x-text="guide.average_rating.toFixed(1)"></span>
                                                    <small>(<span x-text="guide.rating_count"></span>)</small>
                                                </span>
                                            </template>
                                            <template x-if="guide.cancel_rate && guide.cancel_rate.total > 0">
                                                <span :class="['stat-cancel', guide.cancel_rate.rate > 20 ? 'high' : (guide.cancel_rate.rate > 10 ? 'medium' : 'low')]" x-text="'キャンセル率: ' + guide.cancel_rate.rate.toFixed(1) + '%'"></span>
                                            </template>
                                        </div>
                                        <template x-if="guide.introduction">
                                            <p class="guide-result-intro" x-text="(guide.introduction || '').substring(0, 80) + ((guide.introduction || '').length > 80 ? '...' : '')"></p>
                                        </template>
                                    </div>
                                    <div class="guide-result-actions">
                                        <button type="button" class="btn-guide-detail" @click="openGuideDetailModal(guide)" aria-label="このガイドの詳細を表示">詳細を表示</button>
                                        <button type="button" class="btn-select-guide" @click="selectGuide(guide)" aria-label="このガイドを指名する">選択</button>
                                    </div>
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
            </div>
        </div>

        {{-- ガイド詳細プロフィールモーダル --}}
        <div
            class="modal-backdrop"
            x-show="showGuideDetailModal"
            x-cloak
            @click.self="closeGuideDetailModal()"
            @keydown.escape.window="closeGuideDetailModal()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="guide-detail-modal-title"
        >
            <div class="modal-content modal-content-sm">
                <div class="modal-header">
                    <h2 id="guide-detail-modal-title">ガイドの詳細</h2>
                    <button type="button" class="modal-close-btn" @click="closeGuideDetailModal()" aria-label="閉じる">&times;</button>
                </div>
                <template x-if="guideDetailForModal">
                    <div class="guide-detail-modal-body">
                        <dl class="guide-detail-dl">
                            <dt>名前</dt>
                            <dd x-text="guideDetailForModal.name"></dd>
                            <dt>性別</dt>
                            <dd x-text="getGenderLabel(guideDetailForModal.gender)"></dd>
                            <dt>年齢</dt>
                            <dd><span x-text="guideDetailForModal.age !== null ? guideDetailForModal.age + '歳' : '—'"></span></dd>
                            <dt>対応地域</dt>
                            <dd x-text="guideDetailForModal.available_areas && guideDetailForModal.available_areas.length ? guideDetailForModal.available_areas.join('、') : '—'"></dd>
                            <dt>評価</dt>
                            <dd>
                                <template x-if="guideDetailForModal.average_rating">
                                    <span class="guide-rating">
                                        <span class="rating-score" x-text="guideDetailForModal.average_rating.toFixed(1)"></span>
                                        <span class="rating-count">（<span x-text="guideDetailForModal.rating_count"></span>件）</span>
                                    </span>
                                </template>
                                <template x-if="!guideDetailForModal.average_rating">
                                    <span class="no-data">—</span>
                                </template>
                            </dd>
                            <dt>直前キャンセル</dt>
                            <dd>
                                <template x-if="guideDetailForModal.cancel_rate && guideDetailForModal.cancel_rate.total > 0">
                                    <span :class="['cancel-rate-badge', guideDetailForModal.cancel_rate.rate > 20 ? 'high' : (guideDetailForModal.cancel_rate.rate > 10 ? 'medium' : 'low')]">
                                        <span x-text="guideDetailForModal.cancel_rate.rate.toFixed(1) + '%'"></span>
                                    </span>
                                </template>
                                <template x-if="!guideDetailForModal.cancel_rate || guideDetailForModal.cancel_rate.total === 0">
                                    <span class="no-data">—</span>
                                </template>
                            </dd>
                            <dt>重視ポイント</dt>
                            <dd>
                                <template x-if="guideDetailForModal.priority_points && guideDetailForModal.priority_points.length > 0">
                                    <div class="priority-points-tags">
                                        <template x-for="point in guideDetailForModal.priority_points" :key="point">
                                            <span class="priority-tag" x-text="point"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!guideDetailForModal.priority_points || guideDetailForModal.priority_points.length === 0">
                                    <span class="no-data">—</span>
                                </template>
                            </dd>
                            <dt>自己PR</dt>
                            <dd class="guide-detail-intro" x-text="guideDetailForModal.introduction || '—'"></dd>
                        </dl>
                        <template x-if="guideDetailForModal.latest_comment">
                            <div class="guide-latest-comment">
                                <h4>最新コメント</h4>
                                <div class="comment-item">
                                    <span :class="['comment-score', 'score-' + guideDetailForModal.latest_comment.score]" x-text="guideDetailForModal.latest_comment.score_label"></span>
                                    <span class="comment-date" x-text="guideDetailForModal.latest_comment.date"></span>
                                    <p class="comment-text" x-text="guideDetailForModal.latest_comment.comment"></p>
                                </div>
                            </div>
                        </template>
                        <div class="guide-detail-actions">
                            <button type="button" class="btn-select-guide" @click="selectGuideFromDetail()">このガイドを指名する</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <template x-if="formData.request_type === 'outing'">
            <div class="form-block form-block-full">
                <h2 class="form-section-title" id="destination-heading-outing">目的地の入力</h2>
                <div class="form-group" aria-labelledby="destination-heading-outing">
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
                    <label for="destination_address">市区町村・番地又は目的地の名称 <span class="required">*</span></label>
                    <input
                        type="text"
                        id="destination_address"
                        name="destination_address"
                        x-model="formData.destination_address"
                        required
                        placeholder="例: 港区青山１－１－１又は代々木公園など"
                        aria-required="true"
                        class="@if($errors->has('destination_address')) is-invalid @endif"
                    />
                    <small>市区町村・番地又は目的地の名称を入力してください（ガイドには大まかな地域のみ表示されます）</small>
                </div>
                <div class="form-group">
                    <label for="meeting_place">待ち合わせ場所 <span class="required">*</span></label>
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
            <div class="form-block form-block-full">
                <h2 class="form-section-title" id="destination-heading-home">目的地の入力</h2>
                <div class="form-group" aria-labelledby="destination-heading-home">
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
                    <label for="destination_address_home">市区町村・番地又は目的地の名称 <span class="required">*</span></label>
                    <input
                        type="text"
                        id="destination_address_home"
                        name="destination_address"
                        x-model="formData.destination_address"
                        required
                        placeholder="例: 港区青山１－１－１又は代々木公園など"
                        aria-required="true"
                        class="@if($errors->has('destination_address')) is-invalid @endif"
                    />
                    <small>市区町村・番地又は目的地の名称を入力してください（ガイドには大まかな地域のみ表示されます）</small>
                </div>
                <div class="form-group">
                    <label for="meeting_place_home">集合場所 <span class="required">*</span></label>
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
        </div>

        {{-- 繰り返し設定 --}}
        <div class="form-group full-width repeat-settings-section">
            <label>繰り返し設定（任意）</label>
            <div class="repeat-toggle">
                <label class="toggle-label">
                    <input type="checkbox" x-model="repeatSettings.enabled" @change="onRepeatToggle()">
                    <span>複数日程で依頼を作成する</span>
                </label>
            </div>

            <template x-if="repeatSettings.enabled">
                <div class="repeat-options">
                    <div class="repeat-type-selector">
                        <label for="repeat_type">繰り返しパターン</label>
                        <select id="repeat_type" x-model="repeatSettings.type" @change="onRepeatTypeChange()">
                            <option value="weekly">毎週</option>
                            <option value="monthly">毎月（同じ日付）</option>
                            <option value="custom">カスタム（日付を個別指定）</option>
                        </select>
                    </div>

                    {{-- 毎週の設定 --}}
                    <template x-if="repeatSettings.type === 'weekly'">
                        <div class="repeat-weekly-options">
                            <div class="repeat-frequency">
                                <label for="repeat_interval">繰り返し頻度</label>
                                <div class="frequency-input">
                                    <select id="repeat_interval" x-model="repeatSettings.interval">
                                        <option value="1">毎週</option>
                                        <option value="2">隔週（2週間ごと）</option>
                                        <option value="3">3週間ごと</option>
                                        <option value="4">4週間ごと</option>
                                    </select>
                                </div>
                            </div>
                            <div class="weekday-selector">
                                <label>曜日を選択</label>
                                <div class="weekday-buttons">
                                    <template x-for="(day, index) in ['日', '月', '火', '水', '木', '金', '土']" :key="index">
                                        <button
                                            type="button"
                                            class="weekday-btn"
                                            :class="{ 'active': repeatSettings.weekdays.includes(index) }"
                                            @click="toggleWeekday(index)"
                                            x-text="day"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                            <div class="repeat-end-date">
                                <label for="repeat_until">終了日</label>
                                <input
                                    type="date"
                                    id="repeat_until"
                                    x-model="repeatSettings.until"
                                    :min="formData.request_date"
                                />
                                <small class="form-help-text">この日付まで繰り返し依頼を作成します（最大12週間先まで）</small>
                            </div>
                        </div>
                    </template>

                    {{-- 毎月の設定 --}}
                    <template x-if="repeatSettings.type === 'monthly'">
                        <div class="repeat-monthly-options">
                            <div class="repeat-frequency">
                                <label for="repeat_months">繰り返し回数</label>
                                <select id="repeat_months" x-model="repeatSettings.monthCount">
                                    <option value="2">2ヶ月間</option>
                                    <option value="3">3ヶ月間</option>
                                    <option value="4">4ヶ月間</option>
                                    <option value="5">5ヶ月間</option>
                                    <option value="6">6ヶ月間</option>
                                </select>
                            </div>
                            <small class="form-help-text">希望日と同じ日付で毎月依頼を作成します</small>
                        </div>
                    </template>

                    {{-- カスタム日付の設定 --}}
                    <template x-if="repeatSettings.type === 'custom'">
                        <div class="repeat-custom-options">
                            <label>追加の日付（最大5件）</label>
                            <div class="custom-dates-list">
                                <template x-for="(date, index) in repeatSettings.customDates" :key="index">
                                    <div class="custom-date-item">
                                        <input
                                            type="date"
                                            :value="date"
                                            @input="updateCustomDate(index, $event.target.value)"
                                            :min="new Date().toISOString().split('T')[0]"
                                        />
                                        <button
                                            type="button"
                                            class="btn-remove-date"
                                            @click="removeCustomDate(index)"
                                            aria-label="この日付を削除"
                                        >&times;</button>
                                    </div>
                                </template>
                            </div>
                            <button
                                type="button"
                                class="btn-add-date"
                                @click="addCustomDate()"
                                :disabled="repeatSettings.customDates.length >= 5"
                            >
                                + 日付を追加
                            </button>
                            <small class="form-help-text">希望日に加えて、追加で最大5件まで日付を指定できます</small>
                        </div>
                    </template>

                    {{-- 生成される依頼のプレビュー --}}
                    <div class="repeat-preview" x-show="getRepeatDates().length > 0">
                        <label>作成される依頼（<span x-text="getRepeatDates().length"></span>件）</label>
                        <ul class="preview-dates-list">
                            <template x-for="date in getRepeatDates().slice(0, 10)" :key="date">
                                <li x-text="formatDateForDisplay(date)"></li>
                            </template>
                            <template x-if="getRepeatDates().length > 10">
                                <li class="more-dates">...他 <span x-text="getRepeatDates().length - 10"></span> 件</li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>

            {{-- Hidden inputs for repeat settings --}}
            <input type="hidden" name="repeat_enabled" :value="repeatSettings.enabled ? '1' : '0'">
            <input type="hidden" name="repeat_type" :value="repeatSettings.type">
            <input type="hidden" name="repeat_interval" :value="repeatSettings.interval">
            <input type="hidden" name="repeat_weekdays" :value="JSON.stringify(repeatSettings.weekdays)">
            <input type="hidden" name="repeat_until" :value="repeatSettings.until">
            <input type="hidden" name="repeat_month_count" :value="repeatSettings.monthCount">
            <input type="hidden" name="repeat_custom_dates" :value="JSON.stringify(repeatSettings.customDates)">
        </div>

        <div class="form-row">

            <div class="form-group">
                <label for="start_hour">希望時間 <span class="required" aria-label="必須項目">*</span></label>
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

        <input type="hidden" name="notes" value="">
        <div class="form-actions full-width">
            <button
                type="submit"
                class="btn-primary"
                :disabled="loading || submitted"
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
        submitted: false, // 二重送信防止フラグ
        // 繰り返し設定
        repeatSettings: {
            enabled: false,
            type: 'weekly', // 'weekly', 'monthly', 'custom'
            interval: 1, // 毎週=1, 隔週=2, etc.
            weekdays: [], // 0=日, 1=月, ..., 6=土
            until: '', // 終了日
            monthCount: 3, // 何ヶ月間
            customDates: [] // カスタム日付の配列
        },
        // 指名ガイド検索
        guideFilter: { area: '', gender: '', age_range: '', keyword: '' },
        guideSearchResults: [],
        guideSearchTotal: 0,
        guideSearchPage: 1,
        guideSearchLastPage: 1,
        guideSearchLoading: false,
        guideSearchDone: false,
        selectedGuide: null,
        guideSearchSort: 'name_asc',
        showGuideSearchModal: false,
        showGuideDetailModal: false,
        guideDetailForModal: null,
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
        },
        getGenderLabel(gender) {
            const map = { male: '男性', female: '女性', other: 'その他', prefer_not_to_say: '回答しない' };
            return map[gender] || '—';
        },
        // 繰り返し設定メソッド
        onRepeatToggle() {
            if (this.repeatSettings.enabled) {
                // 繰り返しを有効にしたとき、デフォルト値を設定
                if (this.repeatSettings.weekdays.length === 0 && this.formData.request_date) {
                    const date = new Date(this.formData.request_date);
                    this.repeatSettings.weekdays = [date.getDay()];
                }
                if (!this.repeatSettings.until && this.formData.request_date) {
                    const date = new Date(this.formData.request_date);
                    date.setDate(date.getDate() + 28); // 4週間後
                    this.repeatSettings.until = date.toISOString().split('T')[0];
                }
            }
        },
        onRepeatTypeChange() {
            // タイプが変わったときの初期化
            if (this.repeatSettings.type === 'custom') {
                if (this.repeatSettings.customDates.length === 0) {
                    // 最初のカスタム日付を追加
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    this.repeatSettings.customDates = [tomorrow.toISOString().split('T')[0]];
                }
            }
        },
        toggleWeekday(dayIndex) {
            const idx = this.repeatSettings.weekdays.indexOf(dayIndex);
            if (idx >= 0) {
                this.repeatSettings.weekdays.splice(idx, 1);
            } else {
                this.repeatSettings.weekdays.push(dayIndex);
            }
        },
        addCustomDate() {
            if (this.repeatSettings.customDates.length < 5) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                this.repeatSettings.customDates.push(tomorrow.toISOString().split('T')[0]);
            }
        },
        removeCustomDate(index) {
            this.repeatSettings.customDates.splice(index, 1);
        },
        updateCustomDate(index, value) {
            this.repeatSettings.customDates[index] = value;
        },
        getRepeatDates() {
            if (!this.repeatSettings.enabled) return [];
            
            const dates = [];
            const baseDate = this.formData.request_date;
            if (!baseDate) return [];
            
            // 基準日を最初に追加
            dates.push(baseDate);
            
            if (this.repeatSettings.type === 'weekly') {
                // 毎週パターン
                const weekdays = this.repeatSettings.weekdays;
                if (weekdays.length === 0) return dates;
                
                const interval = parseInt(this.repeatSettings.interval) || 1;
                const until = this.repeatSettings.until ? new Date(this.repeatSettings.until) : null;
                const maxWeeks = 12; // 最大12週間
                
                const start = new Date(baseDate);
                for (let week = 0; week < maxWeeks; week++) {
                    for (const dayOfWeek of weekdays) {
                        const date = new Date(start);
                        date.setDate(start.getDate() + (week * 7 * interval));
                        
                        // 基準日の週の曜日に合わせる
                        const diff = dayOfWeek - date.getDay();
                        date.setDate(date.getDate() + diff);
                        
                        // 過去の日付はスキップ
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        if (date < today) continue;
                        
                        // 終了日を超えたらスキップ
                        if (until && date > until) continue;
                        
                        const dateStr = date.toISOString().split('T')[0];
                        if (!dates.includes(dateStr)) {
                            dates.push(dateStr);
                        }
                    }
                }
            } else if (this.repeatSettings.type === 'monthly') {
                // 毎月パターン
                const monthCount = parseInt(this.repeatSettings.monthCount) || 3;
                const start = new Date(baseDate);
                const dayOfMonth = start.getDate();
                
                for (let i = 1; i < monthCount; i++) {
                    const date = new Date(start);
                    date.setMonth(start.getMonth() + i);
                    
                    // 月末調整（例：1/31の翌月は2/28に）
                    if (date.getDate() !== dayOfMonth) {
                        date.setDate(0); // 前月の最終日に
                    }
                    
                    const dateStr = date.toISOString().split('T')[0];
                    if (!dates.includes(dateStr)) {
                        dates.push(dateStr);
                    }
                }
            } else if (this.repeatSettings.type === 'custom') {
                // カスタム日付
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                for (const customDate of this.repeatSettings.customDates) {
                    if (!customDate) continue;
                    const date = new Date(customDate);
                    if (date < today) continue;
                    
                    if (!dates.includes(customDate)) {
                        dates.push(customDate);
                    }
                }
            }
            
            // 日付順にソート
            dates.sort();
            return dates;
        },
        formatDateForDisplay(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            const days = ['日', '月', '火', '水', '木', '金', '土'];
            const y = date.getFullYear();
            const m = date.getMonth() + 1;
            const d = date.getDate();
            const dayName = days[date.getDay()];
            return `${y}/${m}/${d}（${dayName}）`;
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
                q.set('sort', this.guideSearchSort);
                // 依頼タイプに応じた資格フィルタリング
                if (this.formData.request_type) {
                    q.set('request_type', this.formData.request_type);
                }
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
                    if (isFirst && this.guideSearchResults.length > 0) {
                        this.$nextTick(() => {
                            const el = this.$refs.guideSearchResultsHeading;
                            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }
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
        openGuideSearchModal() {
            this.showGuideSearchModal = true;
        },
        closeGuideSearchModal() {
            this.showGuideSearchModal = false;
        },
        openGuideDetailModal(guide) {
            this.guideDetailForModal = guide;
            this.showGuideDetailModal = true;
        },
        closeGuideDetailModal() {
            this.showGuideDetailModal = false;
            this.guideDetailForModal = null;
        },
        selectGuideFromDetail() {
            if (this.guideDetailForModal) {
                this.selectGuide(this.guideDetailForModal);
                this.closeGuideDetailModal();
                this.closeGuideSearchModal();
            }
        },
        selectGuide(guide) {
            this.formData.nominated_guide_id = String(guide.id);
            this.selectedGuide = guide;
            this.closeGuideSearchModal();
        },
        clearNominatedGuide() {
            this.formData.nominated_guide_id = '';
            this.selectedGuide = null;
        },
        handleSubmit(event) {
            // 二重送信防止: 既に送信済みの場合は何もしない
            if (this.submitted || this.loading) {
                console.log('二重送信防止: 既に送信処理中です');
                return;
            }
            
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
            
            // 二重送信防止フラグを設定
            this.submitted = true;
            this.loading = true;
            
            // フォーム要素を取得して送信
            const form = this.$refs.requestForm || event.target.closest('form');
            if (form) {
                console.log('フォーム要素が見つかりました、送信します');
                // 即座に送信（遅延なし）
                try {
                    form.submit();
                } catch (e) {
                    console.error('フォーム送信エラー:', e);
                    this.error = 'フォームの送信に失敗しました。ページをリロードしてください。';
                    this.loading = false;
                    this.submitted = false;
                }
            } else {
                // フォームが見つからない場合のフォールバック
                console.error('フォーム要素が見つかりません');
                this.error = 'フォームの送信に失敗しました。ページをリロードしてください。';
                this.loading = false;
                this.submitted = false;
            }
        }
    }
}
</script>
@endpush

