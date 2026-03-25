@extends('layouts.app')

@section('content')
@php
    $isEdit = $mode === 'edit';
    $prefill = $prefill ?? [];
    if ($isEdit) {
        $startAtVal = old('start_at', $entry->start_at ? $entry->start_at->format('Y-m-d\TH:i') : '');
        $endAtVal = old('end_at', $entry->end_at ? $entry->end_at->format('Y-m-d\TH:i') : '');
    } else {
        $startAtVal = old('start_at', $prefill['start_at'] ?? '');
        $endAtVal = old('end_at', $prefill['end_at'] ?? '');
    }
@endphp
<div class="container" style="max-width: 760px; margin: 0 auto; padding: 1rem;">
    <h1>{{ $isEdit ? 'マイカレンダー編集' : 'マイカレンダー追加' }}</h1>
    @if($errors->any())
        <div class="error-message" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ $isEdit ? route('calendar.personal.update', $entry->id) : route('calendar.personal.store') }}" class="request-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        @if(!$isEdit)
            <input type="hidden" name="event_id" value="{{ old('event_id', $prefill['event_id'] ?? '') }}">
        @endif
        <div class="form-group">
            <label for="title">タイトル <span class="required">*</span></label>
            <input id="title" name="title" type="text" value="{{ old('title', $isEdit ? $entry->title : ($prefill['title'] ?? '')) }}" required>
        </div>
        <div class="form-group">
            <label for="prefecture">都道府県</label>
            <input id="prefecture" name="prefecture" type="text" value="{{ old('prefecture', $isEdit ? $entry->prefecture : ($prefill['prefecture'] ?? '')) }}">
        </div>
        <div class="form-group">
            <label for="place">場所</label>
            <input id="place" name="place" type="text" value="{{ old('place', $isEdit ? $entry->place : ($prefill['place'] ?? '')) }}">
        </div>
        <div class="form-group">
            <label for="start_at">開始日時 <span class="required">*</span></label>
            <input id="start_at" name="start_at" type="datetime-local" value="{{ $startAtVal }}" required>
        </div>
        <div class="form-group">
            <label for="end_at">終了日時</label>
            <input id="end_at" name="end_at" type="datetime-local" value="{{ $endAtVal }}">
        </div>
        <div class="form-group">
            <label for="url">URL</label>
            <input id="url" name="url" type="url" value="{{ old('url', $isEdit ? $entry->url : ($prefill['url'] ?? '')) }}">
        </div>
        <div class="form-group">
            <label for="description">詳細</label>
            <textarea id="description" name="description" rows="5">{{ old('description', $isEdit ? $entry->description : ($prefill['description'] ?? '')) }}</textarea>
        </div>
        <button type="submit" class="btn-primary">{{ $isEdit ? '更新' : '追加' }}</button>
    </form>
</div>
@endsection
