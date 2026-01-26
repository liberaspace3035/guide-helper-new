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
        <div class="loading-container">
            <div class="loading-spinner"></div>
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
                        <span class="status-badge" :class="getStatusClass(request.status)" x-text="getStatusLabel(request.status)"></span>
                    </div>
                    <div class="request-details">
                        <p><strong>場所:</strong> <span x-text="request.masked_address"></span></p>
                        <p><strong>日時:</strong> <span x-text="formatRequestDateTime(request.request_date, request.request_time)"></span></p>
                        <p><strong>内容:</strong> <span x-text="request.service_content"></span></p>
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
                                    @click="fetchApplicants(request.id)"
                                >
                                    応募ガイドを表示
                                </button>
                                <template x-if="selectMessageMap[request.id]">
                                    <p class="info-text" style="color: var(--text-secondary); font-size: 14px; margin: 0;" x-text="selectMessageMap[request.id]"></p>
                                </template>
                            </div>
                        </template>
                    </div>

                    <template x-if="expandedRequestId == request.id">
                        <div class="applicants-panel">
                            <template x-if="applicantsLoading[request.id]">
                                <div class="loading-inline">
                                    <div class="loading-spinner small"></div>
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
                                                        <template x-if="selectedGuideMap[request.id] !== guide.guide_id">
                                                            <button
                                                                type="button"
                                                                class="btn-primary btn-sm"
                                                                :disabled="!!selecting[request.id]"
                                                                @click="handleSelectGuide(request.id, guide.guide_id)"
                                                            >
                                                                <span x-show="!selecting[request.id]">このガイドを選択</span>
                                                                <span x-show="selecting[request.id]">選択中...</span>
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
    </template>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Requests.css') }}">
@endpush

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
                const response = await fetch('/api/requests/my-requests', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(response);
                    if (!shouldContinue) {
                        return;
                    }
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                this.requests = data.requests || [];
                await this.fetchMatchedGuides();
            } catch (err) {
                this.error = '依頼一覧の取得に失敗しました';
                console.error('依頼一覧取得エラー:', err);
            } finally {
                this.loading = false;
            }
        },
        async fetchMatchedGuides() {
            try {
                const res = await fetch('/api/requests/matched-guides/all', {
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
                const matched = {};
                const selected = {};
                
                // マッチング成立済みのガイド（matching_idがあるもの）
                (data.matched || []).forEach(row => {
                    if (row.matching_id) {
                        matched[row.request_id] = { matching_id: row.matching_id, guide_id: row.guide_id };
                        selected[row.request_id] = row.guide_id;
                    }
                });
                
                // ユーザーが選択済みだが、まだマッチング成立していないガイドも含める
                (data.selected || []).forEach(row => {
                    if (row.matching_id) {
                        matched[row.request_id] = { matching_id: row.matching_id, guide_id: row.guide_id };
                    }
                    // user_selected=1のガイドは選択済みとして扱う
                    selected[row.request_id] = row.guide_id;
                });
                
                this.matchedGuideMap = matched;
                this.selectedGuideMap = { ...this.selectedGuideMap, ...selected };
            } catch (err) {
                console.error('マッチ済みガイド取得エラー:', err);
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
            return classMap[status] || '';
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
        formatRequestDateTime(dateStr, timeStr) {
            if (!dateStr) return '';
            
            // 日付を年/月/日にフォーマット
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;
            const day = date.getDate();
            
            // 時間をフォーマット（秒を除く）
            let timeDisplay = '';
            if (timeStr) {
                // "HH:MM:SS" または "HH:MM" 形式から "HH:MM" を抽出
                const timeMatch = timeStr.match(/^(\d{1,2}):(\d{2})/);
                if (timeMatch) {
                    const hours = parseInt(timeMatch[1], 10);
                    const minutes = timeMatch[2];
                    timeDisplay = `${String(hours).padStart(2, '0')}:${minutes}`;
                }
            }
            
            return `${year}/${month}/${day}${timeDisplay ? ' ' + timeDisplay : ''}`;
        }
    }
}
</script>
@endpush

