@extends('layouts.app')

@section('content')
@php
    $ym = $calendarMonth->format('Y-m');
    $prevM = $calendarMonth->copy()->subMonth()->format('Y-m');
    $nextM = $calendarMonth->copy()->addMonth()->format('Y-m');
    $daysInMonth = $calendarMonth->daysInMonth;
    $buildCalUrl = function (array $extra = []) {
        $q = array_merge(request()->except('page'), $extra);
        return route('events.index', $q);
    };
@endphp
<div class="container" style="max-width: 960px; margin: 0 auto; padding: 1rem;">
    <h1>イベントカレンダー（公開）</h1>
    <p>本カレンダーは、どなたでもイベントの閲覧・登録が可能です。</p>
    <p>また、イベントや講座のお知らせなど、他の方にも役立つ情報があれば、自由にご登録ください。</p>
    <p>なお、プライベートなご自身の予定は「マイカレンダー」に追加して管理できます。マイカレンダーはログイン後に利用することができます。</p>

    <div style="margin: 1rem 0; display:flex; gap:.5rem; flex-wrap:wrap;">
        <a href="{{ route('events.create') }}" class="btn-primary">イベントを登録する</a>
        @auth
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.events.index') }}" class="btn-secondary">管理者：イベント一覧・CSV</a>
            @endif
        @endauth
    </div>

    @if(session('success'))
        <div class="success-message" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="error-message" role="alert">{{ $errors->first() }}</div>
    @endif

    @auth
        @if(auth()->user()->isAdmin() && isset($pendingEvents) && $pendingEvents->isNotEmpty())
            <section class="request-card" style="margin-bottom: 1.5rem; border-left: 4px solid #f59e0b;">
                <h2>管理者：未認証・未公開のイベント</h2>
                <p>非会員登録でメール未確認のもの、または手動で保留中のものです。</p>
                @foreach($pendingEvents as $pe)
                    <div style="border-top: 1px solid #e2e8f0; padding: .75rem 0;">
                        <p><strong>{{ $pe->title }}</strong>（ID: {{ $pe->id }}） / 提出メール: {{ $pe->submitter_email ?? '—' }}</p>
                        <p>主催者名: {{ $pe->submitter_name ?? '—' }}</p>
                        <p>開始: {{ $pe->start_at?->format('Y/m/d H:i') }}</p>
                        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                            <form method="POST" action="{{ route('events.admin.publish', $pe->id) }}">
                                @csrf
                                <button type="submit" class="btn-primary">手動で公開</button>
                            </form>
                            <form method="POST" action="{{ route('events.admin.cancel', $pe->id) }}" onsubmit="return confirm('取り下げますか？')">
                                @csrf
                                <button type="submit" class="btn-danger">取り下げ</button>
                            </form>
                            <a href="{{ route('events.show', $pe->id) }}" class="btn-secondary">詳細</a>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    @endauth

    <section class="request-card" style="margin-bottom:1.5rem;">
        <h2 style="margin-top:0;">検索・並び替え</h2>
        <form method="get" action="{{ route('events.index') }}" style="display:flex; flex-direction:column; gap:.75rem;">
            <input type="hidden" name="cal_month" value="{{ $ym }}">
            <div>
                <label for="ev-q">キーワード</label>
                <input id="ev-q" type="search" name="q" value="{{ $search }}" placeholder="タイトル・場所・詳細など" style="width:100%; max-width:28rem;">
            </div>
            <div>
                <label for="ev-cat">カテゴリ</label>
                <select id="ev-cat" name="category" style="max-width:28rem;">
                    <option value="">すべて</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected($selectedCategory === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="ev-sort">並び順</label>
                <select id="ev-sort" name="sort" style="max-width:28rem;">
                    <option value="start_asc" @selected($sort === 'start_asc')>開催日が近い順</option>
                    <option value="start_desc" @selected($sort === 'start_desc')>開催日が遅い順</option>
                    <option value="created_desc" @selected($sort === 'created_desc')>登録が新しい順</option>
                    <option value="created_asc" @selected($sort === 'created_asc')>登録が古い順</option>
                </select>
            </div>
            <div style="display:flex; gap:.5rem;">
                <button type="submit" class="btn-primary">絞り込む</button>
                <a href="{{ route('events.index') }}" class="btn-secondary">クリア</a>
            </div>
        </form>
    </section>

    <section class="request-card" style="margin-bottom:1.5rem;">
        <h2 style="margin-top:0;">カレンダー（月別件数）</h2>
        <p style="color:#64748b; font-size:.9rem;">{{ $calendarMonth->format('Y年n月') }}の公開イベント件数です。</p>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem;">
            <a href="{{ $buildCalUrl(['cal_month' => $prevM]) }}" class="btn-secondary">← 前月</a>
            <strong>{{ $calendarMonth->format('Y年n月') }}</strong>
            <a href="{{ $buildCalUrl(['cal_month' => $nextM]) }}" class="btn-secondary">翌月 →</a>
        </div>
        <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap:2px; font-size:.75rem; text-align:center;">
            @foreach(['日','月','火','水','木','金','土'] as $w)
                <div style="font-weight:700; padding:.25rem;">{{ $w }}</div>
            @endforeach
            @php
                $firstDow = (int) $calendarMonth->copy()->startOfMonth()->format('w');
            @endphp
            @for($i = 0; $i < $firstDow; $i++)
                <div></div>
            @endfor
            @for($d = 1; $d <= $daysInMonth; $d++)
                @php
                    $dateKey = $calendarMonth->format('Y-m') . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT);
                    $cnt = (int) ($calendarCounts[$dateKey] ?? 0);
                @endphp
                <div style="border:1px solid #e2e8f0; padding:.35rem .2rem; min-height:2.5rem; background:{{ $cnt ? '#eff6ff' : '#fafafa' }};">
                    <div>{{ $d }}</div>
                    @if($cnt)
                        <div style="font-weight:700; color:#1d4ed8;">{{ $cnt }}</div>
                    @endif
                </div>
            @endfor
        </div>
    </section>

    <h2>開催予定のイベント（カテゴリ別）</h2>
    @if($groupedUpcoming->isEmpty())
        <p>条件に合う開催予定のイベントはありません。</p>
    @else
        @foreach($categories as $catKey => $catLabel)
            @php $list = $groupedUpcoming->get($catKey, collect()); @endphp
            @if($list->isEmpty())
                @continue
            @endif
            <section style="margin-bottom:2rem;">
                <h3 style="border-bottom:2px solid #e2e8f0; padding-bottom:.35rem;">{{ $catLabel }}</h3>
                <div class="requests-list">
                    @foreach($list as $event)
                        <article class="request-card">
                            <h4 style="margin-bottom:.35rem;">{{ $event->title }}</h4>
                            <p><strong>場所:</strong> {{ trim(($event->prefecture ?? '') . ' ' . ($event->place ?? '')) ?: '未設定' }}</p>
                            <p><strong>日時:</strong> {{ $event->start_at?->format('Y/m/d H:i') }}@if($event->end_at) - {{ $event->end_at->format('Y/m/d H:i') }}@endif</p>
                            <a href="{{ route('events.show', $event->id) }}" class="btn-secondary">詳細を見る</a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif

    <h2 style="margin-top:2rem;">過去のイベント</h2>
    <p style="color:#64748b; font-size:.9rem;">過去のイベントからはガイド依頼・提案の導線は表示されません。</p>
    @if($pastEvents->isEmpty())
        <p>過去のイベントはありません。</p>
    @else
        <div class="requests-list">
            @foreach($pastEvents as $event)
                <article class="request-card" style="opacity:.72; background:#f8fafc;">
                    <p class="event-past-badge" style="font-size:.8rem; color:#64748b;">過去のイベント</p>
                    <h4 style="margin-bottom:.35rem;">{{ $event->title }}</h4>
                    <p><strong>カテゴリ:</strong> {{ $categories[$event->category] ?? $event->category }}</p>
                    <p><strong>場所:</strong> {{ trim(($event->prefecture ?? '') . ' ' . ($event->place ?? '')) ?: '未設定' }}</p>
                    <p><strong>日時:</strong> {{ $event->start_at?->format('Y/m/d H:i') }}</p>
                    <a href="{{ route('events.show', $event->id) }}" class="btn-secondary">詳細のみ表示</a>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
