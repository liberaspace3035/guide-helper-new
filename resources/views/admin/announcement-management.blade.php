<div class="announcement-management">
    <div class="management-header">
        <h1>お知らせ管理</h1>
        <button
            @click="showForm = true"
            class="btn-primary"
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
                    <button type="button" @click="handleCancel()" class="btn-secondary">
                        キャンセル
                    </button>
                    <button type="submit" class="btn-primary">
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
                                <strong x-text="announcement.title"></strong>
                                <span class="content-preview" x-text="announcement.content.substring(0, 50) + '...'"></span>
                            </div>
                        </td>
                        <td>
                            <span class="target-badge" :class="announcement.target_audience" x-text="getTargetLabel(announcement.target_audience)"></span>
                        </td>
                        <td x-text="formatDate(announcement.created_at)"></td>
                        <td x-text="announcement.created_by_name || '不明'"></td>
                        <td>
                            <div class="action-buttons">
                                <button
                                    @click="handleEdit(announcement)"
                                    class="btn-secondary btn-sm"
                                >
                                    編集
                                </button>
                                <button
                                    @click="handleDelete(announcement.id)"
                                    class="btn-secondary btn-sm btn-danger"
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






