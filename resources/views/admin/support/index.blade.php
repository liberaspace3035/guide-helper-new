@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 960px; margin: 2rem auto; padding: 0 1rem;">
    <p><a href="{{ route('admin.dashboard') }}">管理ダッシュボードへ戻る</a></p>
    <h1>個別メッセージ（運営）</h1>

    @if(session('success'))
        <p role="status" style="padding:0.75rem; background:#ecfdf5; border:1px solid #6ee7b7; border-radius:6px;">{{ session('success') }}</p>
    @endif
    @if(!empty($supportSetupWarning))
        <p role="alert" style="padding:0.75rem; background:#fff7ed; border:1px solid #fdba74; border-radius:6px; color:#9a3412;">
            {{ $supportSetupWarning }}
        </p>
    @endif

    <section style="margin:2rem 0; padding:1rem; border:1px solid #e2e8f0; border-radius:8px;">
        <h2 style="margin-top:0;">自動返信メールの定型文</h2>
        <p style="color:#64748b; font-size:0.95rem;">利用者・ガイドがメッセージを送った直後に、登録メール宛へ送る文面です。<code>{{ '{{name}}' }}</code> は送信者の表示名に置き換わります。</p>
        <form method="post" action="{{ route('admin.support.auto-reply') }}" style="display:grid; gap:0.75rem;">
            @csrf
            <div>
                <label for="support_auto_reply_subject">件名</label>
                <input id="support_auto_reply_subject" type="text" name="support_auto_reply_subject" value="{{ old('support_auto_reply_subject', $autoReplySubject) }}" required maxlength="255" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <div>
                <label for="support_auto_reply_body">本文</label>
                <textarea id="support_auto_reply_body" name="support_auto_reply_body" rows="10" required maxlength="20000" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:6px;">{{ old('support_auto_reply_body', $autoReplyBody) }}</textarea>
            </div>
            <button type="submit" class="btn-primary">定型文を保存</button>
        </form>
    </section>

    <section style="margin:2rem 0;">
        <h2>運営から新規に送る（相手を選ぶ）</h2>
        <form method="post" action="{{ route('admin.support.new') }}" style="display:grid; gap:0.75rem; max-width:640px;">
            @csrf
            <div>
                <label for="target_user_id">送信先</label>
                <select id="target_user_id" name="target_user_id" required style="width:100%; padding:0.5rem;">
                    <option value="">選択してください</option>
                    @foreach($allTargets as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}（{{ $u->role === 'guide' ? 'ガイド' : '利用者' }} / ID {{ $u->id }}）</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="new-body">本文</label>
                <textarea id="new-body" name="body" rows="6" required maxlength="10000" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:6px;"></textarea>
            </div>
            <button type="submit" class="btn-primary">送信</button>
        </form>
    </section>

    <section>
        <h2>スレッド一覧</h2>
        @if($threads->isEmpty())
            <p>まだメッセージはありません。</p>
        @else
            <ul style="list-style:none; padding:0; margin:0;">
                @foreach($threads as $t)
                    @php($u = $t['user'])
                    <li style="margin-bottom:0.75rem; padding:0.75rem 1rem; border:1px solid #e2e8f0; border-radius:8px;">
                        <a href="{{ route('admin.support.show', $u->id) }}" style="font-weight:600;">{{ $u->name }}</a>
                        <span style="color:#64748b; font-size:0.9rem;">（{{ $u->role === 'guide' ? 'ガイド' : '利用者' }}）</span>
                        @if($t['last_message'])
                            <p style="margin:0.35rem 0 0; font-size:0.9rem; color:#475569;">{{ $t['last_message']->created_at->format('Y/m/d H:i') }} — {{ \Illuminate\Support\Str::limit($t['last_message']->body, 80) }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
