@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 720px; margin: 2rem auto; padding: 0 1rem;">
    <p><a href="{{ route('admin.support.index') }}">一覧へ戻る</a></p>
    <h1>{{ $participant->name }} さんとのメッセージ</h1>
    <p style="color:#64748b;">{{ $participant->email }} / {{ $participant->role === 'guide' ? 'ガイド' : '利用者' }}</p>

    @if(session('success'))
        <p role="status" style="padding:0.75rem; background:#ecfdf5; border:1px solid #6ee7b7; border-radius:6px;">{{ session('success') }}</p>
    @endif

    <section style="margin:1.5rem 0;" aria-label="やりとり">
        @foreach($messages as $m)
            <article style="margin-bottom:1rem; padding:1rem; border:1px solid #e2e8f0; border-radius:8px; background:{{ $m->is_from_admin ? '#eff6ff' : '#fff' }};">
                <p style="margin:0 0 0.35rem; font-size:0.85rem; color:#64748b;">
                    {{ $m->created_at->format('Y/m/d H:i') }}
                    @if($m->is_from_admin)
                        <strong>運営</strong>
                    @else
                        <strong>相手</strong>
                    @endif
                </p>
                <p style="margin:0; white-space:pre-wrap;">{{ $m->body }}</p>
            </article>
        @endforeach
    </section>

    <form method="post" action="{{ route('admin.support.store', $participant->id) }}" style="display:grid; gap:0.75rem;">
        @csrf
        <div>
            <label for="reply-body">返信</label>
            <textarea id="reply-body" name="body" rows="8" required maxlength="10000" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:6px;"></textarea>
        </div>
        <button type="submit" class="btn-primary">送信する</button>
    </form>
</div>
@endsection
