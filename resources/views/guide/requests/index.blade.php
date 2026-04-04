@extends('layouts.app')

@php($prefillEvent = $prefillEvent ?? null)

@section('content')
<div class="requests-container" x-data="guideRequestsData()" x-init="init()">
    <h1>依頼一覧</h1>
    <p class="info-text">
        依頼が承認されるまでは利用者の個人情報は表示されません。依頼に積極的に応募してください。
    </p>

    <!-- 支援を提案する（ガイド→利用者） -->
    <section class="proposal-section">
        <h2>支援を提案する</h2>
        <p class="proposal-section-desc">利用者に外出支援・自宅支援を提案できます。承諾されるとガイドが確定します。</p>
        <p class="proposal-section-note">提案先の利用者が氏名表示を許可している場合は氏名、していない場合は「利用者」と表示されます。一斉提案もできます。</p>
        <button type="button" @click="showProposalForm = !showProposalForm" class="btn-primary proposal-toggle-btn">
            <span x-text="showProposalForm ? 'フォームを閉じる' : '支援を提案する'"></span>
        </button>
        <div class="proposal-body" x-show="showProposalForm || myProposals.length > 0">
            <div class="proposal-form-column" :class="{ 'proposal-form-column--full': myProposals.length === 0 }">
                <form x-show="showProposalForm" @submit.prevent="submitProposal()" class="proposal-form-wrap" x-transition>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label>提案先 *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="proposal_target" value="individual" x-model="proposalForm.proposal_target" />
                                個別に提案する
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="proposal_target" value="all" x-model="proposalForm.proposal_target" />
                                全体に一斉提案する
                            </label>
                        </div>
                    </div>
                    <template x-if="proposalForm.proposal_target === 'individual'">
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="proposal-user">提案先の利用者 *</label>
                            <select id="proposal-user" x-model="proposalForm.user_id" :required="proposalForm.proposal_target === 'individual'">
                                <option value="">選択してください</option>
                                <template x-for="u in proposalUsers" :key="u.id">
                                    <option :value="u.id" x-text="u.name"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label>依頼タイプ *</label>
                        <select x-model="proposalForm.request_type" required>
                            <option value="outing" :disabled="!canSupportOuting">外出</option>
                            <option value="home" :disabled="!canSupportHome">自宅</option>
                        </select>
                        <p class="form-help-text" x-show="!canSupportOuting || !canSupportHome" x-cloak>
                            保有資格に応じて選択可能な依頼タイプのみ表示しています。
                        </p>
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="proposal-prefecture">都道府県</label>
                        <select id="proposal-prefecture" x-model="proposalForm.prefecture">
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
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="proposal-destination">市区町村・番地又は目的地の名称</label>
                        <input type="text" id="proposal-destination" x-model="proposalForm.destination_address" placeholder="例: 港区青山１－１－１又は代々木公園など" />
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="proposal-meeting" x-text="proposalForm.request_type === 'outing' ? '待ち合わせ場所' : '集合場所'"></label>
                        <input type="text" id="proposal-meeting" x-model="proposalForm.meeting_place" :placeholder="proposalForm.request_type === 'outing' ? '例: 渋谷駅ハチ公前' : '例: 玄関前'" />
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="proposal-content">サービス内容</label>
                        <textarea id="proposal-content" rows="3" x-model="proposalForm.service_content" placeholder="例: 買い物同行・代筆など"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="proposal-date">希望日 *</label>
                        <input type="date" id="proposal-date" x-model="proposalForm.proposed_date" required>
                    </div>
                    <div class="proposal-form-row">
                        <div class="form-group">
                            <label for="proposal-start">開始時刻</label>
                            <input type="time" id="proposal-start" x-model="proposalForm.start_time">
                        </div>
                        <div class="form-group">
                            <label for="proposal-end">終了時刻</label>
                            <input type="time" id="proposal-end" x-model="proposalForm.end_time">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label for="proposal-message">利用者へのメッセージ（任意）</label>
                        <textarea id="proposal-message" rows="2" x-model="proposalForm.message" placeholder="一言メッセージ"></textarea>
                    </div>
                    <button type="submit" class="btn-primary" :disabled="proposalSubmitting">
                        <span x-show="!proposalSubmitting">提案を送信</span>
                        <span x-show="proposalSubmitting">送信中...</span>
                    </button>
                </form>
            </div>
            <template x-if="myProposals.length > 0">
                <div class="proposal-my-list" :class="{ 'proposal-my-list--full': !showProposalForm }">
                    <h3 class="proposal-my-list__title">送った提案 <span class="proposal-my-list__hint" x-show="myProposals.length > 4" x-cloak>（一覧はスクロールで確認できます）</span></h3>
                    <div class="proposal-sent-cards">
                        <template x-for="p in myProposals" :key="p.bulk_group_id || p.id || ('proposal-' + $index)">
                            <article class="proposal-sent-card" :class="{ 'proposal-sent-card--bulk': p.is_bulk }">
                                <template x-if="p.is_bulk">
                                    <div class="proposal-sent-card__body">
                                        <p class="proposal-sent-card__type" x-text="'全体に一斉提案'"></p>
                                        <dl class="proposal-sent-card__meta">
                                            <div class="proposal-sent-card__meta-row">
                                                <dt>希望日</dt>
                                                <dd x-text="p.proposed_date || '—'"></dd>
                                            </div>
                                            <div class="proposal-sent-card__meta-row">
                                                <dt>種別</dt>
                                                <dd x-text="p.request_type_label || '—'"></dd>
                                            </div>
                                            <div class="proposal-sent-card__meta-row">
                                                <dt>送付先</dt>
                                                <dd x-text="(p.total_count || 0) + '件の利用者'"></dd>
                                            </div>
                                        </dl>
                                        <div class="proposal-sent-card__bulk-stats">
                                            <span class="proposal-sent-card__stat proposal-sent-card__stat--accepted" x-text="'承諾 ' + (p.accepted_count || 0)"></span>
                                            <span class="proposal-sent-card__stat proposal-sent-card__stat--rejected" x-text="'辞退 ' + (p.rejected_count || 0)"></span>
                                            <span class="proposal-sent-card__stat proposal-sent-card__stat--pending" x-text="'待機 ' + (p.pending_count || 0)"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!p.is_bulk">
                                    <div class="proposal-sent-card__body">
                                        <p class="proposal-sent-card__type" x-text="p.request_type_label || '—'"></p>
                                        <dl class="proposal-sent-card__meta">
                                            <div class="proposal-sent-card__meta-row">
                                                <dt>提案先</dt>
                                                <dd x-text="p.user?.name || '—'"></dd>
                                            </div>
                                            <div class="proposal-sent-card__meta-row">
                                                <dt>希望日</dt>
                                                <dd x-text="p.proposed_date || '—'"></dd>
                                            </div>
                                        </dl>
                                        <p class="proposal-sent-card__status">
                                            <span class="status-badge" :class="p.status === 'accepted' ? 'status-matched' : p.status === 'rejected' ? 'status-cancelled' : 'status-pending'" x-text="p.status === 'pending' ? '待機中' : p.status === 'accepted' ? '承諾済み' : '辞退'"></span>
                                        </p>
                                    </div>
                                </template>
                            </article>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </section>

    <template x-if="loading">
        <div class="loading-container" aria-busy="true" aria-live="polite">
            <div class="loading-spinner" aria-hidden="true"></div>
            <p>読み込み中...</p>
        </div>
    </template>

    <template x-if="error">
        <div class="error-message" x-text="error"></div>
    </template>

    <template x-if="!loading && !error && requests.length === 0">
        <div class="empty-state">
            <p>現在、利用可能な依頼はありません</p>
        </div>
    </template>

    <template x-if="!loading && !error && requests.length > 0">
        <div class="requests-list">
            <template x-for="request in requests" :key="request.id">
                <div class="request-card">
                    <div class="request-header">
                        <h3 x-text="getRequestTypeLabel(request.request_type)"></h3>
                        <div class="status-badge-wrapper">
                            <span class="status-badge" :class="getStatusClass(request)" :aria-label="getStatusLabel(request) + 'の状態'">
                                <span class="status-icon" x-html="getStatusIcon(request)"></span>
                                <span x-text="getStatusLabel(request)"></span>
                            </span>
                        </div>
                    </div>
                    <div class="request-details">
                        <p><strong>場所:</strong> <span x-text="request.masked_address"></span></p>
                        <p><strong>日時:</strong> <span x-text="formatRequestDateTime(request.request_date, request.start_time, request.end_time)"></span></p>
                        <p><strong>内容:</strong> <span x-text="request.service_content"></span></p>
                        <template x-if="request.request_type === 'outing' && request.meeting_place">
                            <p><strong>待ち合わせ場所:</strong> <span x-text="request.meeting_place"></span></p>
                        </template>
                        <p><strong>作成日:</strong> <span x-text="formatDate(request.created_at)"></span></p>
                    </div>
                    <template x-if="request.user_info">
                        <div class="user-info-section">
                            <h4 class="user-info-title">利用者プロフィール</h4>
                            <div class="user-info-stats">
                                <div class="user-stat">
                                    <span class="stat-label">評価</span>
                                    <template x-if="request.user_info.average_rating">
                                        <span class="stat-value rating">
                                            <svg class="star-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                            <span x-text="request.user_info.average_rating.toFixed(1)"></span>
                                            <small>(<span x-text="request.user_info.rating_count"></span>件)</small>
                                        </span>
                                    </template>
                                    <template x-if="!request.user_info.average_rating">
                                        <span class="stat-value no-data">—</span>
                                    </template>
                                </div>
                                <div class="user-stat">
                                    <span class="stat-label">直前キャンセル</span>
                                    <template x-if="request.user_info.cancel_rate && request.user_info.cancel_rate.total > 0">
                                        <span :class="['stat-value', 'cancel-rate', request.user_info.cancel_rate.rate > 20 ? 'high' : (request.user_info.cancel_rate.rate > 10 ? 'medium' : 'low')]">
                                            <span x-text="request.user_info.cancel_rate.rate.toFixed(1) + '%'"></span>
                                        </span>
                                    </template>
                                    <template x-if="!request.user_info.cancel_rate || request.user_info.cancel_rate.total === 0">
                                        <span class="stat-value no-data">—</span>
                                    </template>
                                </div>
                            </div>
                            <template x-if="request.user_info.priority_points && request.user_info.priority_points.length > 0">
                                <div class="user-priority-points">
                                    <span class="stat-label">重視ポイント</span>
                                    <div class="priority-tags">
                                        <template x-for="point in request.user_info.priority_points" :key="point">
                                            <span class="priority-tag" x-text="point"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="request.user_info.latest_comment">
                                <div class="user-latest-comment">
                                    <span class="stat-label">最新コメント</span>
                                    <div class="comment-preview">
                                        <span :class="['comment-score', 'score-' + request.user_info.latest_comment.score]" x-text="request.user_info.latest_comment.score_label"></span>
                                        <span class="comment-text" x-text="(request.user_info.latest_comment.comment || '').substring(0, 40) + ((request.user_info.latest_comment.comment || '').length > 40 ? '...' : '')"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div class="request-actions">
                        <template x-if="!request.has_applied">
                            <button
                                @click="handleAccept(request.id)"
                                class="btn-primary"
                                aria-label="依頼に応募"
                            >
                                応募
                            </button>
                        </template>
                        <template x-if="request.has_applied">
                            <button
                                class="btn-primary btn-disabled"
                                disabled
                                aria-label="応募済み"
                            >
                                応募済み
                            </button>
                        </template>
                        <!-- 辞退ボタン: 応募済みでpending状態の場合のみ表示 -->
                        <template x-if="request.has_applied && request.acceptance_status === 'pending'">
                            <button
                                @click="handleDecline(request.id)"
                                class="btn-danger"
                                aria-label="依頼を辞退"
                            >
                                辞退
                            </button>
                        </template>
                        <!-- 辞退済み表示 -->
                        <template x-if="request.has_applied && request.acceptance_status === 'declined'">
                            <span class="status-badge status-cancelled">辞退済み</span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
@endsection

{{-- スタイルは layouts/app の @vite(['resources/css/app.scss']) で SCSS からビルドされたものを読み込み --}}

@push('scripts')
@if(!empty($prefillEvent))
<script>window.__guideProposalPrefill = @json($prefillEvent);</script>
@endif
<script>
function guideRequestsData() {
    return {
        canSupportOuting: @json(optional(Auth::user()->guideProfile)->canSupportOuting() ?? false),
        canSupportHome: @json(optional(Auth::user()->guideProfile)->canSupportHome() ?? false),
        requests: [],
        loading: true,
        error: '',
        proposalUsers: [],
        myProposals: [],
        showProposalForm: false,
        proposalForm: {
            proposal_target: 'individual',
            user_id: '',
            request_type: 'outing',
            prefecture: '',
            destination_address: '',
            meeting_place: '',
            proposed_date: '',
            start_time: '',
            end_time: '',
            service_content: '',
            message: ''
        },
        proposalSubmitting: false,
        init() {
            this.ensureProposalRequestTypeByQualification();
            this.fetchRequests();
            this.fetchProposalUsers();
            this.fetchMyProposals();
            if (window.__guideProposalPrefill) {
                const p = window.__guideProposalPrefill;
                this.proposalForm.proposal_target = p.proposal_target || 'individual';
                this.proposalForm.request_type = p.request_type || 'outing';
                this.proposalForm.prefecture = p.prefecture || '';
                this.proposalForm.destination_address = p.destination_address || '';
                this.proposalForm.meeting_place = p.meeting_place || '';
                this.proposalForm.proposed_date = p.proposed_date || '';
                this.proposalForm.start_time = p.start_time || '';
                this.proposalForm.end_time = p.end_time || '';
                this.proposalForm.service_content = p.service_content || '';
                this.proposalForm.message = p.message || '';
                this.ensureProposalRequestTypeByQualification();
                this.showProposalForm = true;
            }
        },
        ensureProposalRequestTypeByQualification() {
            if (this.proposalForm.request_type === 'outing' && !this.canSupportOuting) {
                this.proposalForm.request_type = this.canSupportHome ? 'home' : 'outing';
            }
            if (this.proposalForm.request_type === 'home' && !this.canSupportHome) {
                this.proposalForm.request_type = this.canSupportOuting ? 'outing' : 'home';
            }
        },
        async fetchProposalUsers() {
            try {
                const res = await fetch('/api/guide/proposals/users', { credentials: 'include', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (res.ok) {
                    const data = await res.json();
                    this.proposalUsers = data.users || [];
                }
            } catch (e) { console.error('提案先一覧取得エラー:', e); }
        },
        async fetchMyProposals() {
            try {
                const res = await fetch('/api/guide/proposals', { credentials: 'include', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (res.ok) {
                    const data = await res.json();
                    this.myProposals = data.proposals || [];
                }
            } catch (e) { console.error('提案一覧取得エラー:', e); }
        },
        async submitProposal() {
            if (this.proposalForm.request_type === 'outing' && !this.canSupportOuting) {
                alert('外出支援を提案する資格がありません。プロフィールで資格をご確認ください。');
                return;
            }
            if (this.proposalForm.request_type === 'home' && !this.canSupportHome) {
                alert('自宅支援を提案する資格がありません。プロフィールで資格をご確認ください。');
                return;
            }
            const isIndividual = this.proposalForm.proposal_target === 'individual';
            if (isIndividual && !this.proposalForm.user_id) {
                alert('提案先の利用者を選択してください');
                return;
            }
            if (!this.proposalForm.proposed_date) {
                alert('希望日を入力してください');
                return;
            }
            this.proposalSubmitting = true;
            try {
                const payload = {
                    request_type: this.proposalForm.request_type,
                    proposed_date: this.proposalForm.proposed_date,
                    start_time: this.proposalForm.start_time || null,
                    end_time: this.proposalForm.end_time || null,
                    service_content: this.proposalForm.service_content || null,
                    message: this.proposalForm.message || null,
                    prefecture: this.proposalForm.prefecture || null,
                    destination_address: this.proposalForm.destination_address || null,
                    meeting_place: this.proposalForm.meeting_place || null,
                };
                if (isIndividual) {
                    payload.user_id = parseInt(this.proposalForm.user_id, 10);
                } else {
                    payload.target_all = true;
                }
                const res = await fetch('/api/guide/proposals', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
                    body: JSON.stringify(payload)
                });
                if (window.handleApiResponse) await window.handleApiResponse(res);
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    alert(data.message || '提案を送信しました');
                    this.proposalForm = {
                        proposal_target: 'individual',
                        user_id: '',
                        request_type: 'outing',
                        prefecture: '',
                        destination_address: '',
                        meeting_place: '',
                        proposed_date: '',
                        start_time: '',
                        end_time: '',
                        service_content: '',
                        message: ''
                    };
                    this.ensureProposalRequestTypeByQualification();
                    this.showProposalForm = false;
                    this.fetchMyProposals();
                } else {
                    alert(data.error || '送信に失敗しました');
                }
            } catch (e) {
                alert('送信に失敗しました');
            } finally {
                this.proposalSubmitting = false;
            }
        },
        async fetchRequests() {
            try {
                this.loading = true;
                this.error = '';
                
                // タイムアウト処理（AbortControllerを使用）
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒でタイムアウト
                
                const response = await fetch('/api/requests/guide/available', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(response);
                    if (!shouldContinue) {
                        this.loading = false;
                        return;
                    }
                }
                
                if (response.ok) {
                    const data = await response.json();
                    this.requests = data.requests || [];
                    this.error = '';
                } else {
                    const errorData = await response.json().catch(() => ({ error: '依頼一覧の取得に失敗しました' }));
                    this.error = errorData.error || '依頼一覧の取得に失敗しました';
                    console.error('依頼一覧取得エラー:', response.status, errorData);
                }
            } catch (err) {
                // ネットワークエラーやタイムアウトの処理
                if (err.name === 'AbortError') {
                    this.error = 'リクエストがタイムアウトしました。再度お試しください。';
                } else if (this.isNetworkError(err)) {
                    this.error = 'ネットワーク接続に問題があります。接続を確認してください。';
                } else {
                    this.error = '依頼一覧の取得に失敗しました: ' + (err.message || '不明なエラー');
                }
                console.error('依頼一覧取得エラー:', err);
            } finally {
                this.loading = false;
            }
        },
        isNetworkError(error) {
            // ネットワークエラーの判定
            const errorMessage = error.message || error.toString();
            const errorName = error.name || '';
            
            // AbortError（タイムアウト）もネットワークエラーとして扱う
            if (errorName === 'AbortError' || errorMessage.includes('aborted')) {
                return true;
            }
            
            const networkErrorPatterns = [
                'ERR_NETWORK_CHANGED',
                'ERR_NAME_NOT_RESOLVED',
                'Failed to fetch',
                '取得に失敗しました',
                'NetworkError',
                'ネットワークエラー',
                'Network request failed',
                'ネットワークリクエストに失敗しました',
                'TypeError: Failed to fetch',
                'TypeError: 取得に失敗しました'
            ];
            
            return networkErrorPatterns.some(pattern => 
                errorMessage.includes(pattern)
            );
        },
        async handleAccept(requestId) {
            // 既に応募済みの場合は処理しない
            const request = this.requests.find(r => r.id === requestId);
            if (request && request.has_applied) {
                alert('この依頼は既に応募済みです');
                return;
            }

            if (!confirm('この依頼に応募しますか？')) {
                return;
            }

            try {
                // タイムアウト処理
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                
                const response = await fetch('/api/matchings/accept', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    credentials: 'include',
                    signal: controller.signal,
                    body: JSON.stringify({ request_id: requestId })
                });
                
                clearTimeout(timeoutId);
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(response);
                    if (!shouldContinue) {
                        return;
                    }
                }
                
                const responseData = await response.json().catch(() => ({}));
                
                if (response.ok) {
                    alert(responseData.message || '依頼に応募しました');
                    // 確実に状態を更新
                    await this.fetchRequests();
                } else {
                    console.error('応募エラー詳細:', {
                        status: response.status,
                        statusText: response.statusText,
                        error: responseData
                    });
                    alert(responseData.error || responseData.message || '応募に失敗しました');
                }
            } catch (err) {
                if (err.name === 'AbortError') {
                    alert('リクエストがタイムアウトしました。再度お試しください。');
                } else if (this.isNetworkError(err)) {
                    alert('ネットワーク接続に問題があります。接続を確認してください。');
                } else {
                    console.error('応募エラー:', err);
                    alert('応募に失敗しました: ' + (err.message || 'ネットワークエラー'));
                }
            }
        },
        async handleDecline(requestId) {
            if (!confirm('この依頼を辞退しますか？')) {
                return;
            }

            try {
                // タイムアウト処理
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                
                const response = await fetch('/api/matchings/decline', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    credentials: 'include',
                    signal: controller.signal,
                    body: JSON.stringify({ request_id: requestId })
                });
                
                clearTimeout(timeoutId);
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(response);
                    if (!shouldContinue) {
                        return;
                    }
                }
                
                if (response.ok) {
                    alert('依頼を辞退しました');
                    // 確実に状態を更新
                    await this.fetchRequests();
                } else {
                    const error = await response.json().catch(() => ({ error: '辞退に失敗しました' }));
                    alert(error.error || '辞退に失敗しました');
                }
            } catch (err) {
                if (err.name === 'AbortError') {
                    alert('リクエストがタイムアウトしました。再度お試しください。');
                } else if (this.isNetworkError(err)) {
                    alert('ネットワーク接続に問題があります。接続を確認してください。');
                } else {
                    console.error('辞退エラー:', err);
                    alert('辞退に失敗しました');
                }
            }
        },
        getRequestTypeLabel(type) {
            const map = {
                outing: '外出',
                home: '自宅'
            };
            return map[type] || type;
        },
        getStatusLabel(request) {
            // 応募済みの場合
            if (request.has_applied) {
                if (request.acceptance_status === 'declined') {
                    return '辞退済み';
                } else if (request.acceptance_status === 'matched') {
                    return 'ガイド確定';
                } else if (request.display_status === 'approval_pending') {
                    return '承認待ち';
                } else {
                    return '応募済み';
                }
            }
            // 未応募の場合
            return request.status === 'pending' ? '応募待ち' : '応募可能';
        },
        getStatusClass(request) {
            // 応募済みの場合
            if (request.has_applied) {
                if (request.acceptance_status === 'declined') {
                    return 'status-cancelled';
                } else if (request.acceptance_status === 'matched') {
                    return 'status-matched';
                } else if (request.display_status === 'approval_pending') {
                    return 'status-approval-pending';
                } else {
                    return 'status-accepted';
                }
            }
            // 未応募の場合
            return request.status === 'pending' ? 'status-pending' : 'status-pending';
        },
        getStatusIcon(request) {
            // 応募済みの場合
            if (request.has_applied) {
                if (request.acceptance_status === 'declined') {
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
                } else if (request.acceptance_status === 'matched') {
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
                } else if (request.display_status === 'approval_pending') {
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
                } else {
                    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
                }
            }
            // 未応募の場合
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleString('ja-JP');
        },
        formatRequestDateTime(dateStr, startTimeStr, endTimeStr) {
            if (!dateStr) return '';
            
            // 日付を年/月/日にフォーマット
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;
            const day = date.getDate();
            
            const dateDisplay = `${year}/${month}/${day}`;
            
            // 開始時間と終了時間をフォーマット
            const formatTime = (timeStr) => {
                if (!timeStr) return null;
                // "HH:MM:SS" または "HH:MM" 形式から "HH:MM" を抽出
                const timeMatch = timeStr.match(/^(\d{1,2}):(\d{2})/);
                if (timeMatch) {
                    const hours = parseInt(timeMatch[1], 10);
                    const minutes = timeMatch[2];
                    return `${String(hours).padStart(2, '0')}:${minutes}`;
                }
                return null;
            };
            
            const startTime = formatTime(startTimeStr);
            const endTime = formatTime(endTimeStr);
            
            // 開始時間と終了時間の両方がある場合
            if (startTime && endTime) {
                return `${dateDisplay} ${startTime} - ${endTime}`;
            }
            // 開始時間のみある場合
            if (startTime) {
                return `${dateDisplay} ${startTime}`;
            }
            // どちらもない場合は日付のみ
            return dateDisplay;
        }
    }
}
</script>
@endpush

