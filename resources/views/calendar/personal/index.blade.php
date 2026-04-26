@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 960px; margin: 0 auto; padding: 1rem;">
    <h1>マイカレンダー</h1>
    <div style="margin-bottom:1rem; display:flex; gap:.5rem; flex-wrap:wrap;">
        <a href="{{ route('calendar.personal.create') }}" class="btn-primary">予定を追加</a>
        @if(auth()->user()->isGuide())
            <a href="{{ route('guide.availability.index') }}" class="btn-secondary">対応可能枠（依頼の絞り込み）</a>
        @endif
    </div>
    @if(session('success'))
        <div class="success-message" role="status">{{ session('success') }}</div>
    @endif
    @if(auth()->user()->isGuide())
        <section style="margin-bottom: 1.25rem;">
            <h2 style="font-size: 1.1rem; margin: 0 0 0.5rem;">依頼関連の予定（承諾済み・応募中）</h2>
            <p class="info-text" style="margin-top: 0;">応募一覧・マッチング状況から、今後の依頼予定をまとめて表示しています。</p>
            @if(($guideScheduleItems ?? collect())->isEmpty())
                <p>表示できる依頼予定はありません。</p>
            @else
                <div class="requests-list">
                    @foreach($guideScheduleItems as $item)
                        <article class="request-card">
                            <h3 style="margin-bottom: 0.5rem;">{{ $item['title'] }}</h3>
                            <p>
                                <strong>状態:</strong>
                                <span class="status-badge {{ $item['kind'] === 'accepted' ? 'status-matched' : 'status-pending' }}">{{ $item['status_label'] }}</span>
                            </p>
                            <p><strong>場所:</strong> {{ trim(($item['prefecture'] ?? '') . ' ' . ($item['place'] ?? '')) ?: '未設定' }}</p>
                            <p><strong>日時:</strong> {{ \Carbon\Carbon::parse($item['request_date'])->format('Y/m/d') }}@if(!empty($item['start_time'])) {{ substr($item['start_time'], 0, 5) }}@endif@if(!empty($item['end_time'])) - {{ substr($item['end_time'], 0, 5) }}@endif</p>
                            @if(!empty($item['meeting_place']))
                                <p><strong>待ち合わせ場所:</strong> {{ $item['meeting_place'] }}</p>
                            @endif
                            @if(!empty($item['service_content']))
                                <p><strong>内容:</strong> {{ $item['service_content'] }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
    @if($entries->count() === 0)
        <p>予定はありません。</p>
    @else
        <div class="requests-list">
            @foreach($entries as $entry)
                <article class="request-card">
                    <h2>{{ $entry->title }}</h2>
                    <p><strong>場所:</strong> {{ trim(($entry->prefecture ?? '') . ' ' . ($entry->place ?? '')) ?: '未設定' }}</p>
                    <p><strong>日時:</strong> {{ $entry->start_at?->format('Y/m/d H:i') }}@if($entry->end_at) - {{ $entry->end_at->format('Y/m/d H:i') }}@endif</p>
                    <div class="personal-calendar-card-actions">
                        @if(auth()->user()->isUser())
                            <a href="{{ route('requests.create', ['personal_entry_id' => $entry->id]) }}" class="btn-primary">ガイド依頼を作成</a>
                        @endif
                        <a href="{{ route('calendar.personal.edit', $entry->id) }}" class="btn-secondary">編集</a>
                        <form method="POST" action="{{ route('calendar.personal.destroy', $entry->id) }}" onsubmit="return confirm('削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-danger" type="submit">削除</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1rem;">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
