@extends('layouts.app')

@section('content')
<div class="requests-container" x-data="requestsData()" x-init="init()">
    <div class="page-header">
        <h1>依頼一覧</h1>
        <a href="{{ route('requests.create') }}" class="btn-primary-icon">
            新しい依頼を作成
        </a>
    </div>

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
            <p>依頼がありません</p>
            <a href="{{ route('requests.create') }}" class="btn-primary">
                最初の依頼を作成
            </a>
        </div>
    </template>

    <template x-if="!loading && !error && requests.length > 0">
        <div class="requests-list">
            <template x-for="request in requests" :key="request.id">
                <div class="request-card">
                    <div class="request-header">
                        <h3 x-text="getRequestTypeLabel(request.request_type)"></h3>
                        <div class="status-badge-wrapper">
                            <span class="status-badge" :class="getStatusClass(request.status)" :aria-label="getStatusLabel(request.status) + 'の状態'">
                                <span class="status-icon" x-html="getStatusIcon(request.status)"></span>
                                <span x-text="getStatusLabel(request.status)"></span>
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
                    <div class="request-actions">
                        <template x-if="matchedGuideMap[request.id]?.matching_id">
                            <a :href="`/chat/${matchedGuideMap[request.id].matching_id}`" class="btn-primary btn-with-icon" aria-label="チャットを開く">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span>チャットを開く</span>
                            </a>
                        </template>
                        <template x-if="!matchedGuideMap[request.id]?.matching_id">
                            <div>
                                <template x-if="request.status === 'guide_accepted'">
                                    <p class="info-text" style="color: var(--text-secondary); font-size: 14px; margin: 0;">
                                        ガイドが応募しました。管理者の承認を待っています。
                                    </p>
                                </template>
                                <button
                                    type="button"
                                    class="btn-secondary"
                                    :class="{ 'btn-disabled': isApplicantsEmpty(request.id) }"
                                    :disabled="isApplicantsEmpty(request.id)"
                                    @click="fetchApplicants(request.id)"
                                    aria-label="応募ガイド一覧を表示"
                                >
                                    応募ガイドを表示
                                </button>
                                <template x-if="selectMessageMap[request.id]">
                                    <p class="info-text" style="color: var(--text-secondary); font-size: 14px; margin: 0;" x-text="selectMessageMap[request.id]"></p>
                                </template>
                                <div class="chat-availability-info">
                                    <svg class="chat-availability-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <span class="chat-availability-text">チャットはマッチング確定後に利用可能になります</span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-if="expandedRequestId == request.id">
                        <div class="applicants-panel">
                            <template x-if="applicantsLoading[request.id]">
                                <div class="loading-inline" aria-busy="true" aria-live="polite">
                                    <div class="loading-spinner small" aria-hidden="true"></div>
                                    <span>応募ガイドを読み込み中...</span>
                                </div>
                            </template>
                            <template x-if="!applicantsLoading[request.id] && Array.isArray(applicantsMap[request.id])">
                                <div>
                                    <template x-if="applicantsMap[request.id].length === 0">
                                        <p class="info-text">応募しているガイドはまだいません。</p>
                                    </template>
                                    <template x-if="applicantsMap[request.id].length > 0">
                                        <div class="applicants-list">
                                            <template x-for="(guide, idx) in applicantsMap[request.id]" :key="`${guide.guide_id}-${idx}`">
                                                <div class="applicant-card">
                                                    <div class="applicant-header">
                                                        <span class="applicant-name" x-text="guide.name || 'ガイド'"></span>
                                                        <template x-if="guide.status === 'declined'">
                                                            <span class="status-badge status-draft">辞退済み</span>
                                                        </template>
                                                        <template x-if="selectedGuideMap[request.id] === guide.guide_id">
                                                            <span class="status-badge status-matched">選択済み</span>
                                                        </template>
                                                    </div>
                                                    <div class="applicant-meta">
                                                        <span x-text="`性別: ${getGenderLabel(guide.gender)}`"></span>
                                                        <span x-text="`年代: ${guide.age || '—'}`"></span>
                                                        <template x-if="guide.introduction">
                                                            <span x-text="`自己紹介: ${guide.introduction}`"></span>
                                                        </template>
                                                    </div>
                                                    <div class="applicant-actions">
                                                        <template x-if="selectedGuideMap[request.id] !== guide.guide_id && guide.status !== 'declined'">
                                                            <button
                                                                type="button"
                                                                class="btn-primary btn-sm"
                                                                :disabled="!!selecting[request.id]"
                                                                :aria-busy="!!selecting[request.id]"
                                                                @click="handleSelectGuide(request.id, guide.guide_id)"
                                                                aria-label="このガイドを選択する"
                                                            >
                                                                <span x-show="!selecting[request.id]">このガイドを選択</span>
                                                                <span x-show="selecting[request.id]" aria-live="polite">選択中...</span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="applicantsMap[request.id]?.error">
                        <p class="error-message" style="margin-top: 8px;" x-text="applicantsMap[request.id].error"></p>
                    </template>
                </div>
            </template>
        </div>
        
        {{-- ページネーション --}}
        @if($requests->hasPages())
        <div class="pagination-wrapper" role="navigation" aria-label="ページネーション">
            <div class="pagination">
                @if($requests->onFirstPage())
                    <span class="pagination-link disabled" aria-disabled="true" aria-label="前のページ">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </span>
                @else
                    <a href="{{ $requests->previousPageUrl() }}" class="pagination-link" aria-label="前のページ">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </a>
                @endif
                
                <span class="pagination-info" aria-live="polite">
                    ページ {{ $requests->currentPage() }} / {{ $requests->lastPage() }}
                </span>
                
                @if($requests->hasMorePages())
                    <a href="{{ $requests->nextPageUrl() }}" class="pagination-link" aria-label="次のページ">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                @else
                    <span class="pagination-link disabled" aria-disabled="true" aria-label="次のページ">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </span>
                @endif
            </div>
        </div>
        @endif
    </template>
</div>
@endsection

@push('scripts')
<script>
function requestsData() {
    return {
        requests: [],
        loading: true,
        error: '',
        applicantsMap: {},
        applicantsLoading: {},
        expandedRequestId: null,
        selectedGuideMap: {},
        selecting: {},
        matchedGuideMap: {},
        selectMessageMap: {},
        init() {
            // selecting状態を明示的にリセット
            this.selecting = {};
            this.fetchRequests();
        },
        async fetchRequests() {
            try {
                // タイムアウト設定（10秒）
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                
                const response = await fetch('/api/requests/my-requests', {
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
                        return;
                    }
                }
                
                if (!response.ok) {
                    throw new Error(`HTTPエラー: ステータス ${response.status}`);
                }
                
                const data = await response.json();
                this.requests = data.requests || [];
                
                // マッチング情報を設定
                const matched = {};
                const selected = {};
                this.requests.forEach(request => {
                    if (request.matching_id) {
                        matched[request.id] = { 
                            matching_id: request.matching_id, 
                            guide_id: request.matched_guide_id 
                        };
                        selected[request.id] = request.matched_guide_id;
                    } else if (request.matched_guide_id) {
                        selected[request.id] = request.matched_guide_id;
                    }
                });
                
                this.matchedGuideMap = matched;
                this.selectedGuideMap = { ...this.selectedGuideMap, ...selected };
            } catch (err) {
                if (err.name === 'AbortError') {
                    this.error = 'リクエストがタイムアウトしました。しばらく待ってから再度お試しください。';
                } else {
                    this.error = '依頼一覧の取得に失敗しました';
                }
                console.error('依頼一覧取得エラー:', err);
            } finally {
                this.loading = false;
            }
        },
        async fetchApplicants(requestId) {
            console.log('fetchApplicants', requestId, '型:', typeof requestId);
            console.log('現在のexpandedRequestId', this.expandedRequestId, '型:', typeof this.expandedRequestId);
            console.log('applicantsMap[requestId]', this.applicantsMap[requestId]);
            
            // 既にデータがある場合、パネルを開く（またはトグルする）
            if (this.applicantsMap[requestId] && Array.isArray(this.applicantsMap[requestId])) {
                console.log('既存データを使用', this.applicantsMap[requestId]);
                // 既に開いている場合は閉じる、閉じている場合は開く
                const isCurrentlyExpanded = this.expandedRequestId == requestId;
                console.log('現在開いているか', isCurrentlyExpanded, 'expandedRequestId:', this.expandedRequestId, 'requestId:', requestId);
                this.expandedRequestId = isCurrentlyExpanded ? null : requestId;
                console.log('expandedRequestId（トグル後）', this.expandedRequestId);
                return;
            }

            // データがない場合、取得する
            this.applicantsLoading = { ...this.applicantsLoading, [requestId]: true };
            this.expandedRequestId = requestId; // 読み込み中でもパネルを開く
            console.log('データ取得開始、expandedRequestId', this.expandedRequestId);
            try {
                const res = await fetch(`/api/requests/${requestId}/applicants`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(res);
                    if (!shouldContinue) {
                        return;
                    }
                }
                
                const data = await res.json();
                const guides = data.guides || [];
                this.applicantsMap = { ...this.applicantsMap, [requestId]: guides };
                this.expandedRequestId = requestId;
                console.log('データ取得後 expandedRequestId', this.expandedRequestId, '型:', typeof this.expandedRequestId);
            } catch (err) {
                console.error('応募ガイド取得エラー:', err);
                this.applicantsMap = { ...this.applicantsMap, [requestId]: { error: '応募ガイドの取得に失敗しました' } };
                this.expandedRequestId = requestId; // エラーでもパネルを開いてエラーを表示
            } finally {
                this.applicantsLoading = { ...this.applicantsLoading, [requestId]: false };
            }
        },
        async handleSelectGuide(requestId, guideId) {
            if (!guideId || this.selecting[requestId]) return;
            this.selecting = { ...this.selecting, [requestId]: true };
            try {
                const res = await fetch(`/api/requests/${requestId}/select-guide`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ guide_id: guideId })
                });
                const data = await res.json();
                this.selectedGuideMap = { ...this.selectedGuideMap, [requestId]: guideId };
                const msg = data?.auto_matching
                    ? 'ガイドに選択されたことが通知されました。詳細な連絡はチャットでお願いします。'
                    : '管理者が審査しています。完了までお待ちください。';
                this.selectMessageMap = { ...this.selectMessageMap, [requestId]: msg };
            } catch (err) {
                console.error('ガイド選択エラー:', err);
                alert('ガイドの選択に失敗しました');
            } finally {
                this.selecting = { ...this.selecting, [requestId]: false };
            }
        },
        getStatusLabel(status) {
            const statusMap = {
                pending: '応募待ち',
                guide_accepted: 'ガイド応募済み',
                matched: '依頼確定',
                in_progress: '進行中',
                completed: '完了',
                cancelled: 'キャンセル'
            };
            return statusMap[status] || status;
        },
        getStatusClass(status) {
            const classMap = {
                pending: 'status-pending',
                guide_accepted: 'status-accepted',
                matched: 'status-matched',
                in_progress: 'status-in-progress',
                completed: 'status-completed',
                cancelled: 'status-cancelled'
            };
            return classMap[status] || 'status-pending';
        },
        getStatusIcon(status) {
            const iconMap = {
                pending: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
                guide_accepted: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                matched: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                in_progress: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>',
                completed: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                cancelled: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
            };
            return iconMap[status] || iconMap.pending;
        },
        getGenderLabel(gender) {
            const map = {
                male: '男性',
                female: '女性',
                other: 'その他',
                prefer_not_to_say: '回答しない'
            };
            return map[gender] || '—';
        },
        getRequestTypeLabel(type) {
            const map = {
                outing: '外出',
                home: '自宅'
            };
            return map[type] || type;
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
        },
        isApplicantsEmpty(requestId) {
            // 応募ガイドを取得済みで、0件の場合にtrueを返す
            if (this.applicantsMap[requestId] && Array.isArray(this.applicantsMap[requestId])) {
                return this.applicantsMap[requestId].length === 0;
            }
            // 応募ガイドを取得していない場合、request.statusが'pending'の場合は応募ガイドがいない可能性が高い
            const request = this.requests.find(r => r.id == requestId);
            if (request && request.status === 'pending') {
                return true;
            }
            return false;
        }
    }
}
</script>
@endpush

