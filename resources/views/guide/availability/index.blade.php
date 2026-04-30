@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 960px; margin: 0 auto; padding: 1rem;">
    <h1>対応可能枠（依頼通知・一覧の絞り込み用）</h1>
    <p class="form-help-text" style="margin-bottom:1rem;">
        プロフィールで「枠に合う依頼のみ通知・一覧表示」をオンにしたとき、ここで登録した日時と重なる依頼だけが通知・依頼一覧に表示されます。指名依頼は対象外です。
    </p>
    <div style="margin-bottom:1rem; display:flex; gap:.5rem; flex-wrap:wrap;">
        <a href="{{ route('guide.availability.create') }}" class="btn-primary">枠を追加</a>
        <a href="{{ route('profile') }}" class="btn-secondary">プロフィールで絞り込みを設定</a>
        <a href="{{ route('calendar.personal.index') }}" class="btn-secondary">マイカレンダー</a>
    </div>
    @if(session('success'))
        <div class="success-message" role="status">{{ session('success') }}</div>
    @endif
    @if($slots->count() === 0)
        <p>登録された枠はありません。</p>
    @else
        <div class="requests-list">
            @foreach($slots as $slot)
                <article class="request-card">
                    <p><strong>開始:</strong> {{ $slot->start_at?->format('Y/m/d H:i') }}</p>
                    <p><strong>終了:</strong> {{ $slot->end_at ? $slot->end_at->format('Y/m/d H:i') : '未設定（開始から1時間として扱います）' }}</p>
                    <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
                        <a href="{{ route('guide.availability.edit', $slot->id) }}" class="btn-secondary">編集</a>
                        <form method="POST" action="{{ route('guide.availability.destroy', $slot->id) }}" onsubmit="return confirm('削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-danger" type="submit">削除</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1rem;">{{ $slots->links() }}</div>
    @endif
</div>
@endsection
