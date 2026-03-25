@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 760px; margin: 0 auto; padding: 1rem;">
    <h1>イベント登録</h1>
    <p>非会員でも登録できます。非会員登録時はメール認証後に公開されます。</p>

    @if($errors->any())
        <div class="error-message" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('events.store') }}" class="request-form">
        @csrf
        @guest
            <div class="form-group">
                <label for="submitter_email">メールアドレス <span class="required">*</span></label>
                <input id="submitter_email" name="submitter_email" type="email" value="{{ old('submitter_email') }}" required>
            </div>
        @endguest
        <div class="form-group">
            <label for="title">タイトル <span class="required">*</span></label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required>
        </div>
        <div class="form-group">
            <label for="prefecture">都道府県</label>
            <input id="prefecture" name="prefecture" type="text" value="{{ old('prefecture') }}" placeholder="例: 東京都">
        </div>
        <div class="form-group">
            <label for="place">場所</label>
            <input id="place" name="place" type="text" value="{{ old('place') }}" placeholder="例: 新宿区西新宿1-1-1">
        </div>
        <div class="form-group">
            <label for="start_at">開始日時 <span class="required">*</span></label>
            <input id="start_at" name="start_at" type="datetime-local" value="{{ old('start_at') }}" required>
        </div>
        <div class="form-group">
            <label for="end_at">終了日時</label>
            <input id="end_at" name="end_at" type="datetime-local" value="{{ old('end_at') }}">
        </div>
        <div class="form-group">
            <label for="url">関連URL</label>
            <input id="url" name="url" type="url" value="{{ old('url') }}">
        </div>
        <div class="form-group">
            <label for="description">詳細</label>
            <textarea id="description" name="description" rows="5">{{ old('description') }}</textarea>
        </div>
        <button type="submit" class="btn-primary">登録する</button>
    </form>
</div>
@endsection
