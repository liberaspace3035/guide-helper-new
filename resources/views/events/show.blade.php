@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 900px; margin: 0 auto; padding: 1rem;">
    @if(session('success'))
        <div class="success-message" role="status">{{ session('success') }}</div>
    @endif
    <h1>{{ $event->title }}</h1>
    @if($event->isPast())
        <p class="event-past-badge" style="padding:.35rem .75rem; background:#e2e8f0; color:#475569; display:inline-block; border-radius:4px; font-size:.9rem;">過去のイベント</p>
    @endif
    <p><strong>カテゴリ:</strong> {{ \App\Models\Event::CATEGORIES[$event->category] ?? $event->category }}</p>
    <p><strong>場所:</strong> {{ trim(($event->prefecture ?? '') . ' ' . ($event->place ?? '')) ?: '未設定' }}</p>
    <p><strong>日時:</strong> {{ $event->start_at?->format('Y/m/d H:i') }}@if($event->end_at) - {{ $event->end_at->format('Y/m/d H:i') }}@endif</p>
    @if($event->url)
        <p><strong>URL:</strong> <a href="{{ $event->url }}" target="_blank" rel="noopener noreferrer">{{ $event->url }}</a></p>
    @endif
    @if($event->description)
        <p><strong>詳細:</strong><br>{!! nl2br(e($event->description)) !!}</p>
    @endif

    <div style="display:flex; gap:.75rem; flex-wrap: wrap; margin-top:1rem;">
        @unless($event->isPast())
            @auth
                @if(auth()->user()->isUser())
                    <a class="btn-primary" href="{{ route('requests.create', ['event_id' => $event->id]) }}">このイベントのガイドを募集する</a>
                    <a class="btn-secondary" href="{{ route('calendar.personal.create', ['event_id' => $event->id]) }}">このイベントをマイカレンダーに追加する</a>
                @elseif(auth()->user()->isGuide())
                    <a class="btn-primary" href="{{ route('guide.requests.index', ['event_id' => $event->id]) }}">このイベントのガイドを提案する</a>
                    <a class="btn-secondary" href="{{ route('calendar.personal.create', ['event_id' => $event->id]) }}">このイベントをマイカレンダーに追加する</a>
                @else
                    <a class="btn-secondary" href="{{ route('calendar.personal.create', ['event_id' => $event->id]) }}">このイベントをマイカレンダーに追加する</a>
                @endif
            @else
                <a class="btn-primary" href="{{ route('register', ['role' => 'guide', 'from_event' => $event->id]) }}">このイベントにガイドを依頼したい場合は登録する</a>
            @endauth
        @else
            <p style="color:#64748b; font-size:.95rem;">過去のイベントのため、ガイド依頼・提案の導線は表示していません。マイカレンダーへの追加はログイン後にご利用ください。</p>
            @auth
                <a class="btn-secondary" href="{{ route('calendar.personal.create', ['event_id' => $event->id]) }}">この内容をマイカレンダーに追加する</a>
            @endauth
        @endunless
    </div>
</div>
@endsection
