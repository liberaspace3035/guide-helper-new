@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 720px; margin: 2rem auto; padding: 0 1rem;">
    <h1>運営へのメッセージ</h1>
    <p style="color:#475569; line-height:1.6;">ご質問・ご相談はこちらから送信できます。送信後、登録メールアドレスに自動返信が届きます。運営は内容を確認し、必要に応じてアプリ内通知およびメールでご連絡します。</p>

    @if(session('success'))
        <div class="error-message" role="status" style="background:#ecfdf5; border-color:#6ee7b7; color:#065f46;">{{ session('success') }}</div>
    @endif

    <section style="margin: 1.5rem 0;" aria-label="これまでのやりとり">
        <h2 style="font-size:1.1rem;">やりとり</h2>
        @forelse($messages as $m)
            <article style="margin-bottom:1rem; padding:1rem; border:1px solid #e2e8f0; border-radius:8px; background:{{ $m->is_from_admin ? '#f8fafc' : '#fff' }};">
                <p style="margin:0 0 0.35rem; font-size:0.85rem; color:#64748b;">
                    {{ $m->created_at->format('Y/m/d H:i') }}
                    @if($m->is_from_admin)
                        <strong>運営</strong>
                    @else
                        <strong>あなた</strong>
                    @endif
                </p>
                <p style="margin:0; white-space:pre-wrap;">{{ $m->body }}</p>
            </article>
        @empty
            <p>まだメッセージはありません。下のフォームから送信してください。</p>
        @endforelse
    </section>

    <section aria-label="新規送信">
        <h2 style="font-size:1.1rem;">メッセージを送る</h2>
        <form method="post" action="{{ route('support.store') }}" style="display:grid; gap:0.75rem;">
            @csrf
            <div>
                <label for="support-body">内容</label>
                <textarea id="support-body" name="body" rows="8" required maxlength="10000" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:6px;">{{ old('body') }}</textarea>
                @error('body')
                    <p class="error-message-field" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-primary">送信する</button>
        </form>
    </section>
</div>
@endsection
