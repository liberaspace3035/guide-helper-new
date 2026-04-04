@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1100px; margin: 0 auto; padding: 1rem;">
    <h1>イベント管理</h1>
    <p><a href="{{ route('events.index') }}">公開カレンダーへ</a></p>

    @if(session('success'))
        <div class="success-message" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="error-message" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="request-card" style="margin:1rem 0;">
        <h2>CSV一括登録</h2>
        <p style="font-size:.9rem;">1行目ヘッダ必須: <code>title,category,start_at</code> ほか <code>prefecture,place,end_at,url,description</code></p>
        <p style="font-size:.9rem;">category はキー（例: <code>outing_experience</code>）を指定してください。</p>
        <form method="POST" action="{{ route('admin.events.import-csv') }}" enctype="multipart/form-data" style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <div>
                <label for="csv_file">CSVファイル</label><br>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required>
            </div>
            <button type="submit" class="btn-primary">取り込む</button>
        </form>
    </section>

    <form method="POST" id="bulk-delete-form" action="{{ route('admin.events.bulk-destroy') }}">
        @csrf
    </form>
    <button type="submit" form="bulk-delete-form" class="btn-danger" style="margin-bottom:.75rem;" onclick="return confirm('選択したイベントを削除しますか？');">選択したイベントを削除</button>

    <table class="admin-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th><input type="checkbox" aria-label="すべて選択" onclick="document.querySelectorAll('.ev-cb').forEach(c => { c.checked = this.checked; })"></th>
                <th>ID</th>
                <th>タイトル</th>
                <th>カテゴリ</th>
                <th>開始</th>
                <th>状態</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
                <tr>
                    <td><input class="ev-cb" type="checkbox" name="ids[]" value="{{ $event->id }}" form="bulk-delete-form"></td>
                    <td>{{ $event->id }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($event->title, 40) }}</td>
                    <td>{{ $categories[$event->category] ?? $event->category }}</td>
                    <td>{{ $event->start_at?->format('Y/m/d H:i') }}</td>
                    <td>{{ $event->status }}</td>
                    <td style="white-space:nowrap;">
                        <a href="{{ route('admin.events.edit', $event->id) }}" class="btn-secondary" style="padding:.2rem .5rem; font-size:.85rem;">編集</a>
                        <form method="POST" action="{{ route('admin.events.destroy', $event->id) }}" style="display:inline;" onsubmit="return confirm('削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger" style="padding:.2rem .5rem; font-size:.85rem;">削除</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:1rem;">{{ $events->links() }}</div>
</div>
@endsection
