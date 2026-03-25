@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 960px; margin: 0 auto; padding: 1rem;">
    <h1>イベントカレンダー（公開）</h1>
    <p>利用者・ガイド・管理者・未登録の方が閲覧できます。</p>

    <div style="margin: 1rem 0;">
        <a href="{{ route('events.create') }}" class="btn-primary">イベントを登録する</a>
    </div>

    @if(session('success'))
        <div class="success-message" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="error-message" role="alert">{{ $errors->first() }}</div>
    @endif

    @auth
        @if(auth()->user()->isAdmin() && isset($pendingEvents) && $pendingEvents->isNotEmpty())
            <section class="request-card" style="margin-bottom: 1.5rem; border-left: 4px solid #f59e0b;">
                <h2>管理者：未認証・未公開のイベント</h2>
                <p>非会員登録でメール未確認のもの、または手動で保留中のものです。公開または取り下げできます。</p>
                @foreach($pendingEvents as $pe)
                    <div style="border-top: 1px solid #e2e8f0; padding: .75rem 0;">
                        <p><strong>{{ $pe->title }}</strong>（ID: {{ $pe->id }}） / 提出メール: {{ $pe->submitter_email ?? '—' }}</p>
                        <p>開始: {{ $pe->start_at?->format('Y/m/d H:i') }}</p>
                        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                            <form method="POST" action="{{ route('events.admin.publish', $pe->id) }}">
                                @csrf
                                <button type="submit" class="btn-primary">手動で公開</button>
                            </form>
                            <form method="POST" action="{{ route('events.admin.cancel', $pe->id) }}" onsubmit="return confirm('取り下げますか？')">
                                @csrf
                                <button type="submit" class="btn-danger">取り下げ</button>
                            </form>
                            <a href="{{ route('events.show', $pe->id) }}" class="btn-secondary">詳細</a>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    @endauth

    @if($events->count() === 0)
        <p>公開中のイベントはありません。</p>
    @else
        <div class="requests-list">
            @foreach($events as $event)
                <article class="request-card">
                    <h2 style="margin-bottom: .5rem;">{{ $event->title }}</h2>
                    <p><strong>場所:</strong> {{ trim(($event->prefecture ?? '') . ' ' . ($event->place ?? '')) ?: '未設定' }}</p>
                    <p><strong>日時:</strong> {{ $event->start_at?->format('Y/m/d H:i') }}@if($event->end_at) - {{ $event->end_at->format('Y/m/d H:i') }}@endif</p>
                    <a href="{{ route('events.show', $event->id) }}" class="btn-secondary">詳細を見る</a>
                </article>
            @endforeach
        </div>
        <div style="margin-top: 1rem;">{{ $events->links() }}</div>
    @endif
</div>
@endsection
