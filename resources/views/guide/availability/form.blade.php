@extends('layouts.app')

@section('content')
@php
    $isEdit = $mode === 'edit';
    $startAtVal = old('start_at', $isEdit && $slot->start_at ? $slot->start_at->format('Y-m-d\TH:i') : '');
    $endAtVal = old('end_at', $isEdit && $slot->end_at ? $slot->end_at->format('Y-m-d\TH:i') : '');
@endphp
<div class="container" style="max-width: 760px; margin: 0 auto; padding: 1rem;">
    <h1>{{ $isEdit ? '対応可能枠の編集' : '対応可能枠の追加' }}</h1>
    <p class="form-help-text">依頼の日時と1秒でも重なれば「枠に合う」として扱います。終了を空にすると、開始から1時間の枠として扱います。</p>
    @if($errors->any())
        <div class="error-message" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ $isEdit ? route('guide.availability.update', $slot->id) : route('guide.availability.store') }}" class="request-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div class="form-group">
            <label for="start_at">開始日時 <span class="required">*</span></label>
            <input id="start_at" name="start_at" type="datetime-local" value="{{ $startAtVal }}" required>
        </div>
        <div class="form-group">
            <label for="end_at">終了日時</label>
            <input id="end_at" name="end_at" type="datetime-local" value="{{ $endAtVal }}">
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <button type="submit" class="btn-primary">{{ $isEdit ? '更新' : '追加' }}</button>
            <a href="{{ route('guide.availability.index') }}" class="btn-secondary">一覧へ</a>
        </div>
    </form>
</div>
@endsection
