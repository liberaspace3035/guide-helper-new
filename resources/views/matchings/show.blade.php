@extends('layouts.app')

@section('content')
<div class="matching-detail-container" x-data="matchingData()" x-init="init()">
    <h1>マッチング詳細</h1>
    
    <template x-if="loading">
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <p>読み込み中...</p>
        </div>
    </template>

    <template x-if="!loading && !matching">
        <div class="error-message">マッチングが見つかりません</div>
    </template>

    <template x-if="!loading && matching">
        <div class="matching-card">
            <div class="matching-info">
                <h2>依頼情報</h2>
                <p><strong>タイプ:</strong> <span x-text="getRequestTypeLabel(matching.request_type)"></span></p>
                <p><strong>場所:</strong> <span x-text="matching.masked_address"></span></p>
                <p><strong>日時:</strong> <span x-text="matching.request_date + ' ' + matching.request_time"></span></p>
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
            </div>
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
        }
    }
}
</script>
@endpush

