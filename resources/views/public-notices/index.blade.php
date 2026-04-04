@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 800px; margin: 0 auto; padding: 1rem;">
    <h1>お知らせ（一般公開）</h1>
    <p><a href="{{ route('home') }}">トップへ戻る</a></p>

    <form method="get" style="margin-bottom:1rem;">
        <label for="ncat">カテゴリ</label>
        <select id="ncat" name="category" onchange="this.form.submit()">
            <option value="">すべて</option>
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    @if($notices->isEmpty())
        <p>お知らせはありません。</p>
    @else
        <div class="requests-list">
            @foreach($notices as $n)
                <article class="request-card">
                    <p style="font-size:.85rem; color:#64748b;">
                        {{ $n->published_at?->format('Y/m/d') }}
                        · {{ $n->getCategoryLabel() }}
                    </p>
                    <h2 style="font-size:1.1rem; margin:.35rem 0;">{{ $n->title }}</h2>
                    <div style="white-space:pre-wrap;">{{ $n->body }}</div>
                    @if($n->detail_url)
                        <p style="margin-top:.75rem;"><a href="{{ $n->detail_url }}" target="_blank" rel="noopener noreferrer">詳細リンク</a></p>
                    @endif
                </article>
            @endforeach
        </div>
        <div style="margin-top:1rem;">{{ $notices->links() }}</div>
    @endif
</div>
@endsection
