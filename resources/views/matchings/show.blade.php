@extends('layouts.app')

@section('content')
<div class="matching-detail-container" x-data="matchingData()" x-init="init()">
    <h1>ガイド確定詳細</h1>
    
    <template x-if="loading">
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <p>読み込み中...</p>
        </div>
    </template>

    <template x-if="!loading && !matching">
        <div class="error-message">ガイド確定が見つかりません</div>
    </template>

    <template x-if="!loading && matching">
        <div class="matching-card">
            <div class="matching-info">
                <h2>依頼情報</h2>
                <p><strong>タイプ:</strong> <span x-text="getRequestTypeLabel(matching.request_type)"></span></p>
                <p><strong>場所:</strong> <span x-text="matching.masked_address"></span></p>
                <p><strong>日時:</strong> <span x-text="formatRequestDateTime(matching.request_date, matching.start_time, matching.end_time)"></span></p>
                <template x-if="matching.service_content">
                    <p><strong>サービス内容:</strong> <span x-text="matching.service_content"></span></p>
                </template>
                <template x-if="matching.request_type === 'outing' && matching.meeting_place">
                    <p><strong>待ち合わせ場所:</strong> <span x-text="matching.meeting_place"></span></p>
                </template>
            </div>
            <div class="matching-participants">
                @if(auth()->user()->isUser())
                    <div>
                        <h3>ガイド</h3>
                        <p x-text="matching.guide_name"></p>
                    </div>
                @endif
                @if(auth()->user()->isGuide())
                    <div>
                        <h3>ユーザー</h3>
                        <p x-text="matching.user_name"></p>
                    </div>
                @endif
            </div>
            <div class="matching-actions">
                <a :href="`/chat/${matching.id}`" class="btn-primary btn-with-icon" aria-label="チャットを開く">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>チャットを開く</span>
                </a>
                @if(auth()->user()->isUser())
                <button type="button" @click="blockUser(matching.guide_id, matching.guide_name)" class="btn-danger btn-with-icon" :disabled="blocking" aria-label="このガイドをブロック">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                    </svg>
                    <span x-text="blocking ? 'ブロック中...' : 'このガイドをブロック'"></span>
                </button>
                @endif
                @if(auth()->user()->isGuide())
                <button type="button" @click="blockUser(matching.user_id, matching.user_name)" class="btn-danger btn-with-icon" :disabled="blocking" aria-label="このユーザーをブロック">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                    </svg>
                    <span x-text="blocking ? 'ブロック中...' : 'このユーザーをブロック'"></span>
                </button>
                @endif
            </div>
            <template x-if="blockMessage">
                <div class="block-message" :class="blockMessageType" x-text="blockMessage"></div>
            </template>
        </div>
    </template>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/MatchingDetail.css') }}">
@endpush

@push('scripts')
<script>
function matchingData() {
    return {
        matchingId: {{ $id }},
        matching: null,
        loading: true,
        blocking: false,
        blockMessage: '',
        blockMessageType: '',
        init() {
            this.fetchMatching();
        },
        async fetchMatching() {
            try {
                const response = await fetch('/api/matchings/my-matchings', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                const match = data.matchings?.find(m => m.id === parseInt(this.matchingId));
                this.matching = match;
            } catch (error) {
                console.error('マッチング取得エラー:', error);
            } finally {
                this.loading = false;
            }
        },
        getRequestTypeLabel(type) {
            const map = {
                outing: '外出',
                home: '自宅'
            };
            return map[type] || type;
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
        async blockUser(userId, userName) {
            const targetType = '{{ auth()->user()->isUser() ? 'ガイド' : 'ユーザー' }}';
            const reason = prompt(`${userName}さんをブロックする理由を入力してください（任意）:`);
            if (reason === null) return;
            
            if (!confirm(`${userName}さんをブロックしますか？\n\nブロックすると、今後この${targetType}の依頼や提案が表示されなくなります。`)) return;
            
            this.blocking = true;
            this.blockMessage = '';
            try {
                const res = await fetch('/api/blocks', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ user_id: userId, reason: reason || null })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.blockMessage = `${userName}さんをブロックしました。プロフィール画面でブロック一覧を確認・解除できます。`;
                    this.blockMessageType = 'success-message';
                } else {
                    this.blockMessage = data.error || 'ブロックに失敗しました';
                    this.blockMessageType = 'error-message';
                }
            } catch (e) {
                this.blockMessage = 'ブロックに失敗しました';
                this.blockMessageType = 'error-message';
            } finally {
                this.blocking = false;
            }
        }
    }
}
</script>
@endpush

