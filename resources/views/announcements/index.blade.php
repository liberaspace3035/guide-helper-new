@extends('layouts.app')

@section('content')
<div class="announcements-page" x-data="announcementsData()" x-init="init()">
    <div class="announcements-header">
        <div>
            <h1>運営からのお知らせ</h1>
            <p class="announcements-subtitle">過去のお知らせ一覧</p>
        </div>
        <template x-if="unreadCount > 0">
            <div class="unread-count-badge" x-text="`${unreadCount}件の未読`"></div>
        </template>
    </div>

    <div class="announcements-filters">
        <button
            class="filter-btn"
            :class="{ active: filter === 'all' }"
            @click="filter = 'all'"
        >
            すべて
        </button>
        <button
            class="filter-btn"
            :class="{ active: filter === 'unread' }"
            @click="filter = 'unread'"
        >
            未読
            <template x-if="unreadCount > 0">
                <span class="filter-count" x-text="unreadCount"></span>
            </template>
        </button>
        <button
            class="filter-btn"
            :class="{ active: filter === 'read' }"
            @click="filter = 'read'"
        >
            既読
        </button>
    </div>

    <div class="announcements-list">
        <template x-if="filteredAnnouncements.length === 0">
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <h3 x-text="filter === 'unread' ? '未読のお知らせはありません' : filter === 'read' ? '既読のお知らせはありません' : 'お知らせがありません'"></h3>
            </div>
        </template>

        <template x-if="filteredAnnouncements.length > 0">
            <template x-for="announcement in filteredAnnouncements" :key="announcement.id">
                <article
                    class="announcement-card"
                    :class="{ 'unread': !announcement.is_read }"
                    :aria-label="'お知らせ、' + (announcement.title || '') + '、' + (announcement.is_read ? '既読' : '未読')"
                    role="article"
                    @click="handleRead(announcement.id)"
                >
                    <div class="announcement-header">
                        <h3 x-text="announcement.title"></h3>
                        <template x-if="!announcement.is_read">
                            <span class="unread-badge" aria-label="未読">未読</span>
                        </template>
                        <template x-if="announcement.is_read">
                            <span class="read-badge sr-only" aria-hidden="true">既読</span>
                        </template>
                    </div>
                    <div class="announcement-content" x-html="announcement.content"></div>
                    <div class="announcement-footer">
                        <span class="announcement-date" x-text="formatDate(announcement.created_at)"></span>
                        <template x-if="announcement.is_read">
                            <button
                                type="button"
                                class="btn-unread"
                                @click.stop="handleUnread(announcement.id)"
                                aria-label="このお知らせを未読に戻す"
                            >
                                未読に戻す
                            </button>
                        </template>
                    </div>
                </article>
            </template>
        </template>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Announcements.css') }}">
@endpush

@push('scripts')
<script>
function announcementsData() {
    return {
        announcements: @json($announcements),
        filter: 'all',
        init() {
            // 初期化処理
        },
        get filteredAnnouncements() {
            if (this.filter === 'unread') {
                return this.announcements.filter(a => !a.is_read);
            }
            if (this.filter === 'read') {
                return this.announcements.filter(a => a.is_read);
            }
            return this.announcements;
        },
        get unreadCount() {
            return this.announcements.filter(a => !a.is_read).length;
        },
        async handleRead(announcementId) {
            const announcement = this.announcements.find(a => a.id === announcementId);
            if (announcement && !announcement.is_read) {
                try {
                    const res = await fetch('{{ url("/announcements") }}/' + announcementId + '/read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        announcement.is_read = 1;
                        announcement.read_at = new Date().toISOString();
                    }
                } catch (err) {
                    console.error('既読登録エラー:', err);
                }
            }
        },
        async handleUnread(announcementId) {
            const announcement = this.announcements.find(a => a.id === announcementId);
            if (announcement && announcement.is_read) {
                try {
                    const res = await fetch('{{ url("/announcements") }}/' + announcementId + '/unread', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        announcement.is_read = 0;
                        announcement.read_at = null;
                    } else {
                        const data = await res.json().catch(() => ({}));
                        alert(data.error || '未読に戻せませんでした');
                    }
                } catch (err) {
                    console.error('未読に戻すエラー:', err);
                    alert('未読に戻せませんでした');
                }
            }
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleString('ja-JP');
        }
    }
}
</script>
@endpush






