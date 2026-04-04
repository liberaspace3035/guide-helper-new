@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 960px; margin: 0 auto; padding: 1rem;">
    <h1>一般公開お知らせの管理</h1>
    <p>
        <a href="{{ route('admin.public-notices.create') }}" class="btn-primary">新規作成</a>
        <a href="{{ route('public-notices.index') }}" class="btn-secondary">公開一覧を見る</a>
    </p>
    @if(session('success'))
        <div class="success-message">{{ session('success') }}</div>
    @endif
    <table class="admin-table" style="width:100%; border-collapse:collapse; margin-top:1rem;">
        <thead>
            <tr>
                <th>日付</th>
                <th>カテゴリ</th>
                <th>タイトル</th>
                <th>表示</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($notices as $n)
                <tr>
                    <td>{{ $n->published_at?->format('Y/m/d') }}</td>
                    <td>{{ $categories[$n->category] ?? $n->category }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($n->title, 36) }}</td>
                    <td>{{ $n->is_visible ? '○' : '—' }}</td>
                    <td>
                        <a href="{{ route('admin.public-notices.edit', $n->id) }}" class="btn-secondary" style="padding:.2rem .5rem; font-size:.85rem;">編集</a>
                        <form method="POST" action="{{ route('admin.public-notices.destroy', $n->id) }}" style="display:inline;" onsubmit="return confirm('削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger" style="padding:.2rem .5rem; font-size:.85rem;">削除</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:1rem;">{{ $notices->links() }}</div>
</div>
@endsection
