@extends('layouts.app')

@section('content')
@php($isEdit = $notice !== null)
<div class="container" style="max-width: 720px; margin: 0 auto; padding: 1rem;">
    <h1>{{ $isEdit ? 'お知らせを編集' : 'お知らせを作成' }}</h1>
    <p><a href="{{ route('admin.public-notices.index') }}">一覧へ</a></p>
    @if($errors->any())
        <div class="error-message">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ $isEdit ? route('admin.public-notices.update', $notice->id) : route('admin.public-notices.store') }}" class="request-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div class="form-group">
            <label for="category">カテゴリ <span class="required">*</span></label>
            <select id="category" name="category" required>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $notice->category ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="title">タイトル <span class="required">*</span></label>
            <input id="title" name="title" type="text" value="{{ old('title', $notice->title ?? '') }}" required maxlength="255">
        </div>
        <div class="form-group">
            <label for="body">本文 <span class="required">*</span></label>
            <textarea id="body" name="body" rows="8" required>{{ old('body', $notice->body ?? '') }}</textarea>
        </div>
        <div class="form-group">
            <label for="detail_url">詳細リンク（任意）</label>
            <input id="detail_url" name="detail_url" type="url" value="{{ old('detail_url', $notice->detail_url ?? '') }}">
        </div>
        <div class="form-group">
            <label for="published_at">公開日時 <span class="required">*</span></label>
            <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', $notice && $notice->published_at ? $notice->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
        </div>
        <div class="form-group">
            <label>
                <input type="hidden" name="is_visible" value="0">
                <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $notice->is_visible ?? true))>
                サイトに表示する
            </label>
        </div>
        <button type="submit" class="btn-primary">{{ $isEdit ? '更新' : '作成' }}</button>
    </form>
</div>
@endsection
