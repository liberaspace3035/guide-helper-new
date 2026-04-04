@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 760px; margin: 0 auto; padding: 1rem;">
    <h1>イベント編集</h1>
    <p><a href="{{ route('admin.events.index') }}">一覧へ戻る</a></p>

    @if($errors->any())
        <div class="error-message" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.events.update', $event->id) }}" class="request-form">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">タイトル <span class="required">*</span></label>
            <input id="title" name="title" type="text" value="{{ old('title', $event->title) }}" required>
        </div>
        <div class="form-group">
            <label for="category">カテゴリ <span class="required">*</span></label>
            <select id="category" name="category" required>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $event->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="status">公開状態 <span class="required">*</span></label>
            <select id="status" name="status" required>
                <option value="{{ \App\Models\Event::STATUS_PUBLISHED }}" @selected(old('status', $event->status) === \App\Models\Event::STATUS_PUBLISHED)>公開</option>
                <option value="{{ \App\Models\Event::STATUS_PENDING }}" @selected(old('status', $event->status) === \App\Models\Event::STATUS_PENDING)>保留（未公開）</option>
                <option value="{{ \App\Models\Event::STATUS_CANCELLED }}" @selected(old('status', $event->status) === \App\Models\Event::STATUS_CANCELLED)>取り下げ</option>
            </select>
        </div>
        <div class="form-group">
            <label for="prefecture">都道府県</label>
            <input id="prefecture" name="prefecture" type="text" value="{{ old('prefecture', $event->prefecture) }}">
        </div>
        <div class="form-group">
            <label for="place">場所</label>
            <input id="place" name="place" type="text" value="{{ old('place', $event->place) }}">
        </div>
        <div class="form-group">
            <label for="start_at">開始日時 <span class="required">*</span></label>
            <input id="start_at" name="start_at" type="datetime-local" value="{{ old('start_at', $event->start_at?->format('Y-m-d\TH:i')) }}" required>
        </div>
        <div class="form-group">
            <label for="end_at">終了日時</label>
            <input id="end_at" name="end_at" type="datetime-local" value="{{ old('end_at', $event->end_at?->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="form-group">
            <label for="url">URL</label>
            <input id="url" name="url" type="url" value="{{ old('url', $event->url) }}">
        </div>
        <div class="form-group">
            <label for="description">詳細</label>
            <textarea id="description" name="description" rows="5">{{ old('description', $event->description) }}</textarea>
        </div>
        <button type="submit" class="btn-primary">更新する</button>
    </form>
</div>
@endsection
