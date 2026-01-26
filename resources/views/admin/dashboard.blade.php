@extends('layouts.app')

@section('content')
<div class="admin-dashboard" x-data="adminDashboard()" x-init="init()">
    <div class="admin-dashboard-header">
        <h1>管理画面</h1>
        <p class="admin-welcome-message">システム全体の管理と設定を行います</p>
    </div>

    <!-- タブナビゲーション -->
    <div class="admin-tabs">
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'dashboard' }"
            @click="activeTab = 'dashboard'"
        >
            ダッシュボード
        </button>
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'users' }"
            @click="activeTab = 'users'; if (users.length === 0) fetchUsers()"
        >
            ユーザー管理
        </button>
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'guides' }"
            @click="activeTab = 'guides'; if (guides.length === 0) fetchGuides()"
        >
            ガイド管理
        </button>
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'announcements' }"
            @click="activeTab = 'announcements'"
        >
            お知らせ管理
        </button>
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'monthly-limits' }"
            @click="if (activeTab !== 'monthly-limits') { activeTab = 'monthly-limits'; if (users.length === 0) fetchUsers(); else { fetchAllUserCurrentLimits(); } }"
        >
            限度時間管理
        </button>
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'email-templates' }"
            @click="activeTab = 'email-templates'; if (emailTemplates.length === 0) fetchEmailTemplates()"
        >
            メールテンプレート
        </button>
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'email-settings' }"
            @click="activeTab = 'email-settings'; if (emailSettings.length === 0) fetchEmailSettings()"
        >
            メール通知設定
        </button>
        <button
            class="admin-tab"
            :class="{ active: activeTab === 'operation-logs' }"
            @click="activeTab = 'operation-logs'; if (operationLogs.length === 0) fetchOperationLogs()"
        >
            操作ログ
        </button>
    </div>

    <!-- タブコンテンツラッパー -->
    <div class="admin-tab-content">
        <!-- ダッシュボードタブ -->
        <template x-if="activeTab === 'dashboard'">
            <div>
                <!-- 統計情報セクション -->
                <template x-if="stats">
                    <section class="admin-section stats-section">
                        <div class="section-header">
                            <h2>
                                <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 21H3"></path>
                                    <path d="M21 21V10"></path>
                                    <path d="M3 21V10"></path>
                                    <path d="M7 21V14"></path>
                                    <path d="M11 21V6"></path>
                                    <path d="M15 21V10"></path>
                                    <path d="M19 21V4"></path>
                                </svg>
                                統計情報
                            </h2>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h3>ユーザー</h3>
                                <div class="stat-content">
                                    <div class="stat-item">
                                        <span class="stat-label">総数:</span>
                                        <span class="stat-value" x-text="stats.users?.total || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">承認済み:</span>
                                        <span class="stat-value approved" x-text="stats.users?.approved || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">未承認:</span>
                                        <span class="stat-value pending" x-text="stats.users?.pending || 0"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h3>ガイド</h3>
                                <div class="stat-content">
                                    <div class="stat-item">
                                        <span class="stat-label">総数:</span>
                                        <span class="stat-value" x-text="stats.guides?.total || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">承認済み:</span>
                                        <span class="stat-value approved" x-text="stats.guides?.approved || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">未承認:</span>
                                        <span class="stat-value pending" x-text="stats.guides?.pending || 0"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h3>マッチング</h3>
                                <div class="stat-content">
                                    <div class="stat-item">
                                        <span class="stat-label">総数:</span>
                                        <span class="stat-value" x-text="stats.matchings?.total || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">マッチング済み:</span>
                                        <span class="stat-value" x-text="stats.matchings?.matched || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">進行中:</span>
                                        <span class="stat-value" x-text="stats.matchings?.in_progress || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">完了:</span>
                                        <span class="stat-value approved" x-text="stats.matchings?.completed || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">キャンセル:</span>
                                        <span class="stat-value" x-text="stats.matchings?.cancelled || 0"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <h3>依頼</h3>
                                <div class="stat-content">
                                    <div class="stat-item">
                                        <span class="stat-label">総数:</span>
                                        <span class="stat-value" x-text="stats.requests?.total || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">待機中:</span>
                                        <span class="stat-value pending" x-text="stats.requests?.pending || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">ガイド承諾済み:</span>
                                        <span class="stat-value" x-text="stats.requests?.guide_accepted || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">マッチング済み:</span>
                                        <span class="stat-value" x-text="stats.requests?.matched || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">進行中:</span>
                                        <span class="stat-value" x-text="stats.requests?.in_progress || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">完了:</span>
                                        <span class="stat-value approved" x-text="stats.requests?.completed || 0"></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">キャンセル:</span>
                                        <span class="stat-value" x-text="stats.requests?.cancelled || 0"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

                <section class="admin-section settings-section">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            設定
                        </h2>
                    </div>
                    <div class="setting-item">
                        <label>
                            <input
                                type="checkbox"
                                x-model="autoMatching"
                                @change="toggleAutoMatching"
                            />
                            自動マッチング
                        </label>
                        <p class="setting-description" x-text="autoMatching ? 'ガイドが承諾すると自動的にマッチングが成立します' : 'ガイドが承諾しても管理者の承認が必要です'"></p>
                    </div>
                </section>

                <section class="admin-section acceptances-section">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            承諾待ち一覧
                        </h2>
                        <template x-if="acceptances.length > 0">
                            <span class="pending-count" x-text="acceptances.length + '件'"></span>
                        </template>
                    </div>
                    <template x-if="acceptances.length === 0">
                        <div class="empty-state-small">
                            <p>承諾待ちの依頼はありません</p>
                        </div>
                    </template>
                    <template x-if="acceptances.length > 0">
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>依頼ID</th>
                                        <th>ユーザー</th>
                                        <th>ガイド</th>
                                        <th>日時</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="acc in acceptances" :key="acc.id">
                                        <tr>
                                            <td x-text="acc.request_id"></td>
                                            <td x-text="acc.user_name"></td>
                                            <td x-text="acc.guide_name"></td>
                                            <td x-text="formatRequestDateTime(acc.request_date, acc.request_time)"></td>
                                            <td>
                                                <button
                                                    @click="approveMatching(acc.request_id, acc.guide_id, acc.user_selected)"
                                                    class="btn-primary btn-sm"
                                                >
                                                    承認
                                                </button>
                                                <button
                                                    @click="rejectMatching(acc.request_id, acc.guide_id)"
                                                    class="btn-secondary btn-sm"
                                                >
                                                    却下
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </section>

                <section class="admin-section reports-section">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            報告書一覧
                        </h2>
                    </div>
                    <div class="section-actions">
                        <button
                            @click="exportCSV('reports')"
                            class="btn-secondary"
                        >
                            報告書CSV出力
                        </button>
                        <button
                            @click="exportCSV('usage')"
                            class="btn-secondary"
                        >
                            利用実績CSV出力
                        </button>
                    </div>
                    <template x-if="reports.length === 0">
                        <p>報告書はありません</p>
                    </template>
                    <template x-if="reports.length > 0">
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ユーザー</th>
                                        <th>ガイド</th>
                                        <th>実施日</th>
                                        <th>ステータス</th>
                                        <th>個別報告書ダウンロード</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="report in reports" :key="report.id">
                                        <tr>
                                            <td x-text="report.id"></td>
                                            <td x-text="report.user?.name || '—'"></td>
                                            <td x-text="report.guide?.name || '—'"></td>
                                            <td x-text="report.actual_date || '-'"></td>
                                            <td>
                                                <span class="status-badge" :class="{
                                                    'status-approved': report.status === 'approved' || report.status === 'admin_approved',
                                                    'status-pending': report.status === 'submitted' || report.status === 'user_approved',
                                                    'status-draft': report.status === 'draft'
                                                }" x-text="
                                                    report.status === 'admin_approved'
                                                        ? '管理者承認済み'
                                                        : report.status === 'user_approved'
                                                            ? 'ユーザー承認済み／管理者承認待ち'
                                                            : report.status === 'approved'
                                                                ? '承認済み'
                                                                : report.status === 'submitted'
                                                                    ? '承認待ち'
                                                                    : '下書き'
                                                "></span>
                                            </td>
                                            <td>
                                                <button
                                                    @click="exportReportCSV(report.id)"
                                                    class="btn-icon-small"
                                                    :aria-label="'報告書ID ' + report.id + ' をCSV出力'"
                                                    title="CSV出力"
                                                >
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7 10 12 15 17 10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </section>

                <section class="admin-section user-approved-reports-section">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            管理者承認待ち報告書
                        </h2>
                        <template x-if="userApprovedReports.length > 0">
                            <span class="pending-count" x-text="userApprovedReports.length + '件'"></span>
                        </template>
                    </div>
                    <p class="section-description">
                        ユーザーが承認済みの報告書が表示されます。内容を確認し、問題なければ管理者承認してください。
                    </p>
                    <template x-if="userApprovedReports.length === 0">
                        <div class="empty-state-small">
                            <p>管理者承認待ちの報告書はありません</p>
                        </div>
                    </template>
                    <template x-if="userApprovedReports.length > 0">
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ユーザー</th>
                                        <th>ガイド</th>
                                        <th>実施日</th>
                                        <th>ステータス</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="report in userApprovedReports" :key="report.id">
                                        <tr>
                                            <td x-text="report.id"></td>
                                            <td x-text="report.user?.name || '—'"></td>
                                            <td x-text="report.guide?.name || '—'"></td>
                                            <td x-text="report.actual_date || '-'"></td>
                                            <td>
                                                <span class="status-badge status-pending">
                                                    ユーザー承認済み／管理者承認待ち
                                                </span>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <button
                                                        class="btn-icon-small"
                                                        @click="exportReportCSV(report.id)"
                                                        :aria-label="'報告書ID ' + report.id + ' をCSV出力'"
                                                        title="CSV出力"
                                                        style="margin-right: 4px;"
                                                    >
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                            <polyline points="7 10 12 15 17 10"></polyline>
                                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        class="btn-secondary btn-sm"
                                                        style="margin-right: 8px;"
                                                        @click="openReportModal(report)"
                                                    >
                                                        詳細を見る
                                                    </button>
                                                    <button
                                                        class="btn-primary btn-sm"
                                                        @click="approveReport(report.id)"
                                                    >
                                                        管理者承認
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </section>
            </div>
        </template>

        <!-- ユーザー管理タブ -->
        <template x-if="activeTab === 'users'">
            <section class="admin-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        ユーザー管理
                    </h2>
                </div>
                <template x-if="users.length === 0">
                    <p>ユーザーは登録されていません</p>
                </template>
                <template x-if="users.length > 0">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>名前</th>
                                    <th>メールアドレス</th>
                                    <th>電話番号</th>
                                    <th>受給者証番号</th>
                                    <th>運営コメント</th>
                                    <th>登録日</th>
                                    <th>承認状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(user, index) in users" :key="user.id">
                                    <tr>
                                        <td x-text="index + 1"></td>
                                        <td x-text="user.name"></td>
                                        <td x-text="user.email"></td>
                                        <td x-text="user.phone || '-'"></td>
                                        <td>
                                            <div class="table-inline-field">
                                                <input
                                                    type="text"
                                                    :value="userMeta[user.id] || ''"
                                                    @input="userMeta[user.id] = $event.target.value"
                                                    placeholder="受給者証番号"
                                                />
                                                <button
                                                    class="btn-secondary btn-sm"
                                                    @click="saveUserMeta(user.id)"
                                                >
                                                    保存
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-inline-field">
                                                <input
                                                    type="text"
                                                    :value="userAdminComment[user.id] || ''"
                                                    @input="userAdminComment[user.id] = $event.target.value"
                                                    placeholder="運営コメント"
                                                />
                                                <button
                                                    class="btn-secondary btn-sm"
                                                    @click="saveUserMeta(user.id)"
                                                >
                                                    保存
                                                </button>
                                            </div>
                                        </td>
                                        <td x-text="formatDate(user.created_at)"></td>
                                        <td>
                                            <span class="status-badge" :class="user.is_allowed ? 'status-approved' : 'status-pending'" x-text="user.is_allowed ? '承認済み' : '未承認'"></span>
                                        </td>
                                        <td>
                                            <template x-if="!user.is_allowed">
                                                <button
                                                    @click="approveUser(user.id)"
                                                    class="btn-primary btn-sm"
                                                >
                                                    承認
                                                </button>
                                            </template>
                                            <template x-if="user.is_allowed">
                                                <button
                                                    @click="rejectUser(user.id)"
                                                    class="btn-secondary btn-sm"
                                                >
                                                    拒否
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </section>
        </template>

        <!-- ガイド管理タブ -->
        <template x-if="activeTab === 'guides'">
            <section class="admin-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        ガイド管理
                    </h2>
                </div>
                <template x-if="guides.length === 0">
                    <p>ガイドは登録されていません</p>
                </template>
                <template x-if="guides.length > 0">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>名前</th>
                                    <th>メールアドレス</th>
                                    <th>電話番号</th>
                                    <th>従業員番号</th>
                                    <th>登録日</th>
                                    <th>承認状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(guide, index) in guides" :key="guide.id">
                                    <tr>
                                        <td x-text="index + 1"></td>
                                        <td x-text="guide.name"></td>
                                        <td x-text="guide.email"></td>
                                        <td x-text="guide.phone || '-'"></td>
                                        <td>
                                            <div class="table-inline-field">
                                                <input
                                                    type="text"
                                                    :value="guideMeta[guide.id] || ''"
                                                    @input="guideMeta[guide.id] = $event.target.value"
                                                    placeholder="従業員番号"
                                                />
                                                <button
                                                    class="btn-secondary btn-sm"
                                                    @click="saveGuideMeta(guide.id)"
                                                >
                                                    保存
                                                </button>
                                            </div>
                                        </td>
                                        <td x-text="formatDate(guide.created_at)"></td>
                                        <td>
                                            <span class="status-badge" :class="guide.is_allowed ? 'status-approved' : 'status-pending'" x-text="guide.is_allowed ? '承認済み' : '未承認'"></span>
                                        </td>
                                        <td>
                                            <template x-if="!guide.is_allowed">
                                                <button
                                                    @click="approveGuide(guide.id)"
                                                    class="btn-primary btn-sm"
                                                >
                                                    承認
                                                </button>
                                            </template>
                                            <template x-if="guide.is_allowed">
                                                <button
                                                    @click="rejectGuide(guide.id)"
                                                    class="btn-secondary btn-sm"
                                                >
                                                    拒否
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </section>
        </template>

        <!-- お知らせ管理タブ -->
        <template x-if="activeTab === 'announcements'">
            <div x-data="announcementManagement()" x-init="init()">
                @include('admin.announcement-management')
            </div>
        </template>

        <!-- 限度時間管理タブ -->
        <template x-if="activeTab === 'monthly-limits'">
            <section class="admin-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        利用者の月次限度時間管理
                    </h2>
                </div>
                <template x-if="users.length === 0">
                    <p>ユーザーは登録されていません</p>
                </template>
                <template x-if="users.length > 0">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ユーザー名</th>
                                    <th>年月</th>
                                    <th>限度時間（時間）</th>
                                    <th>使用時間（時間）</th>
                                    <th>残時間（時間）</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="user in users" :key="user.id">
                                    <tr>
                                        <td x-text="user.name"></td>
                                        <td>
                                            <div class="table-inline-field">
                                                <input
                                                    type="number"
                                                    :id="'year-' + user.id"
                                                    min="2000"
                                                    max="2100"
                                                    :value="new Date().getFullYear()"
                                                    style="width: 80px;"
                                                />
                                                <span>年</span>
                                                <input
                                                    type="number"
                                                    :id="'month-' + user.id"
                                                    min="1"
                                                    max="12"
                                                    :value="new Date().getMonth() + 1"
                                                    style="width: 60px;"
                                                />
                                                <span>月</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                step="0.1"
                                                min="0"
                                                :id="'limit-' + user.id"
                                                placeholder="限度時間"
                                                style="width: 100px;"
                                            />
                                        </td>
                                        <td>
                                            <span x-text="getUserUsedHours(user.id) || '0.0'"></span>
                                        </td>
                                        <td>
                                            <span x-text="getUserRemainingHours(user.id) || '0.0'"></span>
                                        </td>
                                        <td>
                                            <button
                                                class="btn-primary btn-sm"
                                                @click="setUserMonthlyLimit(user.id)"
                                            >
                                                設定
                                            </button>
                                            <button
                                                class="btn-secondary btn-sm"
                                                @click="loadUserMonthlyLimits(user.id)"
                                            >
                                                履歴
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </section>
        </template>

        <!-- メールテンプレート編集タブ -->
        <template x-if="activeTab === 'email-templates'">
            <section class="admin-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        メールテンプレート編集
                    </h2>
                </div>
                <template x-if="emailTemplates.length === 0">
                    <p>テンプレートを読み込み中...</p>
                </template>
                <template x-if="emailTemplates.length > 0">
                    <div class="email-templates-list">
                        <template x-for="template in emailTemplates" :key="template.id">
                            <div class="email-template-card">
                                <div class="template-card-header">
                                    <div class="template-title-section">
                                        <h3 x-text="getTemplateKeyLabel(template.template_key) || template.template_key"></h3>
                                    </div>
                                    <div class="template-toggle-section">
                                        <label class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                :checked="template.is_active"
                                                @change="updateTemplateActive(template.id, $event.target.checked)"
                                            />
                                            <span class="toggle-slider"></span>
                                            <span class="toggle-label" x-text="template.is_active ? '有効' : '無効'"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="template-form">
                                    <div class="form-group">
                                        <label class="form-label">件名</label>
                                        <input
                                            type="text"
                                            :value="template.editingSubject || template.subject || ''"
                                            @input="template.editingSubject = $event.target.value"
                                            class="form-control"
                                            placeholder="件名を入力"
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">本文</label>
                                        <textarea
                                            :value="template.editingBody || template.body || ''"
                                            @input="template.editingBody = $event.target.value"
                                            class="form-control"
                                            rows="10"
                                            placeholder="本文を入力"
                                        ></textarea>
                                        <small class="form-text">本文内で依頼情報やマッチング情報などを挿入する場合は、該当する項目名を波括弧で囲んでください</small>
                                    </div>
                                    <div class="template-actions">
                                        <button
                                            class="btn-primary"
                                            @click="updateEmailTemplate(template.id, template.editingSubject || template.subject, template.editingBody || template.body, template.is_active)"
                                        >
                                            保存
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </section>
        </template>

        <!-- メール通知設定タブ -->
        <template x-if="activeTab === 'email-settings'">
            <section class="admin-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        メール通知設定
                    </h2>
                </div>
                <template x-if="emailSettings.length === 0">
                    <p>設定を読み込み中...</p>
                </template>
                <template x-if="emailSettings.length > 0">
                    <div class="email-settings-list">
                        <template x-for="setting in emailSettings" :key="setting.id">
                            <div class="email-setting-card">
                                <div class="setting-card-header">
                                    <div class="setting-title-section">
                                        <h3 x-text="getNotificationTypeLabel(setting.notification_type)"></h3>
                                        <p class="setting-description" x-text="getNotificationDescription(setting.notification_type)"></p>
                                    </div>
                                    <div class="setting-toggle-section">
                                        <label class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                :checked="setting.is_enabled"
                                                @change="updateEmailSetting(setting.id, 'is_enabled', $event.target.checked)"
                                            />
                                            <span class="toggle-slider"></span>
                                            <span class="toggle-label" x-text="setting.is_enabled ? '有効' : '無効'"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="setting-card-body">
                                    <template x-if="setting.notification_type === 'reminder'">
                                        <div class="form-group">
                                            <label class="form-label">リマインド日数</label>
                                            <div class="reminder-input-group">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    :value="setting.reminder_days || 3"
                                                    @change="updateEmailSetting(setting.id, 'reminder_days', $event.target.value)"
                                                    class="form-control"
                                                    style="width: 100px;"
                                                />
                                                <span class="input-suffix">日</span>
                                            </div>
                                            <small class="form-text">承認待ちの依頼がある場合、指定した日数経過後にリマインドメールを送信します</small>
                                        </div>
                                    </template>
                                    <div class="setting-actions">
                                        <button
                                            class="btn-primary"
                                            @click="saveEmailSetting(setting.id)"
                                        >
                                            保存
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </section>
        </template>

        <!-- 管理操作ログタブ -->
        <template x-if="activeTab === 'operation-logs'">
            <section class="admin-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        管理操作ログ
                    </h2>
                </div>
                <div class="section-actions">
                    <select @change="filterOperationLogs($event.target.value)" class="form-control" style="width: 200px; display: inline-block;">
                        <option value="">全ての操作</option>
                        <option value="user_approve">ユーザー承認</option>
                        <option value="user_reject">ユーザー拒否</option>
                        <option value="guide_approve">ガイド承認</option>
                        <option value="guide_reject">ガイド拒否</option>
                        <option value="matching_approve">マッチング承認</option>
                        <option value="matching_reject">マッチング却下</option>
                    </select>
                </div>
                <template x-if="operationLogs.length === 0">
                    <p>操作ログはありません</p>
                </template>
                <template x-if="operationLogs.length > 0">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>日時</th>
                                    <th>操作者</th>
                                    <th>操作種別</th>
                                    <th>対象種別</th>
                                    <th>対象ID</th>
                                    <th>IPアドレス</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="log in operationLogs" :key="log.id">
                                    <tr>
                                        <td x-text="formatDateTime(log.created_at)"></td>
                                        <td x-text="log.admin?.name || '—'"></td>
                                        <td x-text="getOperationTypeLabel(log.operation_type)"></td>
                                        <td x-text="getTargetTypeLabel(log.target_type)"></td>
                                        <td x-text="log.target_id || '—'"></td>
                                        <td x-text="log.ip_address || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </section>
        </template>
    </div>
    <!-- 報告書詳細モーダル -->
    <div
        class="modal-backdrop"
        x-show="showReportModal"
        x-cloak
        style="position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 1100;"
        @click.self="closeReportModal()"
        role="dialog"
        aria-modal="true"
        aria-label="報告書の詳細"
    >
        <div
            class="modal-content"
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 8px; max-width: 720px; width: 90%; max-height: 80vh; overflow: auto; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);"
        >
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 1.1rem; font-weight: 600;">報告書の内容確認</h2>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button
                        type="button"
                        class="btn-secondary btn-sm"
                        @click="exportReportCSV(selectedReport.id)"
                        x-show="selectedReport"
                    >
                        CSV出力
                    </button>
                    <button
                        type="button"
                        class="btn-secondary btn-sm"
                        @click="closeReportModal()"
                    >
                        閉じる
                    </button>
                </div>
            </div>

            <template x-if="selectedReport">
                <div class="modal-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; font-size: 0.9rem;">
                    <div>
                        <strong>報告書ID</strong><br>
                        <span x-text="selectedReport.id"></span>
                    </div>
                    <div>
                        <strong>実施日</strong><br>
                        <span x-text="selectedReport.actual_date || '-'"></span>
                    </div>
                    <div>
                        <strong>ユーザー</strong><br>
                        <span x-text="selectedReport.user?.name || '—'"></span>
                    </div>
                    <div>
                        <strong>ガイド</strong><br>
                        <span x-text="selectedReport.guide?.name || '—'"></span>
                    </div>
                    <div>
                        <strong>開始時刻</strong><br>
                        <span x-text="selectedReport.actual_start_time || '-'"></span>
                    </div>
                    <div>
                        <strong>終了時刻</strong><br>
                        <span x-text="selectedReport.actual_end_time || '-'"></span>
                    </div>

                    <div style="grid-column: 1 / -1; margin-top: 12px;">
                        <strong>サービス内容</strong>
                        <div style="border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px; margin-top: 4px; white-space: pre-wrap; background: #fafafa; max-height: 160px; overflow: auto;">
                            <span x-text="selectedReport.service_content || '未入力'"></span>
                        </div>
                    </div>

                    <div style="grid-column: 1 / -1; margin-top: 12px;">
                        <strong>報告欄（自由記入）</strong>
                        <div style="border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px; margin-top: 4px; white-space: pre-wrap; background: #fafafa; max-height: 200px; overflow: auto;">
                            <span x-text="selectedReport.report_content || '未入力'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/AnnouncementManagement.css') }}">
@endpush

@push('scripts')
<script>
function adminDashboard() {
    return {
        activeTab: 'dashboard',
        requests: [],
        acceptances: [],
        reports: [],
        userApprovedReports: [], // ユーザー承認済み（管理者承認待ち）報告書
        selectedReport: null,    // 詳細表示用
        showReportModal: false,
        users: [],
        guides: [],
        stats: null,
        autoMatching: false,
        loading: true,
        fetchingUsers: false, // ユーザー取得中のフラグ（重複リクエスト防止）
        fetchingLimits: false, // 限度時間取得中のフラグ（重複リクエスト防止）
        userMeta: {},
        guideMeta: {},
        userAdminComment: {},
        emailTemplates: [],
        emailSettings: [],
        operationLogs: [],
        userMonthlyLimits: {},
        userCurrentLimits: {}, // 現在の月の限度時間情報を保持

        // セッション認証用の共通fetch関数
        async apiFetch(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                credentials: 'include', // セッションCookieを送信
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {})
                }
            });
            
            // 419エラー（CSRFトークン期限切れ）: ページをリロードして新しいトークンを取得
            if (response.status === 419) {
                console.warn('セッション期限切れ（419）。ページを再読み込みします。');
                alert('セッションの期限が切れました。ページを再読み込みします。');
                window.location.reload();
                return;
            }
            
            // 401エラー（認証エラー）: ログイン画面へリダイレクト
            if (response.status === 401) {
                console.error('認証エラー:', url);
                alert('認証が期限切れです。ログイン画面に移動します。');
                window.location.href = '/login?message=expired';
                throw new Error('認証エラー');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'エラーが発生しました' }));
                console.error('APIエラー:', url, errorData);
                throw new Error(errorData.error || 'エラーが発生しました');
            }
            
            return response.json();
        },

        async init() {
            await this.fetchDashboardData();
        },

        async fetchDashboardData() {
            try {
                // fetchWithErrorHandlingはapiFetchを使用するように変更
                const fetchWithErrorHandling = this.apiFetch.bind(this);

                const [
                    requestsRes,
                    acceptancesRes,
                    reportsRes,
                    userApprovedReportsRes,
                    settingsRes,
                    statsRes,
                    userStatsRes
                ] = await Promise.all([
                    fetchWithErrorHandling('/api/admin/requests').catch((error) => {
                        console.error('❌ API失敗: /api/admin/requests', error);
                        return { requests: [] };
                    }),
                    fetchWithErrorHandling('/api/admin/acceptances').catch((error) => {
                        console.error('❌ API失敗: /api/admin/acceptances', error);
                        return { acceptances: [] };
                    }),
                    fetchWithErrorHandling('/api/admin/reports').catch((error) => {
                        console.error('❌ API失敗: /api/admin/reports', error);
                        return { reports: [] };
                    }),
                    fetchWithErrorHandling('/api/admin/reports/user-approved').catch((error) => {
                        console.error('❌ API失敗: /api/admin/reports/user-approved', error);
                        return { reports: [] };
                    }),
                    fetchWithErrorHandling('/api/admin/settings/auto-matching').catch((error) => {
                        console.error('❌ API失敗: /api/admin/settings/auto-matching', error);
                        return { auto_matching: false };
                    }),
                    fetchWithErrorHandling('/api/admin/stats').catch((error) => {
                        console.error('❌ API失敗: /api/admin/stats', error);
                        return {};
                    }),
                    fetchWithErrorHandling('/api/users/stats').catch((error) => {
                        console.error('❌ API失敗: /api/users/stats', error);
                        return { users: {}, guides: {} };
                    })
                ]);
                
                // 成功したAPIリクエストを確認
                console.log('✅ API結果確認:');
                console.log('  - requests:', requestsRes.requests ? `✅ 成功 (${requestsRes.requests.length}件)` : '❌ 失敗');
                console.log('  - acceptances:', acceptancesRes.acceptances ? `✅ 成功 (${acceptancesRes.acceptances.length}件)` : '❌ 失敗');
                console.log('  - reports (submitted):', reportsRes.reports ? `✅ 成功 (${reportsRes.reports.length}件)` : '❌ 失敗');
                console.log('  - reports (user_approved):', userApprovedReportsRes.reports ? `✅ 成功 (${userApprovedReportsRes.reports.length}件)` : '❌ 失敗');
                console.log('  - settings:', settingsRes.auto_matching !== undefined ? '✅ 成功' : '❌ 失敗');
                console.log('  - stats:', Object.keys(statsRes).length > 0 ? '✅ 成功' : '❌ 失敗');
                console.log('  - userStats:', userStatsRes.users ? '✅ 成功' : '❌ 失敗');
                
                console.log("acceptancesRes", acceptancesRes);
                console.log("statsRes", statsRes);

                this.requests = requestsRes.requests || [];
                this.acceptances = acceptancesRes.acceptances || [];
                console.log("acceptancesRes", this.acceptances);
                this.reports = reportsRes.reports || [];
                this.userApprovedReports = userApprovedReportsRes.reports || [];
                this.autoMatching = settingsRes.auto_matching || false;
                
                // 統計情報をマージ
                this.stats = {
                    ...statsRes,
                    users: userStatsRes.users || {},
                    guides: userStatsRes.guides || {}
                };
                console.log("this.stats", this.stats);
            } catch (error) {
                console.error('ダッシュボードデータ取得エラー:', error);
                if (error.message !== '認証エラー') {
                    alert('データの取得に失敗しました。ページをリロードしてください。');
                }
            } finally {
                this.loading = false;
            }
        },

        async fetchUsers() {
            // 既に取得中の場合は重複リクエストを防ぐ
            if (this.fetchingUsers) {
                return;
            }
            
            this.fetchingUsers = true;
            try {
                const data = await this.apiFetch('/api/admin/users');
                this.users = data.users || [];
                
                // メタデータを初期化
                this.users.forEach(u => {
                    this.userMeta[u.id] = u.recipient_number || '';
                    this.userAdminComment[u.id] = u.admin_comment || '';
                });
                
                // 限度時間タブ用に、現在月の限度時間/使用時間/残時間も取得（順次処理でレート制限を回避）
                if (this.activeTab === 'monthly-limits') {
                    await this.fetchAllUserCurrentLimits();
                }
            } catch (error) {
                console.error('ユーザー一覧取得エラー:', error);
                if (error.message && error.message.includes('Too Many')) {
                    alert('リクエストが多すぎます。しばらく待ってから再度お試しください。');
                } else {
                    alert('ユーザー一覧の取得に失敗しました');
                }
            } finally {
                this.fetchingUsers = false;
            }
        },

        async fetchGuides() {
            try {
                const data = await this.apiFetch('/api/admin/guides');
                this.guides = data.guides || [];
                
                // メタデータを初期化
                this.guides.forEach(g => {
                    this.guideMeta[g.id] = g.employee_number || '';
                });
            } catch (error) {
                console.error('ガイド一覧取得エラー:', error);
                alert('ガイド一覧の取得に失敗しました');
            }
        },

        async toggleAutoMatching() {
            try {
                await this.apiFetch('/api/admin/settings/auto-matching', {
                    method: 'PUT',
                    body: JSON.stringify({ auto_matching: this.autoMatching })
                });
                alert('自動マッチング設定を更新しました');
            } catch (error) {
                alert('設定の更新に失敗しました');
                this.autoMatching = !this.autoMatching;
            }
        },

        async approveMatching(requestId, guideId, userSelected) {
            if (userSelected === false || userSelected === "false") {
                alert('ユーザーが選択されていません');
                return;
            }

            if (!confirm('このマッチングを承認しますか？')) {
                return;
            }

            try {
                await this.apiFetch('/api/admin/matchings/approve', {
                    method: 'POST',
                    body: JSON.stringify({
                        request_id: requestId,
                        guide_id: guideId
                    })
                });
                alert('マッチングを承認しました');
                await this.fetchDashboardData();
            } catch (error) {
                alert('マッチング承認に失敗しました');
                console.error(error);
            }
        },

        async rejectMatching(requestId, guideId) {
            if (!confirm('このマッチングを却下しますか？')) {
                return;
            }

            try {
                await this.apiFetch('/api/admin/matchings/reject', {
                    method: 'POST',
                    body: JSON.stringify({
                        request_id: requestId,
                        guide_id: guideId
                    })
                });
                alert('マッチングを却下しました');
                await this.fetchDashboardData();
            } catch (error) {
                alert('マッチング却下に失敗しました');
                console.error(error);
            }
        },

        exportCSV(type) {
            const url = type === 'reports' 
                ? '/api/admin/reports/csv'
                : '/api/admin/usage/csv';
            window.open(url, '_blank');
        },
        exportReportCSV(reportId) {
            const url = `/api/admin/reports/${reportId}/csv`;
            window.open(url, '_blank');
        },

        async approveReport(reportId) {
            if (!confirm('この報告書を管理者承認しますか？')) return;
            
            try {
                const data = await this.apiFetch(`/api/admin/reports/${reportId}/approve`, {
                    method: 'POST'
                });
                
                alert(data.message || '報告書を管理者承認しました');
                await this.fetchDashboardData();
            } catch (error) {
                console.error('報告書管理者承認エラー:', error);
                alert('報告書の管理者承認に失敗しました: ' + error.message);
            }
        },

        openReportModal(report) {
            this.selectedReport = report;
            this.showReportModal = true;
        },

        closeReportModal() {
            this.showReportModal = false;
            this.selectedReport = null;
        },

        async approveUser(userId) {
            if (!confirm('このユーザーを承認しますか？')) return;
            try {
                await this.apiFetch(`/api/admin/users/${userId}/approve`, {
                    method: 'PUT'
                });
                alert('ユーザーを承認しました');
                await this.fetchUsers();
            } catch (error) {
                console.error('ユーザー承認エラー:', error);
                alert('ユーザー承認に失敗しました: ' + error.message);
            }
        },

        async rejectUser(userId) {
            if (!confirm('このユーザーを拒否しますか？')) return;
            try {
                await this.apiFetch(`/api/admin/users/${userId}/reject`, {
                    method: 'PUT'
                });
                alert('ユーザーを拒否しました');
                await this.fetchUsers();
            } catch (error) {
                alert('ユーザー拒否に失敗しました');
                console.error(error);
            }
        },

        async approveGuide(guideId) {
            if (!confirm('このガイドを承認しますか？')) return;
            try {
                await this.apiFetch(`/api/admin/guides/${guideId}/approve`, {
                    method: 'PUT'
                });
                alert('ガイドを承認しました');
                await this.fetchGuides();
            } catch (error) {
                console.error('ガイド承認エラー:', error);
                alert('ガイド承認に失敗しました: ' + error.message);
            }
        },

        async rejectGuide(guideId) {
            if (!confirm('このガイドを拒否しますか？')) return;
            try {
                await this.apiFetch(`/api/admin/guides/${guideId}/reject`, {
                    method: 'PUT'
                });
                alert('ガイドを拒否しました');
                await this.fetchGuides();
            } catch (error) {
                console.error('ガイド拒否エラー:', error);
                alert('ガイド拒否に失敗しました: ' + error.message);
            }
        },

        async saveUserMeta(userId) {
            try {
                await this.apiFetch(`/api/admin/users/${userId}/profile-extra`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        recipient_number: this.userMeta[userId] || null,
                        admin_comment: this.userAdminComment[userId] || null
                    })
                });
                alert('受給者証番号/コメントを更新しました');
                await this.fetchUsers();
            } catch (error) {
                alert('受給者証番号/コメントの更新に失敗しました');
                console.error(error);
            }
        },

        async saveGuideMeta(guideId) {
            try {
                await this.apiFetch(`/api/admin/guides/${guideId}/profile-extra`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        employee_number: this.guideMeta[guideId] || null
                    })
                });
                alert('従業員番号を更新しました');
                await this.fetchGuides();
            } catch (error) {
                alert('従業員番号の更新に失敗しました');
                console.error(error);
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('ja-JP');
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleString('ja-JP');
        },

        async fetchEmailTemplates() {
            try {
                const data = await this.apiFetch('/api/admin/email-templates');
                this.emailTemplates = (data.templates || []).map(t => ({
                    ...t,
                    editingSubject: t.subject || '',
                    editingBody: t.body || ''
                }));
            } catch (error) {
                console.error('メールテンプレート取得エラー:', error);
                alert('メールテンプレートの取得に失敗しました: ' + error.message);
            }
        },

        async fetchEmailSettings() {
            try {
                const data = await this.apiFetch('/api/admin/email-settings');
                this.emailSettings = data.settings || [];
            } catch (error) {
                console.error('メール通知設定取得エラー:', error);
                alert('メール通知設定の取得に失敗しました');
            }
        },

        async fetchOperationLogs(operationType = '') {
            try {
                const url = `/api/admin/operation-logs${operationType ? '?operation_type=' + operationType : ''}`;
                const data = await this.apiFetch(url);
                this.operationLogs = data.logs || [];
            } catch (error) {
                console.error('操作ログ取得エラー:', error);
                alert('操作ログの取得に失敗しました');
            }
        },

        async updateEmailTemplate(templateId, subject, body, isActive) {
            try {
                await this.apiFetch(`/api/admin/email-templates/${templateId}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        subject: subject,
                        body: body,
                        is_active: isActive
                    })
                });
                alert('テンプレートを更新しました');
                await this.fetchEmailTemplates();
            } catch (error) {
                alert('テンプレートの更新に失敗しました');
                console.error(error);
            }
        },

        async updateTemplateActive(templateId, isActive) {
            const template = this.emailTemplates.find(t => t.id === templateId);
            if (template) {
                template.is_active = isActive;
            }
        },

        async updateEmailSetting(settingId, field, value) {
            const setting = this.emailSettings.find(s => s.id === settingId);
            if (setting) {
                setting[field] = value;
            }
        },

        async saveEmailSetting(settingId) {
            const setting = this.emailSettings.find(s => s.id === settingId);
            if (!setting) return;

            try {
                await this.apiFetch(`/api/admin/email-settings/${settingId}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        is_enabled: setting.is_enabled,
                        reminder_days: setting.reminder_days || null
                    })
                });
                alert('通知設定を更新しました');
                await this.fetchEmailSettings();
            } catch (error) {
                alert('通知設定の更新に失敗しました');
                console.error(error);
            }
        },

        async setUserMonthlyLimit(userId) {
            const year = parseInt(document.getElementById(`year-${userId}`).value);
            const month = parseInt(document.getElementById(`month-${userId}`).value);
            const limitHours = parseFloat(document.getElementById(`limit-${userId}`).value);

            if (!limitHours || limitHours < 0) {
                alert('限度時間を正しく入力してください');
                return;
            }

            if (!confirm(`${year}年${month}月の限度時間を${limitHours}時間に設定しますか？`)) {
                return;
            }

            try {
                
                await this.apiFetch(`/api/admin/users/${userId}/monthly-limit`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        limit_hours: limitHours,
                        year: year,
                        month: month
                    })
                });
                alert('限度時間を設定しました');
                await this.fetchUserCurrentLimit(userId);
                await this.loadUserMonthlyLimits(userId);
            } catch (error) {
                alert('限度時間の設定に失敗しました');
                console.error(error);
            }
        },

        async loadUserMonthlyLimits(userId) {
            try {
                
                const data = await this.apiFetch(`/api/admin/users/${userId}/monthly-limits`);
                this.userMonthlyLimits[userId] = data.limits || [];
                
                let message = `ユーザーID ${userId} の限度時間履歴:\n\n`;
                if (data.limits && data.limits.length > 0) {
                    data.limits.forEach(limit => {
                        const usedHours = limit.used_hours || 0;
                        const remainingHours = limit.remaining_hours || (limit.limit_hours - usedHours);
                        message += `${limit.year}年${limit.month}月: 限度${limit.limit_hours}時間 / 使用${usedHours.toFixed(1)}時間 / 残り${remainingHours.toFixed(1)}時間\n`;
                    });
                } else {
                    message += '履歴がありません';
                }
                alert(message);
            } catch (error) {
                console.error('限度時間履歴取得エラー:', error);
                alert('限度時間履歴の取得に失敗しました');
            }
        },

        getUserUsedHours(userId) {
            const currentLimit = this.userCurrentLimits[userId];
            if (!currentLimit) return '0.0';
            return (currentLimit.used_hours || 0).toFixed(1);
        },

        getUserRemainingHours(userId) {
            const currentLimit = this.userCurrentLimits[userId];
            if (!currentLimit) return '0.0';
            const remaining = (currentLimit.limit_hours || 0) - (currentLimit.used_hours || 0);
            return remaining.toFixed(1);
        },

        async fetchUserCurrentLimit(userId) {
            try {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth() + 1;
                
                const data = await this.apiFetch(`/api/admin/users/${userId}/monthly-limits?year=${year}&month=${month}`);
                
                if (data.limits && data.limits.length > 0) {
                    const limit = data.limits[0];
                    this.userCurrentLimits[userId] = {
                        limit_hours: limit.limit_hours || 0,
                        used_hours: limit.used_hours || 0,
                        remaining_hours: limit.remaining_hours || (limit.limit_hours - (limit.used_hours || 0))
                    };
                } else {
                    this.userCurrentLimits[userId] = { limit_hours: 0, used_hours: 0, remaining_hours: 0 };
                }
            } catch (error) {
                console.error(`ユーザー${userId}の限度時間取得エラー:`, error);
                this.userCurrentLimits[userId] = {
                    limit_hours: 0,
                    used_hours: 0,
                    remaining_hours: 0
                };
            }
        },

        // すべてのユーザーの現在月の限度時間を順次取得（レート制限を回避）
        async fetchAllUserCurrentLimits() {
            if (!this.users || this.users.length === 0) return;
            
            // 既に取得中の場合は重複リクエストを防ぐ
            if (this.fetchingLimits) {
                return;
            }
            
            this.fetchingLimits = true;
            
            try {
                // バッチサイズ（一度に処理するユーザー数）- レート制限を考慮して小さく設定
                const batchSize = 3;
                
                for (let i = 0; i < this.users.length; i += batchSize) {
                    const batch = this.users.slice(i, i + batchSize);
                    
                    // バッチ内のリクエストを並行処理
                    await Promise.allSettled(
                        batch.map(user => this.fetchUserCurrentLimit(user.id))
                    );
                    
                    // 次のバッチの前に待機（レート制限を回避）- 待機時間を増やす
                    if (i + batchSize < this.users.length) {
                        await new Promise(resolve => setTimeout(resolve, 200));
                    }
                }
            } finally {
                this.fetchingLimits = false;
            }
        },

        filterOperationLogs(operationType) {
            this.fetchOperationLogs(operationType);
        },

        getOperationTypeLabel(type) {
            const labels = {
                'user_approve': 'ユーザー承認',
                'user_reject': 'ユーザー拒否',
                'guide_approve': 'ガイド承認',
                'guide_reject': 'ガイド拒否',
                'matching_approve': 'マッチング承認',
                'matching_reject': 'マッチング却下',
                'report_approve': '報告書承認',
                'report_revision_request': '報告書修正依頼'
            };
            return labels[type] || type;
        },

        getTargetTypeLabel(type) {
            const labels = {
                'user': 'ユーザー',
                'guide': 'ガイド',
                'matching': 'マッチング',
                'report': '報告書'
            };
            return labels[type] || type;
        },

        getNotificationTypeLabel(type) {
            const labels = {
                'request': '依頼通知',
                'matching': 'マッチング通知',
                'report': '報告書通知',
                'reminder': 'リマインド通知'
            };
            return labels[type] || type;
        },

        getTemplateKeyLabel(key) {
            if (!key) return '';
            const labels = {
                'request_notification': '依頼通知',
                'matching_notification': 'マッチング成立通知',
                'report_submitted': '報告書提出通知',
                'report_approved': '報告書承認通知',
                'reminder_pending_request': '承認待ち依頼リマインド'
            };
            return labels[key] || key;
        },

        getNotificationDescription(type) {
            const descriptions = {
                'request': '新しい依頼が登録された際に送信される通知',
                'matching': 'マッチングが成立した際に送信される通知',
                'report': '報告書が提出・承認された際に送信される通知',
                'reminder': '承認待ちの依頼がある場合に送信されるリマインド'
            };
            return descriptions[type] || '';
        }
    }
}

function announcementManagement() {
    return {
        announcements: [],
        loading: true,
        showForm: false,
        editingId: null,
        formData: {
            title: '',
            content: '',
            target_audience: 'all'
        },

        async init() {
            await this.fetchAnnouncements();
        },

        async fetchAnnouncements() {
            try {
                const data = await this.apiFetch('/api/announcements/admin/all');
                this.announcements = data.announcements || [];
            } catch (error) {
                console.error('お知らせ取得エラー:', error);
                alert('お知らせの取得に失敗しました');
            } finally {
                this.loading = false;
            }
        },

        async handleSubmit() {
            if (!this.formData.title.trim() || !this.formData.content.trim()) {
                alert('タイトルと本文は必須です');
                return;
            }

            try {
                const url = this.editingId 
                    ? `/api/announcements/admin/${this.editingId}`
                    : '/api/announcements/admin';
                const method = this.editingId ? 'PUT' : 'POST';

                await this.apiFetch(url, {
                    method: method,
                    body: JSON.stringify(this.formData)
                });
                
                alert(this.editingId ? 'お知らせを更新しました' : 'お知らせを作成しました');
                this.showForm = false;
                this.editingId = null;
                this.formData = { title: '', content: '', target_audience: 'all' };
                await this.fetchAnnouncements();
            } catch (error) {
                alert('お知らせの保存に失敗しました: ' + error.message);
                console.error(error);
            }
        },

        handleEdit(announcement) {
            this.formData = {
                title: announcement.title,
                content: announcement.content,
                target_audience: announcement.target_audience
            };
            this.editingId = announcement.id;
            this.showForm = true;
        },

        async handleDelete(id) {
            if (!confirm('このお知らせを削除しますか？')) {
                return;
            }

            try {
                await this.apiFetch(`/api/announcements/admin/${id}`, {
                    method: 'DELETE'
                });
                alert('お知らせを削除しました');
                await this.fetchAnnouncements();
            } catch (error) {
                alert('お知らせの削除に失敗しました: ' + error.message);
                console.error(error);
            }
        },

        handleCancel() {
            this.showForm = false;
            this.editingId = null;
            this.formData = { title: '', content: '', target_audience: 'all' };
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ja-JP', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getTargetLabel(target) {
            const labels = {
                'user': 'ユーザー向け',
                'guide': 'ガイド向け',
                'all': '全体向け'
            };
            return labels[target] || target;
        },
        formatRequestDateTime(dateStr, timeStr) {
            if (!dateStr) return '';
            
            // 日付を年/月/日にフォーマット
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;
            const day = date.getDate();
            
            // 時間をフォーマット（秒を除く）
            let timeDisplay = '';
            if (timeStr) {
                // "HH:MM:SS" または "HH:MM" 形式から "HH:MM" を抽出
                const timeMatch = timeStr.match(/^(\d{1,2}):(\d{2})/);
                if (timeMatch) {
                    const hours = parseInt(timeMatch[1], 10);
                    const minutes = timeMatch[2];
                    timeDisplay = `${String(hours).padStart(2, '0')}:${minutes}`;
                }
            }
            
            return `${year}/${month}/${day}${timeDisplay ? ' ' + timeDisplay : ''}`;
        }
    }
}
</script>
@endpush
