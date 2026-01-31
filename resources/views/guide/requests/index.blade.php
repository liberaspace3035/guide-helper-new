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
                        <div class="status-badge-wrapper">
                            <span class="status-badge" :class="getStatusClass(request)" :aria-label="getStatusLabel(request) + 'の状態'">
                                <span class="status-icon" x-html="getStatusIcon(request)"></span>
                                <span x-text="getStatusLabel(request)"></span>
                            </span>
                        </div>
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
                'NetworkError',
                'Network request failed',
                'TypeError: Failed to fetch'
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
                    return 'マッチング確定';
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

