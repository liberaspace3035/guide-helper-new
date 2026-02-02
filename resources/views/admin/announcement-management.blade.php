<div class="announcement-management">
    <div class="management-header">
        <h1>お知らせ管理</h1>
        <button
            @click="showForm = true"
            class="btn-primary btn-new-announcement"
            :disabled="showForm"
        >
            + 新規お知らせ
        </button>
    </div>

    <template x-if="showForm">
        <div class="announcement-form-container">
            <h2 x-text="editingId ? 'お知らせを編集' : '新規お知らせを作成'"></h2>
            <form @submit.prevent="handleSubmit()" class="announcement-form">
                <div class="form-group">
                    <label for="title">タイトル *</label>
                    <input
                        type="text"
                        id="title"
                        x-model="formData.title"
                        required
                        placeholder="お知らせのタイトルを入力"
                    />
                </div>

                <div class="form-group">
                    <label for="content">本文 *</label>
                    <textarea
                        id="content"
                        x-model="formData.content"
                        required
                        rows="8"
                        placeholder="お知らせの本文を入力"
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="target_audience">対象者 *</label>
                    <select
                        id="target_audience"
                        x-model="formData.target_audience"
                        required
                    >
                        <option value="all">全体向け</option>
                        <option value="user">ユーザー向け</option>
                        <option value="guide">ガイド向け</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" @click="handleCancel()" class="btn-secondary btn-form-action">
                        キャンセル
                    </button>
                    <button type="submit" class="btn-primary btn-form-action">
                        <span x-text="editingId ? '更新' : '作成'"></span>
                    </button>
                </div>
            </form>
        </div>
    </template>

    <div class="announcements-table-container">
        <table class="announcements-table">
            <thead>
                <tr>
                    <th>タイトル</th>
                    <th>対象者</th>
                    <th>作成日時</th>
                    <th>作成者</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="announcements.length === 0">
                    <tr>
                        <td colspan="5" class="empty-message">
                            お知らせがありません
                        </td>
                    </tr>
                </template>
                <template x-for="announcement in announcements" :key="announcement.id">
                    <tr>
                        <td class="title-cell">
                            <div class="title-content">
                                <strong class="announcement-title-bold" x-text="announcement.title"></strong>
                                <span class="content-preview" x-text="announcement.content.substring(0, 50) + '...'"></span>
                            </div>
                        </td>
                        <td>
                            <span class="target-badge" :class="announcement.target_audience" x-text="getTargetLabel(announcement.target_audience)"></span>
                        </td>
                        <td>
                            <div class="datetime-cell-vertical">
                                <span class="datetime-date" x-text="formatDateOnly(announcement.created_at)"></span>
                                <span class="datetime-time" x-text="formatTimeOnly(announcement.created_at)"></span>
                            </div>
                        </td>
                        <td>
                            <span class="announcement-creator-name" x-text="announcement.created_by_name || '不明'"></span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button
                                    @click="handleEdit(announcement)"
                                    class="btn-edit-announcement"
                                >
                                    編集
                                </button>
                                <button
                                    @click="handleDelete(announcement.id)"
                                    class="btn-delete-announcement"
                                >
                                    削除
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // formatDateOnlyとformatTimeOnly関数をグローバルに追加
    window.formatDateOnly = function(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}.${month}.${day}`;
    };
    
    window.formatTimeOnly = function(timeStr) {
        if (!timeStr) return '';
        
        // 日時文字列の場合（ISO形式など）
        if (timeStr.includes('T') || timeStr.includes(' ')) {
            const date = new Date(timeStr);
            if (isNaN(date.getTime())) return '';
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${hours}:${minutes}`;
        }
        
        // 時間文字列の場合（HH:MM形式）
        const timeMatch = timeStr.match(/^(\d{1,2}):(\d{2})/);
        if (timeMatch) {
            const hours = parseInt(timeMatch[1], 10);
            const minutes = timeMatch[2];
            return `${String(hours).padStart(2, '0')}:${minutes}`;
        }
        return '';
    };
});
</script>
@endpush
