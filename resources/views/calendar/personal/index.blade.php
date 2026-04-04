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
    @if($entries->count() === 0)
        <p>予定はありません。</p>
    @else
        <div class="requests-list">
            @foreach($entries as $entry)
                <article class="request-card">
                    <h2>{{ $entry->title }}</h2>
                    <p><strong>場所:</strong> {{ trim(($entry->prefecture ?? '') . ' ' . ($entry->place ?? '')) ?: '未設定' }}</p>
                    <p><strong>日時:</strong> {{ $entry->start_at?->format('Y/m/d H:i') }}@if($entry->end_at) - {{ $entry->end_at->format('Y/m/d H:i') }}@endif</p>
                    <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
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
