@extends('layouts.app')

@section('content')
<div class="requests-container" x-data="guideRequestsData()" x-init="init()">
    <h1>依頼一覧</h1>
    <p class="info-text">
        依頼が承認されるまでは利用者の個人情報は表示されません。依頼に積極的に応募してください。
    </p>

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
            <p>現在、利用可能な依頼はありません</p>
        </div>
    </template>

    <template x-if="!loading && !error && requests.length > 0">
        <div class="requests-list">
            <template x-for="request in requests" :key="request.id">
                <div class="request-card">
                    <div class="request-header">
                        <h3 x-text="getRequestTypeLabel(request.request_type)"></h3>
                        <span class="status-badge" :class="getStatusClass(request)" x-text="getStatusLabel(request)"></span>
                    </div>
                    <div class="request-details">
                        <p><strong>場所:</strong> <span x-text="request.masked_address"></span></p>
                        <p><strong>日時:</strong> <span x-text="formatRequestDateTime(request.request_date, request.request_time)"></span></p>
                        <p><strong>内容:</strong> <span x-text="request.service_content"></span></p>
                        <p><strong>作成日:</strong> <span x-text="formatDate(request.created_at)"></span></p>
                    </div>
                    <div class="request-actions">
                        <template x-if="!request.has_applied">
                            <button
                                @click="handleAccept(request.id)"
                                class="btn-primary"
                                aria-label="依頼を承諾"
                            >
                                承諾
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
                                class="btn-secondary"
                                aria-label="依頼を辞退"
                            >
                                辞退
                            </button>
                        </template>
                        <!-- 辞退済み表示 -->
                        <template x-if="request.has_applied && request.acceptance_status === 'declined'">
                            <span class="status-badge status-draft">辞退済み</span>
                        </template>
                    </div>
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
function guideRequestsData() {
    return {
        requests: [],
        loading: true,
        error: '',
        init() {
            this.fetchRequests();
        },
        async fetchRequests() {
            try {
                const token = localStorage.getItem('token');
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`;
                }
                
                const response = await fetch('/api/requests/guide/available', {
                    headers: headers,
                    credentials: 'same-origin'
                });
                const data = await response.json();
                this.requests = data.requests || [];
            } catch (err) {
                this.error = '依頼一覧の取得に失敗しました';
                console.error(err);
            } finally {
                this.loading = false;
            }
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
                const token = localStorage.getItem('token');
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`;
                }
                
                const response = await fetch('/api/matchings/accept', {
                    method: 'POST',
                    headers: headers,
                    credentials: 'same-origin',
                    body: JSON.stringify({ request_id: requestId })
                });
                
                const responseData = await response.json().catch(() => ({}));
                
                if (response.ok) {
                    alert(responseData.message || '依頼に応募しました');
                    this.fetchRequests();
                } else {
                    console.error('応募エラー詳細:', {
                        status: response.status,
                        statusText: response.statusText,
                        error: responseData
                    });
                    alert(responseData.error || responseData.message || '応募に失敗しました');
                }
            } catch (err) {
                console.error('応募エラー:', err);
                alert('応募に失敗しました: ' + (err.message || 'ネットワークエラー'));
            }
        },
        async handleDecline(requestId) {
            if (!confirm('この依頼を辞退しますか？')) {
                return;
            }

            try {
                const token = localStorage.getItem('token');
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`;
                }
                
                const response = await fetch('/api/matchings/decline', {
                    method: 'POST',
                    headers: headers,
                    credentials: 'same-origin',
                    body: JSON.stringify({ request_id: requestId })
                });
                
                if (response.ok) {
                    alert('依頼を辞退しました');
                    this.fetchRequests();
                } else {
                    const error = await response.json().catch(() => ({ error: '辞退に失敗しました' }));
                    alert(error.error || '辞退に失敗しました');
                }
            } catch (err) {
                console.error('辞退エラー:', err);
                alert('辞退に失敗しました');
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
            if (request.has_applied) {
                if (request.acceptance_status === 'declined') {
                    return '辞退済み';
                } else if (request.display_status === 'approval_pending') {
                    return '承認待ち';
                } else if (request.acceptance_status === 'matched') {
                    return 'マッチング済み';
                } else {
                    return '応募済み';
                }
            }
            return request.status === 'pending' ? '応募待ち' : '応募済み';
        },
        getStatusClass(request) {
            if (request.has_applied) {
                if (request.acceptance_status === 'declined') {
                    return 'status-draft';
                } else if (request.display_status === 'approval_pending') {
                    return 'status-pending';
                } else if (request.acceptance_status === 'matched') {
                    return 'status-matched';
                } else {
                    return 'status-accepted';
                }
            }
            return request.status === 'pending' ? 'status-pending' : 'status-accepted';
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

