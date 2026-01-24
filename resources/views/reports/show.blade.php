@extends('layouts.app')

@section('content')
<div class="report-detail-container" x-data="reportDetail()" x-init="init()">
    <div class="report-detail-header">
        <h1>報告書確認</h1>
        <a href="{{ route('dashboard') }}" class="back-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>ダッシュボードに戻る</span>
        </a>
    </div>

    <div class="report-card">
        <div class="report-header">
            <div class="report-header-left">
                <div class="report-guide-info">
                    <svg class="guide-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <div>
                        <span class="report-label">ガイド</span>
                        <h2><span x-text="report.guide?.name || '—'"></span></h2>
                    </div>
                </div>
            </div>
            <span class="status-badge" :class="report.status === 'submitted' ? 'status-pending' : 'status-approved'" x-text="report.status === 'submitted' ? '承認待ち' : '承認済み'"></span>
        </div>

        <div class="report-content">
            <div class="report-section">
                <div class="report-section-header">
                    <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <h3>サービス内容</h3>
                </div>
                <div class="report-section-content">
                    <p x-text="report.service_content || '未記入'"></p>
                </div>
            </div>

            <div class="report-section">
                <div class="report-section-header">
                    <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h3>実施日時</h3>
                </div>
                <div class="report-section-content">
                    <div class="date-time-info">
                        <span class="date-value" x-text="report.actual_date || '—'"></span>
                        <template x-if="report.actual_start_time && report.actual_end_time">
                            <span class="time-range" x-text="`${report.actual_start_time.substring(0, 5)} ～ ${report.actual_end_time.substring(0, 5)}`"></span>
                        </template>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <div class="report-section-header">
                    <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <h3>報告欄</h3>
                </div>
                <div class="report-section-content">
                    <p x-text="report.report_content || '未記入'"></p>
                </div>
            </div>
        </div>

        <template x-if="report.status === 'submitted'">
            <div class="report-actions">
                <div class="action-section">
                    <h4 class="action-section-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        承認
                    </h4>
                    <p class="action-section-description">報告書の内容に問題がなければ承認してください</p>
                    <button
                        type="button"
                        @click="handleApprove"
                        class="btn-primary btn-approve"
                        :disabled="processing"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span x-show="!processing">承認</span>
                        <span x-show="processing">処理中...</span>
                    </button>
                </div>

                <div class="revision-section">
                    <h4 class="action-section-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        修正依頼
                    </h4>
                    <p class="action-section-description">修正が必要な場合は、修正内容を記入して送信してください</p>
                    <div class="revision-input-wrapper">
                        <textarea
                            x-model="revisionNotes"
                            placeholder="修正が必要な点を具体的に記入してください"
                            rows="5"
                            class="revision-input"
                        ></textarea>
                    </div>
                    <button
                        type="button"
                        @click="handleRequestRevision"
                        class="btn-secondary btn-revision"
                        :disabled="processing || !revisionNotes.trim()"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span x-show="!processing">修正依頼を送信</span>
                        <span x-show="processing">送信中...</span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/ReportDetail.css') }}">
@endpush

@push('scripts')
<script>
function reportDetail() {
    return {
        report: @json($report),
        revisionNotes: '',
        processing: false,
        init() {
            // 初期化処理
        },
        async handleApprove() {
            if (!confirm('この報告書を承認しますか？')) {
                return;
            }

            this.processing = true;
            try {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'POST');

                const response = await fetch('{{ route("reports.approve", $report->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    alert('報告書を承認しました');
                    window.location.href = '{{ route("dashboard") }}';
                } else {
                    alert('承認処理に失敗しました');
                }
            } catch (err) {
                alert('承認処理に失敗しました');
            } finally {
                this.processing = false;
            }
        },
        async handleRequestRevision() {
            if (!this.revisionNotes.trim()) {
                alert('修正内容を入力してください');
                return;
            }

            if (!confirm('修正依頼を送信しますか？')) {
                return;
            }

            this.processing = true;
            try {
                const formData = new FormData();
                formData.append('revision_notes', this.revisionNotes);
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'POST');

                const response = await fetch('{{ route("reports.request-revision", $report->id) }}', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin', // セッションクッキーを送信（必須）
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    alert('修正依頼を送信しました');
                    window.location.href = '{{ route("dashboard") }}';
                } else {
                    // エラーレスポンスの詳細を確認
                    let errorMessage = '修正依頼の送信に失敗しました';
                    try {
                        const errorData = await response.json();
                        errorMessage = errorData.error || errorData.message || errorMessage;
                        console.error('修正依頼エラー:', errorData);
                    } catch (e) {
                        console.error('レスポンス解析エラー:', e);
                        errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                    }
                    alert(errorMessage);
                }
            } catch (err) {
                console.error('修正依頼送信エラー:', err);
                alert('修正依頼の送信に失敗しました: ' + (err.message || 'ネットワークエラー'));
            } finally {
                this.processing = false;
            }
        }
    }
}
</script>
@endpush




