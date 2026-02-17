@extends('layouts.app')

@section('content')
<div class="admin-dashboard-wrapper" x-data="adminDashboard()" x-init="init(); window.adminDashboard = $data;">

    <!-- メインコンテンツエリア -->
    <div class="admin-dashboard-main">
        <!-- パンくずリスト -->
        <nav class="admin-breadcrumb" aria-label="パンくずリスト">
            <ol class="admin-breadcrumb-list">
                <li class="admin-breadcrumb-item">
                    <a href="{{ route('home') }}" class="admin-breadcrumb-link">ホーム</a>
                </li>
                <li class="admin-breadcrumb-separator" aria-hidden="true">/</li>
                <li class="admin-breadcrumb-item" aria-current="page">ダッシュボード</li>
            </ol>
        </nav>

        <div class="admin-dashboard-header">
            <h1>管理画面</h1>
            <p class="admin-welcome-message">システム全体の管理と設定を行います</p>
        </div>

        <!-- タブコンテンツラッパー -->
        <div class="admin-tab-content">
        <!-- ダッシュボードタブ -->
        <template x-if="activeTab === 'dashboard'">
            <div>
                <!-- 通知セクション -->
                <template x-if="notifications.length > 0">
                    <section class="admin-section notifications-section" aria-label="通知">
                        <div class="section-header">
                            <h2>
                                <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                通知
                            </h2>
                            <span class="notification-count" x-text="notifications.length + '件'"></span>
                        </div>
                        <ul class="notification-list">
                            <template x-for="notif in notifications" :key="notif.id">
                                <li class="notification-item">
                                    <div class="notification-content">
                                        <strong x-text="notif.title"></strong>
                                        <p x-text="notif.message"></p>
                                    </div>
                                    <small x-text="formatDate(notif.created_at)"></small>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>

                <!-- 統計情報セクション -->
                <template x-if="stats">
                    <section class="admin-stats-section">
                        <div class="admin-stats-grid">
                            <!-- ユーザー・ガイド関連 -->
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">登録ユーザー数</h3>
                                <div class="admin-stat-value" x-text="stats.users?.total || 0"></div>
                                <div class="admin-stat-subtitle" x-show="stats.users?.pending > 0">
                                    <span class="admin-stat-badge pending">承認待ち: <span x-text="stats.users?.pending || 0"></span></span>
                                </div>
                            </div>
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">登録ガイド数</h3>
                                <div class="admin-stat-value" x-text="stats.guides?.total || 0"></div>
                                <div class="admin-stat-subtitle" x-show="stats.guides?.pending > 0">
                                    <span class="admin-stat-badge pending">承認待ち: <span x-text="stats.guides?.pending || 0"></span></span>
                                </div>
                            </div>
                            <!-- 依頼関連 -->
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">総依頼数</h3>
                                <div class="admin-stat-value" x-text="stats.requests?.total || 0"></div>
                                <div class="admin-stat-subtitle">
                                    <span class="admin-stat-badge">稼働中: <span x-text="stats.requests?.in_progress || 0"></span></span>
                                </div>
                            </div>
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">応募待ち依頼</h3>
                                <div class="admin-stat-value" x-text="stats.requests?.pending || 0"></div>
                                <div class="admin-stat-subtitle" x-show="stats.requests?.guide_accepted > 0">
                                    <span class="admin-stat-badge">承諾済み: <span x-text="stats.requests?.guide_accepted || 0"></span></span>
                                </div>
                            </div>
                            <!-- マッチング関連 -->
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">進行中マッチング</h3>
                                <div class="admin-stat-value" x-text="stats.matchings?.in_progress || 0"></div>
                                <div class="admin-stat-subtitle" x-show="stats.matchings?.matched > 0">
                                    <span class="admin-stat-badge">成立済み: <span x-text="stats.matchings?.matched || 0"></span></span>
                                </div>
                            </div>
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">完了マッチング</h3>
                                <div class="admin-stat-value" x-text="stats.matchings?.completed || 0"></div>
                                <div class="admin-stat-subtitle">
                                    <span class="admin-stat-badge">総数: <span x-text="stats.matchings?.total || 0"></span></span>
                                </div>
                            </div>
                            <!-- 報告書関連 -->
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">承認待ち報告書</h3>
                                <div class="admin-stat-value" x-text="userApprovedReports?.length || 0"></div>
                                <div class="admin-stat-subtitle">
                                    <span class="admin-stat-badge alert">管理者承認待ち</span>
                                </div>
                            </div>
                            <div class="admin-stat-card">
                                <h3 class="admin-stat-title">提出済み報告書</h3>
                                <div class="admin-stat-value" x-text="reports?.length || 0"></div>
                                <div class="admin-stat-subtitle">
                                    <span class="admin-stat-badge">総報告書数</span>
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
                            <span class="section-title-hint" title="ユーザーが選択したガイドのみ承認できます">ⓘ</span>
                        </h2>
                        <template x-if="getFilteredAcceptances().length > 0">
                            <span class="pending-count" x-text="getFilteredAcceptances().length + '件'"></span>
                        </template>
                    </div>
                    <div class="filter-controls">
                        <label class="filter-toggle">
                            <input
                                type="checkbox"
                                x-model="hideOldAcceptances"
                                @change="saveAcceptanceFilterSetting()"
                            />
                            <span>長期間未承認を非表示</span>
                        </label>
                        <template x-if="hideOldAcceptances">
                            <select
                                x-model="hideOldAcceptancesDays"
                                @change="saveAcceptanceFilterSetting()"
                                class="filter-days-select"
                            >
                                <option value="30">30日以上</option>
                                <option value="60">60日以上</option>
                                <option value="90">90日以上</option>
                                <option value="180">180日以上</option>
                            </select>
                        </template>
                    </div>
                    <div class="section-actions">
                        <button
                            x-show="selectedAcceptances.length > 0"
                            @click="batchApproveMatchings()"
                            class="btn-primary"
                            :disabled="selectedAcceptances.length === 0"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            選択した<span x-text="selectedAcceptances.length"></span>件を一括承認
                        </button>
                    </div>
                    <template x-if="getFilteredAcceptances().length === 0">
                        <div class="empty-state-small">
                            <p>承諾待ちの依頼はありません</p>
                        </div>
                    </template>
                    <template x-if="getFilteredAcceptances().length > 0">
                        <div class="table-container acceptances-table-container">
                        <table class="admin-table acceptances-table">
                            <thead>
                                    <tr>
                                        <th class="checkbox-cell">
                                            <input
                                                type="checkbox"
                                                class="acceptance-checkbox acceptance-checkbox-select-all"
                                                @change="toggleSelectAllAcceptances($event.target.checked)"
                                                :checked="getSelectableAcceptancesCount() > 0 && selectedAcceptances.length === getSelectableAcceptancesCount()"
                                                x-ref="selectAllCheckbox"
                                                x-effect="if ($refs.selectAllCheckbox) { $refs.selectAllCheckbox.indeterminate = selectedAcceptances.length > 0 && selectedAcceptances.length < getSelectableAcceptancesCount(); }"
                                                aria-label="すべて選択"
                                            />
                                        </th>
                                        <th>依頼ID</th>
                                        <th>ユーザー</th>
                                        <th>ガイド</th>
                                        <th>日時</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="acc in getFilteredAcceptances()" :key="acc.id">
                                        <tr>
                                            <td class="checkbox-cell">
                                                <input
                                                    type="checkbox"
                                                    class="acceptance-checkbox"
                                                    :value="JSON.stringify({request_id: acc.request_id, guide_id: acc.guide_id, user_selected: acc.user_selected})"
                                                    @change="toggleAcceptanceSelection(acc.request_id, acc.guide_id, $event.target.checked)"
                                                    :checked="isAcceptanceSelected(acc.request_id, acc.guide_id)"
                                                    :disabled="!acc.user_selected"
                                                    :aria-label="acc.user_selected ? `依頼ID ${acc.request_id}番を選択` : `依頼ID ${acc.request_id}番（ユーザーがまだガイドを選択していないため選択できません）`"
                                                    :title="acc.user_selected ? '' : 'ユーザーがまだガイドを選択していないため、承認できません'"
                                                />
                                            </td>
                                            <td>
                                                <span class="request-id" x-text="acc.request_id"></span>
                                            </td>
                                            <td>
                                                <span class="user-name" x-text="acc.user_name || '—'"></span>
                                            </td>
                                            <td>
                                                <span class="user-name" x-text="acc.guide_name || '—'"></span>
                                            </td>
                                            <td>
                                                <div class="datetime-cell-vertical">
                                                    <span class="datetime-date" x-text="formatDateOnly(acc.request_date)"></span>
                                                    <span class="datetime-time" x-text="formatTimeOnly(acc.request_time)" x-show="acc.request_time"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button
                                                        @click="approveMatching(acc.request_id, acc.guide_id, acc.user_selected)"
                                                        class="btn-approve"
                                                        :aria-label="`依頼ID ${acc.request_id}番を承認する`"
                                                    >
                                                        承認
                                                    </button>
                                                    <button
                                                        @click="rejectMatching(acc.request_id, acc.guide_id)"
                                                        class="btn-reject"
                                                        :aria-label="`依頼ID ${acc.request_id}番を却下する`"
                                                    >
                                                        却下
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
                        <div class="month-filter-controls">
                            <label class="month-filter-label">
                                <span>表示月:</span>
                                <select
                                    x-model="selectedReportMonth"
                                    @change="filterReportsByMonth()"
                                    class="month-filter-select"
                                >
                                    <option value="">すべての月</option>
                                    <template x-for="monthKey in getAvailableMonths()" :key="monthKey">
                                        <option :value="monthKey" x-text="monthKey"></option>
                                    </template>
                                </select>
                            </label>
                        </div>
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
                        <div class="reports-by-month">
                            <template x-for="(monthReports, monthKey) in getFilteredReportsByMonth()" :key="monthKey">
                                <div class="month-section">
                                    <div class="month-header">
                                        <h3 class="month-title" x-text="monthKey"></h3>
                                        <span class="month-count" x-text="monthReports.length + '件'"></span>
                                    </div>
                                    <div class="table-container acceptances-table-container">
                                        <table class="admin-table acceptances-table">
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
                                                <template x-for="report in monthReports" :key="report.id">
                                                    <tr>
                                                        <td>
                                                            <span class="request-id" x-text="report.id"></span>
                                                        </td>
                                                        <td>
                                                            <span class="user-name" x-text="report.user?.name || '—'"></span>
                                                        </td>
                                                        <td>
                                                            <span class="user-name" x-text="report.guide?.name || '—'"></span>
                                                        </td>
                                                        <td>
                                                            <div class="datetime-cell-vertical">
                                                                <span class="datetime-date" x-text="formatReportDate(report.actual_date) || '-'"></span>
                                                            </div>
                                                        </td>
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
                                                                class="download-link"
                                                                :aria-label="'報告書ID ' + report.id + ' をCSV出力'"
                                                            >
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                                </svg>
                                                                <span>CSV</span>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
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
                    <div class="section-actions">
                        <button
                            x-show="userApprovedReports.length > 0"
                            @click="approveAllReports()"
                            class="btn-primary"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            全件承諾
                        </button>
                        <button
                            x-show="selectedReports.length > 0"
                            @click="batchApproveReports()"
                            class="btn-primary"
                            :disabled="selectedReports.length === 0"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            選択した<span x-text="selectedReports.length"></span>件を承諾
                        </button>
                        <button
                            x-show="selectedReports.length > 0"
                            @click="batchReturnReports()"
                            class="btn-reject"
                            :disabled="selectedReports.length === 0"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                <path d="M3 12l6-6m-6 6l6 6m-6-6h18"></path>
                            </svg>
                            選択した<span x-text="selectedReports.length"></span>件を差し戻し
                        </button>
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
                        <div class="table-container acceptances-table-container">
                            <table class="admin-table acceptances-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-cell">
                                            <input
                                                type="checkbox"
                                                class="acceptance-checkbox acceptance-checkbox-select-all"
                                                @change="toggleSelectAllReports($event.target.checked)"
                                                :checked="userApprovedReports.length > 0 && selectedReports.length === userApprovedReports.length"
                                                x-ref="selectAllReportsCheckbox"
                                                x-effect="if ($refs.selectAllReportsCheckbox) { $refs.selectAllReportsCheckbox.indeterminate = selectedReports.length > 0 && selectedReports.length < userApprovedReports.length; }"
                                                aria-label="すべて選択"
                                            />
                                        </th>
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
                                            <td class="checkbox-cell">
                                                <input
                                                    type="checkbox"
                                                    class="acceptance-checkbox"
                                                    :value="report.id"
                                                    @change="toggleReportSelection(report.id, $event.target.checked)"
                                                    :checked="isReportSelected(report.id)"
                                                    :aria-label="`報告書ID ${report.id}番を選択`"
                                                />
                                            </td>
                                            <td>
                                                <span class="request-id" x-text="report.id"></span>
                                            </td>
                                            <td>
                                                <span class="user-name" x-text="report.user?.name || '—'"></span>
                                            </td>
                                            <td>
                                                <span class="user-name" x-text="report.guide?.name || '—'"></span>
                                            </td>
                                            <td>
                                                <div class="datetime-cell-vertical">
                                                    <span class="datetime-date" x-text="formatReportDate(report.actual_date) || '-'"></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-pending">
                                                    ユーザー承認済み／管理者承認待ち
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button
                                                        class="btn-secondary"
                                                        @click="openReportModal(report)"
                                                    >
                                                        詳細を見る
                                                    </button>
                                                    <button
                                                        class="btn-approve"
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
                <div class="users-toolbar">
                    <div class="users-search-sort">
                        <label for="user-search" class="sr-only">名前またはメールで検索</label>
                        <input
                            type="search"
                            id="user-search"
                            class="users-search-input"
                            placeholder="名前・メールで検索"
                            x-model="userSearchQuery"
                            @keydown.enter.prevent="fetchUsers()"
                            aria-label="名前またはメールアドレスで検索"
                        />
                        <button type="button" class="btn-primary btn-search" @click="fetchUsers()">検索</button>
                        <label for="user-sort" class="sr-only">並び順</label>
                        <select
                            id="user-sort"
                            class="users-sort-select"
                            x-model="userSortOrder"
                            @change="fetchUsers()"
                            aria-label="並び順を選択"
                        >
                            <option value="pending_first">未承認優先</option>
                            <option value="created_desc">登録が新しい順</option>
                            <option value="created_asc">登録が古い順</option>
                            <option value="name_asc">名前（あいうえお順）</option>
                            <option value="name_desc">名前（逆順）</option>
                        </select>
                    </div>
                    <p x-show="userSearchQuery.trim() !== ''" class="users-result-count" x-text="'検索結果: ' + users.length + '件'"></p>
                </div>
                <template x-if="users.length === 0">
                    <p x-text="fetchingUsers ? '読み込み中...' : (userSearchQuery.trim() !== '' ? '検索条件に一致するユーザーはいません' : 'ユーザーは登録されていません')"></p>
                </template>
                <template x-if="users.length > 0">
                    <div class="table-container">
                        <table class="admin-table admin-table-scrollable-large users-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>名前</th>
                                    <th>メールアドレス</th>
                                    <th>電話番号</th>
                                    <th>受給者証番号</th>
                                    <th>登録日</th>
                                    <th>承認状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(user, index) in users" :key="user.id">
                                    <tr>
                                        <td>
                                            <span class="user-id-number" x-text="index + 1"></span>
                                        </td>
                                        <td>
                                            <span class="user-name-bold" x-text="user.name"></span>
                                        </td>
                                        <td>
                                            <span class="user-email" x-text="user.email"></span>
                                        </td>
                                        <td>
                                            <span class="user-phone" :class="{ 'empty-data': !user.phone || user.phone === '-' }" x-text="user.phone || '-'"></span>
                                        </td>
                                        <td>
                                            <div class="table-inline-field">
                                                <input
                                                    type="text"
                                                    class="recipient-number-input"
                                                    :value="userMeta[user.id] || ''"
                                                    @input="userMeta[user.id] = $event.target.value.replace(/\D/g, '').slice(0, 10)"
                                                    maxlength="10"
                                                    pattern="\d{10}"
                                                    placeholder="受給者証番号（10桁）"
                                                    aria-label="受給者証番号（半角数字10桁）"
                                                    :id="'recipient-number-' + user.id"
                                                />
                                                <button
                                                    class="btn-save-meta"
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
                                            <div class="user-action-buttons">
                                                <button
                                                    @click="openUserProfileModal(user.id)"
                                                    class="btn-detail-user"
                                                    aria-label="ユーザー詳細を表示"
                                                >
                                                    詳細
                                                </button>
                                                <template x-if="!user.is_allowed">
                                                    <button
                                                        @click="approveUser(user.id)"
                                                        class="btn-approve-user"
                                                        aria-label="ユーザーを承認"
                                                    >
                                                        承認
                                                    </button>
                                                </template>
                                                <template x-if="user.is_allowed">
                                                    <button
                                                        @click="rejectUser(user.id)"
                                                        class="btn-reject-user"
                                                        aria-label="ユーザーを拒否"
                                                    >
                                                        拒否
                                                    </button>
                                                </template>
                                            </div>
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
                <div class="users-toolbar guides-toolbar">
                    <div class="users-search-sort">
                        <label for="guide-search" class="sr-only">名前またはメールで検索</label>
                        <input
                            type="search"
                            id="guide-search"
                            class="users-search-input"
                            placeholder="名前・メールで検索"
                            x-model="guideSearchQuery"
                            @keydown.enter.prevent="fetchGuides()"
                            aria-label="名前またはメールアドレスで検索"
                        />
                        <button type="button" class="btn-primary btn-search" @click="fetchGuides()">検索</button>
                        <label for="guide-sort" class="sr-only">並び順</label>
                        <select
                            id="guide-sort"
                            class="users-sort-select"
                            x-model="guideSortOrder"
                            @change="fetchGuides()"
                            aria-label="並び順を選択"
                        >
                            <option value="pending_first">未承認優先</option>
                            <option value="created_desc">登録が新しい順</option>
                            <option value="created_asc">登録が古い順</option>
                            <option value="name_asc">名前（あいうえお順）</option>
                            <option value="name_desc">名前（逆順）</option>
                        </select>
                    </div>
                    <p x-show="guideSearchQuery.trim() !== ''" class="users-result-count" x-text="'検索結果: ' + guides.length + '件'"></p>
                </div>
                <template x-if="guides.length === 0">
                    <p x-text="fetchingGuides ? '読み込み中...' : (guideSearchQuery.trim() !== '' ? '検索条件に一致するガイドはいません' : 'ガイドは登録されていません')"></p>
                </template>
                <template x-if="guides.length > 0">
                    <div class="table-container">
                        <table class="admin-table admin-table-scrollable-large guides-table">
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
                                        <td>
                                            <span class="user-id-number" x-text="index + 1"></span>
                                        </td>
                                        <td>
                                            <span class="user-name-bold" x-text="guide.name"></span>
                                        </td>
                                        <td>
                                            <span class="user-email" x-text="guide.email"></span>
                                        </td>
                                        <td>
                                            <span class="user-phone" :class="{ 'empty-data': !guide.phone || guide.phone === '-' }" x-text="guide.phone || '-'"></span>
                                        </td>
                                        <td>
                                            <div class="table-inline-field">
                                                <input
                                                    type="text"
                                                    class="recipient-number-input"
                                                    :value="guideMeta[guide.id] || ''"
                                                    @input="formatEmployeeNumber(guide.id, $event.target.value)"
                                                    maxlength="7"
                                                    pattern="\d{3}-\d{3}"
                                                    placeholder="000-000"
                                                    aria-label="従業員番号（000-000形式）"
                                                    :id="'employee-number-' + guide.id"
                                                />
                                                <button
                                                    class="btn-save-meta"
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
                                            <div class="user-action-buttons">
                                                <button
                                                    @click="openGuideProfileModal(guide.id)"
                                                    class="btn-detail-user"
                                                    aria-label="ガイド詳細を表示"
                                                >
                                                    詳細
                                                </button>
                                                <template x-if="!guide.is_allowed">
                                                    <button
                                                        @click="approveGuide(guide.id)"
                                                        class="btn-approve-user"
                                                        aria-label="ガイドを承認"
                                                    >
                                                        承認
                                                    </button>
                                                </template>
                                                <template x-if="guide.is_allowed">
                                                    <button
                                                        @click="rejectGuide(guide.id)"
                                                        class="btn-reject-user"
                                                        aria-label="ガイドを拒否"
                                                    >
                                                        拒否
                                                    </button>
                                                </template>
                                            </div>
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
                    <template x-if="users.length > 0">
                        <a
                            :href="getMonthlyLimitsSummaryCsvUrl()"
                            download
                            class="btn-primary"
                            style="margin-left: auto;"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: middle;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            残時間一覧をCSVダウンロード
                        </a>
                    </template>
                </div>
                <p class="section-description" style="margin-bottom: 1rem; color: var(--text-muted, #666); font-size: 0.9rem;">
                    照会時は下表で各利用者の残時間をご確認いただくか、上記ボタンでCSVをダウンロードしてご利用ください。
                </p>
                <template x-if="users.length === 0">
                    <p>ユーザーは登録されていません</p>
                </template>
                <template x-if="users.length > 0">
                    <div class="table-container">
                        <table class="admin-table monthly-limits-table">
                            <thead>
                                <tr>
                                    <th>ユーザー名</th>
                                    <th>受給者証番号</th>
                                    <th>年月</th>
                                    <th colspan="3" class="limit-type-th">外出</th>
                                    <th colspan="3" class="limit-type-th">自宅</th>
                                    <th>操作</th>
                                </tr>
                                <tr class="sub-header">
                                    <th></th>
                                    <th></th>
                                    <th class="monthly-limits-th-year-month"></th>
                                    <th class="monthly-limits-th-limit monthly-limits-th-outing">限度（h）</th>
                                    <th class="monthly-limits-th-used monthly-limits-th-outing">使用（h）</th>
                                    <th class="monthly-limits-th-remaining monthly-limits-th-outing">残（h）</th>
                                    <th class="monthly-limits-th-limit">限度（h）</th>
                                    <th class="monthly-limits-th-used">使用（h）</th>
                                    <th class="monthly-limits-th-remaining">残（h）</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="user in users" :key="user.id">
                                    <tr>
                                        <td>
                                            <span class="user-name-bold" x-text="user.name"></span>
                                        </td>
                                        <td>
                                            <span class="recipient-number-value" :class="{ 'empty-data': !userMeta[user.id] }" x-text="userMeta[user.id] || '—'"></span>
                                        </td>
                                        <td class="monthly-limits-cell-year-month">
                                            <div class="monthly-limits-year-month">
                                                <input
                                                    type="number"
                                                    class="year-month-input"
                                                    :id="'year-' + user.id"
                                                    min="2000"
                                                    max="2100"
                                                    :value="new Date().getFullYear()"
                                                />
                                                <span class="year-month-sep">年</span>
                                                <input
                                                    type="number"
                                                    class="year-month-input"
                                                    :id="'month-' + user.id"
                                                    min="1"
                                                    max="12"
                                                    :value="new Date().getMonth() + 1"
                                                />
                                                <span class="year-month-sep">月</span>
                                            </div>
                                        </td>
                                        <td class="monthly-limits-cell-limit monthly-limits-cell-outing">
                                            <input
                                                type="number"
                                                class="limit-hours-input"
                                                step="0.1"
                                                min="0"
                                                :id="'limit-outing-' + user.id"
                                                :value="getUserOutingLimitHours(user.id)"
                                                placeholder="0"
                                            />
                                        </td>
                                        <td class="monthly-limits-cell-used monthly-limits-cell-outing"><span class="hours-value" x-text="getUserOutingUsedHours(user.id)"></span></td>
                                        <td class="monthly-limits-cell-remaining monthly-limits-cell-outing"><span class="hours-value" x-text="getUserOutingRemainingHours(user.id)"></span></td>
                                        <td class="monthly-limits-cell-limit">
                                            <input
                                                type="number"
                                                class="limit-hours-input"
                                                step="0.1"
                                                min="0"
                                                :id="'limit-home-' + user.id"
                                                :value="getUserHomeLimitHours(user.id)"
                                                placeholder="0"
                                            />
                                        </td>
                                        <td class="monthly-limits-cell-used"><span class="hours-value" x-text="getUserHomeUsedHours(user.id)"></span></td>
                                        <td class="monthly-limits-cell-remaining"><span class="hours-value" x-text="getUserHomeRemainingHours(user.id)"></span></td>
                                        <td>
                                            <div class="user-action-buttons">
                                                <button
                                                    class="btn-set-limit"
                                                    @click="setUserMonthlyLimit(user.id)"
                                                >
                                                    設定
                                                </button>
                                                <button
                                                    class="btn-detail-user"
                                                    @click="loadUserMonthlyLimits(user.id)"
                                                >
                                                    履歴
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
                    <button
                        class="btn-primary"
                        @click="showNewTemplateForm = !showNewTemplateForm"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        新規作成
                    </button>
                </div>
                <!-- 新規テンプレート作成フォーム -->
                <template x-if="showNewTemplateForm">
                    <div class="email-template-card" style="margin-bottom: var(--spacing-xl); border: 2px dashed var(--border-color);">
                        <div class="template-card-header">
                            <div class="template-title-section">
                                <h3>新規テンプレート作成</h3>
                                <template x-if="newTemplate.template_key">
                                    <div class="template-meta-info">
                                        <span class="template-recipient-badge" :class="getRecipientClassForNew(newTemplate)">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                <polyline points="22,6 12,13 2,6"></polyline>
                                            </svg>
                                            <span x-text="getRecipientLabelForNew(newTemplate)"></span>
                                        </span>
                                        <span class="template-trigger-info" x-text="getTriggerDescription(newTemplate.template_key) || '送信タイミングを説明文に記載してください'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="template-form">
                            <div class="form-group">
                                <label class="form-label">テンプレートキー <span style="color: var(--error-color);">*</span></label>
                                <input
                                    type="text"
                                    x-model="newTemplate.template_key"
                                    class="form-control"
                                    placeholder="例: request_notification, matching_notification"
                                    @input="updateRecipientFromKey()"
                                />
                                <small class="form-text">英数字とアンダースコアのみ使用可能です。既存のテンプレートキーを入力すると送信先が自動設定されます。</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">送信先 <span style="color: var(--error-color);">*</span></label>
                                <select
                                    x-model="newTemplate.recipient"
                                    class="form-control"
                                >
                                    <option value="">選択してください</option>
                                    <option value="user">ユーザー</option>
                                    <option value="guide">ガイド</option>
                                    <option value="both">ユーザー・ガイド</option>
                                </select>
                                <small class="form-text">このメールテンプレートの送信先を選択してください。既存のテンプレートキーを入力すると自動設定されます。</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">件名 <span style="color: var(--error-color);">*</span></label>
                                <input
                                    type="text"
                                    x-model="newTemplate.subject"
                                    class="form-control"
                                    placeholder="件名を入力"
                                />
                            </div>
                            <div class="form-group">
                                <label class="form-label">本文 <span style="color: var(--error-color);">*</span></label>
                                <textarea
                                    x-model="newTemplate.body"
                                    class="form-control"
                                    rows="10"
                                    placeholder="本文を入力"
                                ></textarea>
                                <small class="form-text">本文内で依頼情報やマッチング情報などを挿入する場合は、該当する項目名を波括弧で囲んでください</small>
                            </div>
                            <div class="template-actions">
                                <button
                                    class="btn-secondary"
                                    @click="showNewTemplateForm = false; newTemplate = { template_key: '', subject: '', body: '', is_active: true, recipient: '' }"
                                >
                                    キャンセル
                                </button>
                                <button
                                    class="btn-primary"
                                    @click="createEmailTemplate()"
                                    :disabled="!newTemplate.template_key || !newTemplate.subject || !newTemplate.body"
                                >
                                    作成
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
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
                                        <div class="template-meta-info">
                                            <span class="template-recipient-badge" :class="getRecipientClass(template.template_key)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                    <polyline points="22,6 12,13 2,6"></polyline>
                                                </svg>
                                                <span x-text="getRecipientLabel(template.template_key)"></span>
                                            </span>
                                            <span class="template-trigger-info" x-text="getTriggerDescription(template.template_key)"></span>
                                        </div>
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
                                            class="btn-danger"
                                            @click="deleteEmailTemplate(template.id)"
                                            style="margin-right: auto;"
                                        >
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            削除
                                        </button>
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

        <!-- 設定タブ（通知のオン/オフ・リマインド日数など） -->
        <template x-if="activeTab === 'email-settings'">
            <section class="admin-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        設定
                    </h2>
                </div>
                <p class="setting-section-note">各通知のオン/オフは、依頼・マッチング・報告書・リマインドなどのメール送信時に反映されます。</p>
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
                        <table class="admin-table operation-logs-table">
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
                                        <td>
                                            <div class="datetime-cell-vertical">
                                                <span class="datetime-date" x-text="formatDateOnly(log.created_at)"></span>
                                                <span class="datetime-time" x-text="formatTimeOnly(log.created_at)"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="user-name-bold" x-text="log.admin?.name || '—'"></span>
                                        </td>
                                        <td>
                                            <span class="operation-type" x-text="getOperationTypeLabel(log.operation_type)"></span>
                                        </td>
                                        <td>
                                            <span class="target-type" x-text="getTargetTypeLabel(log.target_type)"></span>
                                        </td>
                                        <td>
                                            <span class="request-id" x-text="log.target_id || '—'"></span>
                                        </td>
                                        <td>
                                            <span class="ip-address" :class="{ 'empty-data': !log.ip_address || log.ip_address === '—' }" x-text="log.ip_address || '—'"></span>
                                        </td>
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
        @click.self="closeReportModal()"
        role="dialog"
        aria-modal="true"
        aria-label="報告書の詳細"
    >
        <div class="modal-content modal-content-sm">
            <div class="modal-header">
                <h2>報告書の内容確認</h2>
                <div>
                    <button
                        type="button"
                        class="btn-secondary btn-sm"
                        @click="exportReportCSV(selectedReport.id)"
                        x-show="selectedReport && selectedReport.status === 'approved'"
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
                <div class="modal-body report-modal-body">
                    <div class="modal-grid">
                        <div class="modal-field">
                            <strong>報告書ID</strong>
                            <span x-text="selectedReport.id"></span>
                        </div>
                        <div class="modal-field">
                            <strong>実施日</strong>
                            <span x-text="formatReportDate(selectedReport.actual_date) || '-'"></span>
                        </div>
                        <div class="modal-field">
                            <strong>ユーザー</strong>
                            <span x-text="selectedReport.user?.name || '—'"></span>
                        </div>
                        <div class="modal-field">
                            <strong>ガイド</strong>
                            <span x-text="selectedReport.guide?.name || '—'"></span>
                        </div>
                        <div class="modal-field">
                            <strong>開始時刻</strong>
                            <span x-text="selectedReport.actual_start_time ? formatTimeOnly(selectedReport.actual_start_time) : '-'"></span>
                        </div>
                        <div class="modal-field">
                            <strong>終了時刻</strong>
                            <span x-text="selectedReport.actual_end_time ? formatTimeOnly(selectedReport.actual_end_time) : '-'"></span>
                        </div>

                        <div class="modal-field modal-grid-full modal-field-spaced">
                            <strong>サービス内容</strong>
                            <div class="modal-display-box modal-display-box-sm">
                                <span x-text="selectedReport.service_content || '未入力'"></span>
                            </div>
                        </div>

                        <div class="modal-field modal-grid-full modal-field-spaced">
                            <strong>報告欄（自由記入）</strong>
                            <div class="modal-display-box modal-display-box-scroll">
                                <span x-text="selectedReport.report_content || '未入力'"></span>
                            </div>
                        </div>
                </div>
            </template>
        </div>
    </div>
    <!-- ユーザープロフィール詳細モーダル -->
    <div
        class="modal-backdrop"
        x-show="showUserProfileModal"
        x-cloak
        @click.self="closeUserProfileModal()"
        @keydown.escape.window="closeUserProfileModal()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-profile-modal-title"
    >
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="user-profile-modal-title">ユーザープロフィール</h2>
                <div>
                    <button
                        type="button"
                        class="btn-secondary btn-sm"
                        @click="closeUserProfileModal()"
                        aria-label="モーダルを閉じる"
                    >
                        閉じる
                    </button>
                </div>
            </div>

            <template x-if="selectedUserProfile">
                <div>
                    <div class="modal-body">
                        <div class="modal-section">
                        <h3>基本情報</h3>
                        <div class="modal-grid">
                                <div class="modal-field">
                                    <strong>氏名</strong>
                                    <template x-if="!editingUserProfile">
                                        <span x-text="selectedUserProfile.name || '—'"></span>
                                    </template>
                                    <template x-if="editingUserProfile">
                                        <input
                                            type="text"
                                            class="modal-input"
                                            x-model="editingUserProfileData.name"
                                            aria-label="氏名"
                                            aria-required="true"
                                        />
                                    </template>
                                </div>
                                <div>
                                    <strong>メールアドレス</strong><br>
                                    <span x-text="selectedUserProfile.email || '—'"></span>
                                </div>
                                <div class="modal-field" x-show="selectedUserProfile.phone || editingUserProfile">
                                    <strong>電話番号</strong>
                                    <template x-if="!editingUserProfile">
                                        <span x-text="selectedUserProfile.phone"></span>
                                    </template>
                                    <template x-if="editingUserProfile">
                                        <input
                                            type="tel"
                                            class="modal-input"
                                            x-model="editingUserProfileData.phone"
                                            aria-label="電話番号"
                                        />
                                    </template>
                                </div>
                                <div class="modal-field" x-show="selectedUserProfile.address || editingUserProfile">
                                    <strong>住所</strong>
                                    <template x-if="!editingUserProfile">
                                        <span x-text="selectedUserProfile.address"></span>
                                    </template>
                                    <template x-if="editingUserProfile">
                                        <textarea
                                            class="modal-textarea"
                                            rows="2"
                                            placeholder="都道府県、市区町村、番地を入力してください"
                                            x-model="editingUserProfileData.address"
                                            aria-label="住所"
                                            aria-describedby="address-help-user"
                                        ></textarea>
                                        <small id="address-help-user" class="modal-help-text">都道府県、市区町村、番地を入力してください</small>
                                    </template>
                                </div>
                                <div class="modal-field" x-show="selectedUserProfile.birth_date">
                                    <strong>生年月日</strong>
                                    <span x-text="formatDate(selectedUserProfile.birth_date)"></span>
                                </div>
                                <div class="modal-field" x-show="selectedUserProfile.age && selectedUserProfile.age > 0">
                                    <strong>年齢</strong>
                                    <span x-text="selectedUserProfile.age + '歳'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- プロフィール情報 -->
                        <div class="modal-section">
                            <h3>プロフィール情報</h3>
                            <div class="modal-grid">
                                <div class="modal-field" x-show="selectedUserProfile.contact_method || editingUserProfile">
                                    <strong>連絡手段</strong>
                                    <template x-if="!editingUserProfile">
                                        <span x-text="selectedUserProfile.contact_method"></span>
                                    </template>
                                    <template x-if="editingUserProfile">
                                        <input
                                            type="text"
                                            class="modal-input"
                                            x-model="editingUserProfileData.contact_method"
                                            aria-label="連絡手段"
                                        />
                                    </template>
                                </div>
                                <div class="modal-field" x-show="selectedUserProfile.recipient_number || editingUserProfile">
                                    <strong>受給者証番号</strong>
                                    <template x-if="!editingUserProfile">
                                        <span x-text="selectedUserProfile.recipient_number"></span>
                                    </template>
                                    <template x-if="editingUserProfile">
                                        <input
                                            type="text"
                                            class="modal-input"
                                            x-model="editingUserProfileData.recipient_number"
                                            @input="editingUserProfileData.recipient_number = $event.target.value.replace(/\D/g, '').slice(0, 10)"
                                            maxlength="10"
                                            pattern="\d{10}"
                                            placeholder="半角数字10桁"
                                            aria-label="受給者証番号（半角数字10桁）"
                                        />
                                    </template>
                                </div>
                                <div class="modal-field modal-grid-full" x-show="selectedUserProfile.notes || editingUserProfile">
                                    <strong>備考</strong>
                                    <template x-if="!editingUserProfile">
                                        <div class="modal-display-box" style="min-height: auto;">
                                            <span x-text="selectedUserProfile.notes"></span>
                                        </div>
                                    </template>
                                    <template x-if="editingUserProfile">
                                        <textarea
                                            class="modal-textarea"
                                            rows="4"
                                            placeholder="備考を入力してください（任意）"
                                            x-model="editingUserProfileData.notes"
                                            aria-label="備考（任意）"
                                            id="notes-user"
                                        ></textarea>
                                    </template>
                                </div>
                                <div class="modal-field modal-grid-full" x-show="selectedUserProfile.introduction || editingUserProfile">
                                    <strong>自己紹介</strong>
                                    <template x-if="!editingUserProfile">
                                        <div class="modal-display-box" style="min-height: auto;">
                                            <span x-text="selectedUserProfile.introduction"></span>
                                        </div>
                                    </template>
                                    <template x-if="editingUserProfile">
                                        <textarea
                                            class="modal-textarea"
                                            rows="4"
                                            placeholder="自己紹介を入力してください（任意）"
                                            x-model="editingUserProfileData.introduction"
                                            aria-label="自己紹介（任意）"
                                            id="introduction-user"
                                        ></textarea>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </template>
            
            <!-- 編集ボタン（閲覧モード時） - templateの外に配置 -->
            <div 
                class="modal-edit-button" 
                x-show="selectedUserProfile && !editingUserProfile"
                x-init="console.log('編集ボタン要素初期化（ユーザー） - editingUserProfile:', editingUserProfile, 'selectedUserProfile:', selectedUserProfile)"
            >
                <button
                    type="button"
                    class="btn-primary"
                    @click="console.log('編集ボタンクリック - editingUserProfile:', editingUserProfile, 'selectedUserProfile:', selectedUserProfile); editUserProfile(selectedUserProfile.id)"
                    aria-label="プロフィールを編集"
                >
                    編集
                </button>
            </div>
            <!-- 編集モード時の保存・キャンセルボタン -->
            <div class="modal-button-group" x-show="selectedUserProfile && editingUserProfile">
                <button
                    type="button"
                    class="btn-secondary btn-sm modal-cancel-btn"
                    @click="cancelEditUserProfile()"
                    aria-label="編集をキャンセル"
                >
                    キャンセル
                </button>
                <button
                    type="button"
                    class="btn-primary btn-sm"
                    @click="saveUserProfile(selectedUserProfile.id)"
                    :aria-busy="savingUserProfile"
                    aria-label="プロフィールを保存する"
                >
                    <span x-show="!savingUserProfile">保存する</span>
                    <span x-show="savingUserProfile">保存中...</span>
                </button>
            </div>
        </div>
    </div>
    <!-- ガイドプロフィール詳細モーダル -->
    <div
        class="modal-backdrop"
        x-show="showGuideProfileModal"
        x-cloak
        @click.self="closeGuideProfileModal()"
        @keydown.escape.window="closeGuideProfileModal()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="guide-profile-modal-title"
    >
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="guide-profile-modal-title">ガイドプロフィール</h2>
                <div>
                    <button
                        type="button"
                        class="btn-secondary btn-sm"
                        @click.stop="closeGuideProfileModal()"
                        aria-label="モーダルを閉じる"
                    >
                        閉じる
                    </button>
                </div>
            </div>

            <template x-if="selectedGuideProfile">
                <div class="modal-body">
                    <div class="modal-section">
                        <h3>基本情報</h3>
                        <div class="modal-grid">
                                <div class="modal-field">
                                    <strong>氏名</strong>
                                    <template x-if="!editingGuideProfile">
                                        <span x-text="selectedGuideProfile.name || '—'"></span>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <input
                                            type="text"
                                            class="modal-input"
                                            x-model="editingGuideProfileData.name"
                                            aria-label="氏名"
                                            aria-required="true"
                                        />
                                    </template>
                                </div>
                                <div class="modal-field">
                                    <strong>メールアドレス</strong>
                                    <span x-text="selectedGuideProfile.email || '—'"></span>
                                </div>
                                <div class="modal-field" x-show="selectedGuideProfile.phone || editingGuideProfile">
                                    <strong>電話番号</strong>
                                    <template x-if="!editingGuideProfile">
                                        <span x-text="selectedGuideProfile.phone"></span>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <input
                                            type="tel"
                                            class="modal-input"
                                            x-model="editingGuideProfileData.phone"
                                            aria-label="電話番号"
                                        />
                                    </template>
                                </div>
                                <div class="modal-field" x-show="selectedGuideProfile.address || editingGuideProfile">
                                    <strong>住所</strong>
                                    <template x-if="!editingGuideProfile">
                                        <span x-text="selectedGuideProfile.address"></span>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <textarea
                                            class="modal-textarea"
                                            rows="2"
                                            placeholder="都道府県、市区町村、番地を入力してください"
                                            x-model="editingGuideProfileData.address"
                                            aria-label="住所"
                                            aria-describedby="address-help-guide"
                                        ></textarea>
                                        <small id="address-help-guide" class="modal-help-text">都道府県、市区町村、番地を入力してください</small>
                                    </template>
                                </div>
                                <div class="modal-field" x-show="selectedGuideProfile.birth_date">
                                    <strong>生年月日</strong>
                                    <span x-text="formatDate(selectedGuideProfile.birth_date)"></span>
                                </div>
                                <div class="modal-field" x-show="selectedGuideProfile.age && selectedGuideProfile.age > 0">
                                    <strong>年齢</strong>
                                    <span x-text="selectedGuideProfile.age + '歳'"></span>
                                </div>
                                <div class="modal-field modal-field-compact" x-show="selectedGuideProfile.employee_number || editingGuideProfile">
                                    <strong>従業員番号 <span class="modal-label-optional">（任意）</span></strong>
                                    <template x-if="!editingGuideProfile">
                                        <span x-text="selectedGuideProfile.employee_number"></span>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <input
                                            type="text"
                                            class="modal-input"
                                            x-model="editingGuideProfileData.employee_number"
                                            @input="formatEmployeeNumberInModal($event.target.value)"
                                            maxlength="7"
                                            pattern="\d{3}-\d{3}"
                                            placeholder="000-000"
                                            aria-label="従業員番号（000-000形式、任意）"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- プロフィール情報 -->
                        <div class="modal-section">
                            <h3>プロフィール情報</h3>
                            <div class="modal-grid">
                                <div class="modal-field modal-grid-full" x-show="selectedGuideProfile.introduction || editingGuideProfile">
                                    <strong>自己紹介</strong>
                                    <template x-if="!editingGuideProfile">
                                        <div class="modal-display-box" style="min-height: auto;">
                                            <span x-text="selectedGuideProfile.introduction"></span>
                                        </div>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <textarea
                                            class="modal-textarea"
                                            rows="4"
                                            placeholder="自己紹介を入力してください（任意）"
                                            x-model="editingGuideProfileData.introduction"
                                            aria-label="自己紹介（任意）"
                                            id="introduction-guide"
                                        ></textarea>
                                    </template>
                                </div>
                                <div class="modal-field modal-grid-full" x-show="(selectedGuideProfile.available_areas && selectedGuideProfile.available_areas.length > 0) || editingGuideProfile">
                                    <strong>対応可能エリア</strong>
                                    <small class="modal-help-text modal-help-text-before">対応可能な都道府県を選択してください（複数選択可）</small>
                                    <template x-if="!editingGuideProfile">
                                        <div class="modal-display-box" style="min-height: auto;">
                                            <span x-text="selectedGuideProfile.available_areas.join(', ')"></span>
                                        </div>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <div class="modal-checkbox-grid modal-checkbox-grid-scroll">
                                            <template x-for="area in ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県']" :key="area">
                                                <label class="modal-checkbox-label" :for="'area-' + area">
                                                    <input
                                                        type="checkbox"
                                                        :id="'area-' + area"
                                                        :value="area"
                                                        x-model="editingGuideProfileData.available_areas"
                                                        :aria-label="area"
                                                    />
                                                    <span x-text="area"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <div class="modal-field" x-show="(selectedGuideProfile.available_days && selectedGuideProfile.available_days.length > 0) || editingGuideProfile">
                                    <strong>対応可能日</strong>
                                    <small class="modal-help-text modal-help-text-before">対応可能な日を選択してください（複数選択可）</small>
                                    <template x-if="!editingGuideProfile">
                                        <div class="modal-display-box" style="min-height: auto;">
                                            <span x-text="selectedGuideProfile.available_days.join(', ')"></span>
                                        </div>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <div class="modal-checkbox-horizontal">
                                            <template x-for="day in ['平日', '土日', '祝日']" :key="day">
                                                <label class="modal-checkbox-label" :for="'day-' + day">
                                                    <input
                                                        type="checkbox"
                                                        :id="'day-' + day"
                                                        :value="day"
                                                        x-model="editingGuideProfileData.available_days"
                                                        :aria-label="day"
                                                    />
                                                    <span x-text="day"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <div class="modal-field" x-show="(selectedGuideProfile.available_times && selectedGuideProfile.available_times.length > 0) || editingGuideProfile">
                                    <strong>対応可能時間帯</strong>
                                    <small class="modal-help-text modal-help-text-before">対応可能な時間帯を選択してください（複数選択可）</small>
                                    <template x-if="!editingGuideProfile">
                                        <div class="modal-display-box" style="min-height: auto;">
                                            <span x-text="selectedGuideProfile.available_times.join(', ')"></span>
                                        </div>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <div class="modal-checkbox-horizontal">
                                            <template x-for="time in ['午前', '午後', '夜間']" :key="time">
                                                <label class="modal-checkbox-label" :for="'time-' + time">
                                                    <input
                                                        type="checkbox"
                                                        :id="'time-' + time"
                                                        :value="time"
                                                        x-model="editingGuideProfileData.available_times"
                                                        :aria-label="time"
                                                    />
                                                    <span x-text="time"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <!-- 運営用情報 -->
                        <div class="modal-section" x-show="selectedGuideProfile.admin_comment || editingGuideProfile">
                            <h3>運営用情報</h3>
                            <div class="modal-grid">
                                <div class="modal-field modal-grid-full" x-show="selectedGuideProfile.admin_comment || editingGuideProfile">
                                    <strong>運営コメント</strong>
                                    <small class="modal-help-text modal-help-text-before">運営側からのメモ（ガイドには表示されません）</small>
                                    <template x-if="!editingGuideProfile">
                                        <div class="modal-display-box" style="min-height: auto;">
                                            <span x-text="selectedGuideProfile.admin_comment"></span>
                                        </div>
                                    </template>
                                    <template x-if="editingGuideProfile">
                                        <textarea
                                            class="modal-textarea"
                                            rows="4"
                                            placeholder="運営側からのメモを入力してください（ガイドには表示されません）"
                                            x-model="editingGuideProfileData.admin_comment"
                                            aria-label="運営コメント"
                                            aria-describedby="admin-comment-help-guide"
                                        ></textarea>
                                        <small id="admin-comment-help-guide" class="modal-help-text">運営側からのメモ（ガイドには表示されません）</small>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- 編集ボタン（閲覧モード時） - templateの外に配置 -->
            <div 
                class="modal-edit-button" 
                x-show="selectedGuideProfile && !editingGuideProfile"
                x-init="console.log('編集ボタン要素初期化 - editingGuideProfile:', editingGuideProfile, 'selectedGuideProfile:', selectedGuideProfile)"
            >
                <button
                    type="button"
                    class="btn-primary"
                    @click="console.log('編集ボタンクリック - editingGuideProfile:', editingGuideProfile, 'selectedGuideProfile:', selectedGuideProfile); editGuideProfile(selectedGuideProfile.id)"
                    aria-label="プロフィールを編集"
                >
                    編集
                </button>
            </div>
            <!-- 編集モード時の保存・キャンセルボタン -->
            <div class="modal-button-group" x-show="selectedGuideProfile && editingGuideProfile">
                <button
                    type="button"
                    class="btn-secondary btn-sm modal-cancel-btn"
                    @click="cancelEditGuideProfile()"
                    aria-label="編集をキャンセル"
                >
                    キャンセル
                </button>
                <button
                    type="button"
                    class="btn-primary btn-sm"
                    @click="saveGuideProfile(selectedGuideProfile.id)"
                    :aria-busy="savingGuideProfile"
                    aria-label="プロフィールを保存する"
                >
                    <span x-show="!savingGuideProfile">保存する</span>
                    <span x-show="savingGuideProfile">保存中...</span>
                </button>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection

@push('styles')
{{-- CSSはViteでビルドされたapp.scssに含まれています --}}
@endpush

@push('scripts')
<script>
function adminDashboard() {
    return {
        activeTab: 'dashboard',
        requests: [],
        acceptances: [],
        selectedAcceptances: [], // 一括承認用の選択された承諾リスト
        hideOldAcceptances: false, // 長期間未承認を非表示にする設定
        hideOldAcceptancesDays: 30, // 非表示にする日数（デフォルト30日）
        reports: [],
        userApprovedReports: [], // ユーザー承認済み（管理者承認待ち）報告書
        selectedReportMonth: '', // 選択された月（フィルタリング用）
        selectedReports: [], // 一括操作用の選択された報告書IDリスト
        selectedReport: null,    // 詳細表示用
        showReportModal: false,
        selectedUserProfile: null,    // ユーザープロフィール詳細表示用
        showUserProfileModal: false,
        editingUserProfile: false,
        editingUserProfileData: {},
        savingUserProfile: false,
        selectedGuideProfile: null,    // ガイドプロフィール詳細表示用
        showGuideProfileModal: false,
        editingGuideProfile: false,
        editingGuideProfileData: {},
        savingGuideProfile: false,
        users: [],
        guides: [],
        userSortOrder: 'created_desc',
        userSearchQuery: '',
        guideSortOrder: 'created_desc',
        guideSearchQuery: '',
        stats: null,
        autoMatching: false,
        loading: true,
        fetchingUsers: false, // ユーザー取得中のフラグ（重複リクエスト防止）
        fetchingGuides: false, // ガイド取得中のフラグ（重複リクエスト防止）
        fetchingLimits: false, // 限度時間取得中のフラグ（重複リクエスト防止）
        userMeta: {},
        guideMeta: {},
        userAdminComment: {},
        emailTemplates: [],
        showNewTemplateForm: false,
        newTemplate: {
            template_key: '',
            subject: '',
            body: '',
            is_active: true,
            recipient: '' // 'user', 'guide', 'both'
        },
        emailSettings: [],
        operationLogs: [],
        userMonthlyLimits: {},
        userCurrentLimits: {}, // 現在の月の限度時間情報を保持
        notifications: @json($notifications ?? []),

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

            console.log("response", response);
            
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
                // バリデーションエラーの場合、エラーオブジェクトにresponseを含める
                const error = new Error(errorData.message || errorData.error || 'エラーが発生しました');
                error.response = { data: errorData };
                throw error;
            }
            
            return response.json();
        },

        async init() {
            this.loadAcceptanceFilterSetting();
            await this.fetchDashboardData();
            // 設定タブ用データをバックグラウンドで事前取得（タブ表示が速くなる）
            this.fetchEmailSettings();
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
                console.log("承諾待ちデータ:", this.acceptances);
                console.log("acceptances.length:", this.acceptances.length);
                console.log("user_selectedの値:", this.acceptances.map(acc => ({ 
                    id: acc.id, 
                    request_id: acc.request_id, 
                    guide_id: acc.guide_id, 
                    user_selected: acc.user_selected,
                    user_selected_type: typeof acc.user_selected,
                    user_selected_value: acc.user_selected
                })));
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
                const params = new URLSearchParams();
                params.set('sort', this.userSortOrder || 'created_desc');
                if (this.userSearchQuery && this.userSearchQuery.trim() !== '') {
                    params.set('search', this.userSearchQuery.trim());
                }
                const data = await this.apiFetch('/api/admin/users?' + params.toString());
                this.users = data.users || [];
                
                // メタデータを初期化
                this.users.forEach(u => {
                    this.userMeta[u.id] = u.recipient_number || '';
                });
                
                // 限度時間タブ用に、現在月の限度時間/使用時間/残時間を一括取得
                if (this.activeTab === 'monthly-limits') {
                    await this.fetchMonthlyLimitsSummary();
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
            if (this.fetchingGuides) {
                return;
            }
            this.fetchingGuides = true;
            try {
                const params = new URLSearchParams();
                params.set('sort', this.guideSortOrder || 'created_desc');
                if (this.guideSearchQuery && this.guideSearchQuery.trim() !== '') {
                    params.set('search', this.guideSearchQuery.trim());
                }
                const data = await this.apiFetch('/api/admin/guides?' + params.toString());
                this.guides = data.guides || [];
                
                // メタデータを初期化
                this.guides.forEach(g => {
                    this.guideMeta[g.id] = g.employee_number || '';
                });
            } catch (error) {
                console.error('ガイド一覧取得エラー:', error);
                alert('ガイド一覧の取得に失敗しました');
            } finally {
                this.fetchingGuides = false;
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

        toggleAcceptanceSelection(requestId, guideId, checked) {
            console.log('toggleAcceptanceSelection:', { requestId, guideId, checked });
            if (checked) {
                // 既に選択されていない場合のみ追加
                if (!this.selectedAcceptances.find(acc => acc.request_id === requestId && acc.guide_id === guideId)) {
                    this.selectedAcceptances.push({ request_id: requestId, guide_id: guideId });
                    console.log('選択追加:', this.selectedAcceptances);
                }
            } else {
                // 選択を解除
                this.selectedAcceptances = this.selectedAcceptances.filter(
                    acc => !(acc.request_id === requestId && acc.guide_id === guideId)
                );
                console.log('選択解除:', this.selectedAcceptances);
            }
        },

        isAcceptanceSelected(requestId, guideId) {
            return this.selectedAcceptances.some(
                acc => acc.request_id === requestId && acc.guide_id === guideId
            );
        },

        getSelectableAcceptancesCount() {
            // ユーザーが選択されている承諾（選択可能な承諾）の数を返す（フィルタリング後）
            return this.getFilteredAcceptances().filter(acc => acc.user_selected === true).length;
        },

        getFilteredAcceptances() {
            if (!this.acceptances || this.acceptances.length === 0) {
                return [];
            }

            if (!this.hideOldAcceptances) {
                return this.acceptances;
            }

            const now = new Date();
            const daysAgo = new Date(now);
            daysAgo.setDate(now.getDate() - this.hideOldAcceptancesDays);

            return this.acceptances.filter(acc => {
                if (!acc.created_at) {
                    return true; // created_atがない場合は表示
                }

                const createdDate = new Date(acc.created_at);
                // 指定日数より新しい（最近の）データのみ表示
                return createdDate >= daysAgo;
            });
        },

        loadAcceptanceFilterSetting() {
            // ローカルストレージから設定を読み込む
            try {
                const saved = localStorage.getItem('hideOldAcceptances');
                if (saved !== null) {
                    this.hideOldAcceptances = saved === 'true';
                }
                const savedDays = localStorage.getItem('hideOldAcceptancesDays');
                if (savedDays !== null) {
                    this.hideOldAcceptancesDays = parseInt(savedDays, 10) || 30;
                }
            } catch (e) {
                console.error('設定の読み込みに失敗しました:', e);
            }
        },

        saveAcceptanceFilterSetting() {
            // ローカルストレージに設定を保存
            try {
                localStorage.setItem('hideOldAcceptances', this.hideOldAcceptances.toString());
                localStorage.setItem('hideOldAcceptancesDays', this.hideOldAcceptancesDays.toString());
            } catch (e) {
                console.error('設定の保存に失敗しました:', e);
            }
        },

        toggleSelectAllAcceptances(checked) {
            console.log('toggleSelectAllAcceptances:', checked);
            // ユーザーが選択されている承諾のみを対象とする（フィルタリング後）
            const selectableAcceptances = this.getFilteredAcceptances().filter(acc => acc.user_selected === true);
            
            if (checked) {
                // 一括選択: 選択可能なすべての承諾を選択
                this.selectedAcceptances = selectableAcceptances.map(acc => ({ 
                    request_id: acc.request_id, 
                    guide_id: acc.guide_id 
                }));
                console.log('全選択後のselectedAcceptances:', this.selectedAcceptances);
            } else {
                // 一括解除: すべての選択を解除
                this.selectedAcceptances = [];
                console.log('全選択解除');
            }
        },

        async batchApproveMatchings() {
            if (this.selectedAcceptances.length === 0) {
                alert('承認する項目を選択してください');
                return;
            }

            // ユーザーが選択されている承諾のみを承認
            const validAcceptances = this.selectedAcceptances.filter(acc => {
                const acceptance = this.acceptances.find(
                    a => a.request_id === acc.request_id && a.guide_id === acc.guide_id
                );
                return acceptance && acceptance.user_selected === true;
            });

            if (validAcceptances.length === 0) {
                alert('ユーザーが選択されている承諾のみ承認できます');
                return;
            }

            if (!confirm(`選択した${validAcceptances.length}件のマッチングを承認しますか？`)) {
                return;
            }

            try {
                const response = await this.apiFetch('/api/admin/matchings/batch-approve', {
                    method: 'POST',
                    body: JSON.stringify({
                        matchings: validAcceptances
                    })
                });

                const successCount = response.results?.success?.length || 0;
                const failedCount = response.results?.failed?.length || 0;

                let message = `${successCount}件のマッチングを承認しました`;
                if (failedCount > 0) {
                    message += `\n${failedCount}件の承認に失敗しました`;
                    if (response.results?.failed) {
                        const errors = response.results.failed.map(f => 
                            `依頼ID ${f.request_id}: ${f.error}`
                        ).join('\n');
                        message += '\n\n失敗した項目:\n' + errors;
                    }
                }

                alert(message);
                this.selectedAcceptances = [];
                await this.fetchDashboardData();
            } catch (error) {
                let errorMessage = '一括承認に失敗しました';
                if (error.response && error.response.data && error.response.data.error) {
                    errorMessage = error.response.data.error;
                } else if (error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
                console.error(error);
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

        toggleReportSelection(reportId, checked) {
            if (checked) {
                if (!this.selectedReports.includes(reportId)) {
                    this.selectedReports.push(reportId);
                }
            } else {
                this.selectedReports = this.selectedReports.filter(id => id !== reportId);
            }
        },

        isReportSelected(reportId) {
            return this.selectedReports.includes(reportId);
        },

        toggleSelectAllReports(checked) {
            if (checked) {
                this.selectedReports = this.userApprovedReports.map(report => report.id);
            } else {
                this.selectedReports = [];
            }
        },

        async approveAllReports() {
            if (!confirm(`すべての報告書（${this.userApprovedReports.length}件）を管理者承認しますか？`)) return;
            
            const reportIds = this.userApprovedReports.map(report => report.id);
            await this.batchApproveReports(reportIds);
        },

        async batchApproveReports(reportIds = null) {
            const ids = reportIds || this.selectedReports;
            if (!ids || ids.length === 0) {
                alert('承認する報告書を選択してください');
                return;
            }
            
            if (!confirm(`選択した${ids.length}件の報告書を管理者承認しますか？`)) return;
            
            try {
                const data = await this.apiFetch('/api/admin/reports/batch-approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        report_ids: ids
                    })
                });
                
                alert(data.message || `${data.successful_count || ids.length}件の報告書を管理者承認しました`);
                this.selectedReports = [];
                await this.fetchDashboardData();
            } catch (error) {
                console.error('一括承認エラー:', error);
                alert('報告書の一括承認に失敗しました: ' + error.message);
            }
        },

        async batchReturnReports() {
            if (this.selectedReports.length === 0) {
                alert('差し戻しする報告書を選択してください');
                return;
            }
            
            const revisionNotes = prompt(`選択した${this.selectedReports.length}件の報告書の差し戻し理由を入力してください（ガイドに通知されます）:`);
            if (!revisionNotes || !revisionNotes.trim()) {
                alert('差し戻し理由を入力してください');
                return;
            }
            
            if (!confirm(`選択した${this.selectedReports.length}件の報告書をガイドに差し戻しますか？`)) return;
            
            try {
                const data = await this.apiFetch('/api/admin/reports/batch-return', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        report_ids: this.selectedReports,
                        revision_notes: revisionNotes.trim()
                    })
                });
                
                alert(data.message || `${data.successful_count || this.selectedReports.length}件の報告書を差し戻しました`);
                this.selectedReports = [];
                await this.fetchDashboardData();
            } catch (error) {
                console.error('一括差し戻しエラー:', error);
                alert('報告書の一括差し戻しに失敗しました: ' + error.message);
            }
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
            // 入力欄から直接値を取得
            const inputElement = document.getElementById(`recipient-number-${userId}`);
            const recipientNumber = inputElement ? inputElement.value.trim() : (this.userMeta[userId] || '').trim();
            
            // フロントエンドでバリデーションチェック
            if (recipientNumber && recipientNumber !== '') {
                if (!/^\d{10}$/.test(recipientNumber)) {
                    alert('受給者証番号は半角数字10桁で入力してください。');
                    if (inputElement) {
                        inputElement.focus();
                    }
                    return;
                }
            }
            
            try {
                await this.apiFetch(`/api/admin/users/${userId}/profile-extra`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        recipient_number: recipientNumber || null,
                        admin_comment: null
                    })
                });
                alert('受給者証番号を更新しました');
                await this.fetchUsers();
            } catch (error) {
                let errorMessage = '受給者証番号の更新に失敗しました';
                
                if (error.response && error.response.data) {
                    if (error.response.data.errors) {
                        const errors = error.response.data.errors;
                        const errorMessages = Object.values(errors).flat();
                        errorMessage = errorMessages.join('\n');
                    } else if (error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.data.error) {
                        errorMessage = error.response.data.error;
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }
                
                alert(errorMessage);
                if (inputElement) {
                    inputElement.focus();
                }
            }
        },

        formatEmployeeNumber(guideId, value) {
            // 数字以外を削除
            let digits = value.replace(/\D/g, '');
            // 6桁まで制限
            digits = digits.slice(0, 6);
            // 3桁-3桁の形式にフォーマット
            if (digits.length <= 3) {
                this.guideMeta[guideId] = digits;
            } else {
                this.guideMeta[guideId] = digits.slice(0, 3) + '-' + digits.slice(3, 6);
            }
        },

        formatEmployeeNumberInModal(value) {
            // 数字以外を削除
            let digits = value.replace(/\D/g, '');
            // 6桁まで制限
            digits = digits.slice(0, 6);
            // 3桁-3桁の形式にフォーマット
            if (digits.length <= 3) {
                this.editingGuideProfileData.employee_number = digits;
            } else {
                this.editingGuideProfileData.employee_number = digits.slice(0, 3) + '-' + digits.slice(3, 6);
            }
        },

        async saveGuideMeta(guideId) {
            // 入力欄から直接値を取得
            const inputElement = document.getElementById(`employee-number-${guideId}`);
            const employeeNumber = inputElement ? inputElement.value.trim() : (this.guideMeta[guideId] || '').trim();
            
            // フロントエンドでバリデーションチェック
            if (employeeNumber && employeeNumber !== '') {
                if (!/^\d{3}-\d{3}$/.test(employeeNumber)) {
                    alert('従業員番号は000-000形式（半角数字6桁をハイフンで区切る）で入力してください。');
                    if (inputElement) {
                        inputElement.focus();
                    }
                    return;
                }
            }
            
            try {
                await this.apiFetch(`/api/admin/guides/${guideId}/profile-extra`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        employee_number: employeeNumber || null
                    })
                });
                alert('従業員番号を更新しました');
                await this.fetchGuides();
            } catch (error) {
                let errorMessage = '従業員番号の更新に失敗しました';
                
                if (error.response && error.response.data) {
                    if (error.response.data.errors) {
                        const errors = error.response.data.errors;
                        const errorMessages = Object.values(errors).flat();
                        errorMessage = errorMessages.join('\n');
                    } else if (error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.data.error) {
                        errorMessage = error.response.data.error;
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }
                
                alert(errorMessage);
                if (inputElement) {
                    inputElement.focus();
                }
            }
        },

        async openUserProfileModal(userId) {
            try {
                const user = this.users.find(u => u.id === userId);
                if (!user) {
                    alert('ユーザーが見つかりません');
                    return;
                }
                this.selectedUserProfile = { ...user };
                this.showUserProfileModal = true;
                this.editingUserProfile = false;
            } catch (error) {
                console.error('ユーザープロフィール取得エラー:', error);
                alert('ユーザープロフィールの取得に失敗しました');
            }
        },

        closeUserProfileModal() {
            this.showUserProfileModal = false;
            this.selectedUserProfile = null;
            this.editingUserProfile = false;
            this.editingUserProfileData = {};
        },

        editUserProfile(userId) {
            if (!this.selectedUserProfile || this.selectedUserProfile.id !== userId) {
                return;
            }
            this.editingUserProfile = true;
            this.editingUserProfileData = {
                name: this.selectedUserProfile.name || '',
                phone: this.selectedUserProfile.phone || '',
                address: this.selectedUserProfile.address || '',
                contact_method: this.selectedUserProfile.contact_method || '',
                notes: this.selectedUserProfile.notes || '',
                introduction: this.selectedUserProfile.introduction || '',
                recipient_number: this.selectedUserProfile.recipient_number || '',
            };
        },

        cancelEditUserProfile() {
            this.editingUserProfile = false;
            this.editingUserProfileData = {};
        },

        async saveUserProfile(userId) {
            if (!this.editingUserProfileData) {
                return;
            }
            this.savingUserProfile = true;
            try {
                await this.apiFetch(`/api/admin/users/${userId}/profile`, {
                    method: 'PUT',
                    body: JSON.stringify(this.editingUserProfileData)
                });
                alert('プロフィールを更新しました');
                await this.fetchUsers();
                // 更新後のデータを再取得してモーダルを更新
                const user = this.users.find(u => u.id === userId);
                if (user) {
                    this.selectedUserProfile = { ...user };
                }
                this.editingUserProfile = false;
                this.editingUserProfileData = {};
            } catch (error) {
                console.error('プロフィール更新エラー:', error);
                let errorMessage = 'プロフィールの更新に失敗しました';
                if (error.response && error.response.data) {
                    if (error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.data.errors) {
                        const errors = error.response.data.errors;
                        const errorMessages = Object.values(errors).flat();
                        errorMessage = errorMessages.join('\n');
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
            } finally {
                this.savingUserProfile = false;
            }
        },

        async openGuideProfileModal(guideId) {
            try {
                const guide = this.guides.find(g => g.id === guideId);
                if (!guide) {
                    alert('ガイドが見つかりません');
                    return;
                }
                this.selectedGuideProfile = { ...guide };
                this.showGuideProfileModal = true;
                this.editingGuideProfile = false;
                console.log('openGuideProfileModal - editingGuideProfile:', this.editingGuideProfile);
                console.log('openGuideProfileModal - selectedGuideProfile:', this.selectedGuideProfile);
            } catch (error) {
                console.error('ガイドプロフィール取得エラー:', error);
                alert('ガイドプロフィールの取得に失敗しました');
            }
        },

        closeGuideProfileModal() {
            console.log('closeGuideProfileModal - editingGuideProfile:', this.editingGuideProfile);
            this.showGuideProfileModal = false;
            this.selectedGuideProfile = null;
            this.editingGuideProfile = false;
            this.editingGuideProfileData = {};
        },

        editGuideProfile(guideId) {
            console.log('editGuideProfile - before:', {
                editingGuideProfile: this.editingGuideProfile,
                selectedGuideProfile: this.selectedGuideProfile,
                guideId: guideId
            });
            if (!this.selectedGuideProfile || this.selectedGuideProfile.id !== guideId) {
                console.log('editGuideProfile - early return');
                return;
            }
            this.editingGuideProfile = true;
            console.log('editGuideProfile - after setting to true:', this.editingGuideProfile);
            this.editingGuideProfileData = {
                name: this.selectedGuideProfile.name || '',
                phone: this.selectedGuideProfile.phone || '',
                address: this.selectedGuideProfile.address || '',
                introduction: this.selectedGuideProfile.introduction || '',
                available_areas: Array.isArray(this.selectedGuideProfile.available_areas) 
                    ? [...this.selectedGuideProfile.available_areas] 
                    : [],
                available_days: Array.isArray(this.selectedGuideProfile.available_days) 
                    ? [...this.selectedGuideProfile.available_days] 
                    : [],
                available_times: Array.isArray(this.selectedGuideProfile.available_times) 
                    ? [...this.selectedGuideProfile.available_times] 
                    : [],
                employee_number: this.selectedGuideProfile.employee_number || '',
                admin_comment: this.selectedGuideProfile.admin_comment || '',
            };
        },

        cancelEditGuideProfile() {
            console.log('cancelEditGuideProfile - before:', this.editingGuideProfile);
            this.editingGuideProfile = false;
            this.editingGuideProfileData = {};
            console.log('cancelEditGuideProfile - after:', this.editingGuideProfile);
        },

        async saveGuideProfile(guideId) {
            if (!this.editingGuideProfileData) {
                return;
            }
            this.savingGuideProfile = true;
            try {
                await this.apiFetch(`/api/admin/guides/${guideId}/profile`, {
                    method: 'PUT',
                    body: JSON.stringify(this.editingGuideProfileData)
                });
                alert('プロフィールを更新しました');
                await this.fetchGuides();
                // 更新後のデータを再取得してモーダルを更新
                const guide = this.guides.find(g => g.id === guideId);
                if (guide) {
                    this.selectedGuideProfile = { ...guide };
                }
                this.editingGuideProfile = false;
                this.editingGuideProfileData = {};
            } catch (error) {
                console.error('プロフィール更新エラー:', error);
                let errorMessage = 'プロフィールの更新に失敗しました';
                if (error.response && error.response.data) {
                    if (error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.data.errors) {
                        const errors = error.response.data.errors;
                        const errorMessages = Object.values(errors).flat();
                        errorMessage = errorMessages.join('\n');
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
            } finally {
                this.savingGuideProfile = false;
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
                console.error('設定取得エラー:', error);
                alert('設定の取得に失敗しました');
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

        updateRecipientFromKey() {
            // 既存のテンプレートキーに基づいて送信先を自動設定
            const key = this.newTemplate.template_key;
            if (!key) {
                this.newTemplate.recipient = '';
                return;
            }
            
            const recipientMap = {
                'request_notification': 'guide',
                'matching_notification': 'both',
                'report_submitted': 'user',
                'report_approved': 'guide',
                'reminder_pending_request': 'user',
                'password_reset': 'both',
                'user_registration_thanks': 'user',
                'guide_registration_thanks': 'guide',
                'reminder_same_day': 'both',
                'reminder_day_before': 'both',
                'reminder_report_missing': 'guide'
            };
            
            if (recipientMap[key]) {
                this.newTemplate.recipient = recipientMap[key];
            }
        },

        getRecipientLabelForNew(template) {
            if (!template.recipient) return '未選択';
            const labels = {
                'user': 'ユーザー',
                'guide': 'ガイド',
                'both': 'ユーザー・ガイド'
            };
            return labels[template.recipient] || '未選択';
        },

        getRecipientClassForNew(template) {
            if (!template.recipient) return '';
            const classes = {
                'user': 'recipient-user',
                'guide': 'recipient-guide',
                'both': 'recipient-both'
            };
            return classes[template.recipient] || '';
        },

        async createEmailTemplate() {
            if (!this.newTemplate.template_key || !this.newTemplate.subject || !this.newTemplate.body || !this.newTemplate.recipient) {
                alert('すべての必須項目を入力してください');
                return;
            }

            // テンプレートキーのバリデーション（英数字とアンダースコアのみ）
            if (!/^[a-zA-Z0-9_]+$/.test(this.newTemplate.template_key)) {
                alert('テンプレートキーは英数字とアンダースコアのみ使用可能です');
                return;
            }

            try {
                await this.apiFetch('/api/admin/email-templates', {
                    method: 'POST',
                    body: JSON.stringify({
                        template_key: this.newTemplate.template_key,
                        subject: this.newTemplate.subject,
                        body: this.newTemplate.body,
                        is_active: this.newTemplate.is_active
                    })
                });
                alert('テンプレートを作成しました');
                this.showNewTemplateForm = false;
                this.newTemplate = { template_key: '', subject: '', body: '', is_active: true, recipient: '' };
                await this.fetchEmailTemplates();
            } catch (error) {
                const errorMessage = error.message || 'テンプレートの作成に失敗しました';
                alert(errorMessage);
                console.error(error);
            }
        },

        async deleteEmailTemplate(templateId) {
            const template = this.emailTemplates.find(t => t.id === templateId);
            if (!template) return;

            if (!confirm(`テンプレート「${template.template_key}」を削除しますか？この操作は取り消せません。`)) {
                return;
            }

            try {
                await this.apiFetch(`/api/admin/email-templates/${templateId}`, {
                    method: 'DELETE'
                });
                alert('テンプレートを削除しました');
                await this.fetchEmailTemplates();
            } catch (error) {
                alert('テンプレートの削除に失敗しました');
                console.error(error);
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
                alert('設定を更新しました');
                await this.fetchEmailSettings();
            } catch (error) {
                alert('設定の更新に失敗しました');
                console.error(error);
            }
        },

        async setUserMonthlyLimit(userId) {
            const year = parseInt(document.getElementById(`year-${userId}`).value);
            const month = parseInt(document.getElementById(`month-${userId}`).value);
            const limitOuting = parseFloat(document.getElementById(`limit-outing-${userId}`).value);
            const limitHome = parseFloat(document.getElementById(`limit-home-${userId}`).value);
            const outingVal = !isNaN(limitOuting) && limitOuting >= 0 ? limitOuting : 0;
            const homeVal = !isNaN(limitHome) && limitHome >= 0 ? limitHome : 0;

            if (isNaN(limitOuting) && isNaN(limitHome)) {
                alert('外出・自宅の限度時間を入力してください（未入力の場合は0として保存されます）');
                return;
            }

            if (!confirm(`${year}年${month}月の限度時間を設定しますか？\n外出: ${outingVal}時間 / 自宅: ${homeVal}時間`)) {
                return;
            }

            try {
                await this.apiFetch(`/api/admin/users/${userId}/monthly-limit`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        limit_hours: outingVal,
                        year: year,
                        month: month,
                        request_type: 'outing'
                    })
                });
                await this.apiFetch(`/api/admin/users/${userId}/monthly-limit`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        limit_hours: homeVal,
                        year: year,
                        month: month,
                        request_type: 'home'
                    })
                });
                alert('限度時間を設定しました');
                await this.fetchUserCurrentLimit(userId);
            } catch (error) {
                alert('限度時間の設定に失敗しました');
                console.error(error);
            }
        },

        async loadUserMonthlyLimits(userId) {
            try {
                const data = await this.apiFetch(`/api/admin/users/${userId}/monthly-limits`);
                this.userMonthlyLimits[userId] = data.limits || [];
                const limits = data.limits || [];
                const byMonth = {};
                limits.forEach(limit => {
                    const key = `${limit.year}-${limit.month}`;
                    if (!byMonth[key]) byMonth[key] = { year: limit.year, month: limit.month, outing: null, home: null };
                    if (limit.request_type === 'outing') byMonth[key].outing = limit;
                    if (limit.request_type === 'home') byMonth[key].home = limit;
                });
                let message = `ユーザーID ${userId} の限度時間履歴:\n\n`;
                Object.keys(byMonth).sort().reverse().forEach(key => {
                    const m = byMonth[key];
                    const fmt = (l) => l ? `限度${l.limit_hours} / 使用${(l.used_hours || 0).toFixed(1)} / 残${(l.remaining_hours != null ? l.remaining_hours : (l.limit_hours - (l.used_hours || 0))).toFixed(1)}h` : '—';
                    message += `${m.year}年${m.month}月\n  外出: ${fmt(m.outing)}\n  自宅: ${fmt(m.home)}\n`;
                });
                if (Object.keys(byMonth).length === 0) message += '履歴がありません';
                alert(message);
            } catch (error) {
                console.error('限度時間履歴取得エラー:', error);
                alert('限度時間履歴の取得に失敗しました');
            }
        },

        getUserOutingLimitHours(userId) {
            const cur = this.userCurrentLimits[userId];
            if (!cur || !cur.outing) return '';
            return (cur.outing.limit_hours ?? 0);
        },
        getUserHomeLimitHours(userId) {
            const cur = this.userCurrentLimits[userId];
            if (!cur || !cur.home) return '';
            return (cur.home.limit_hours ?? 0);
        },
        getUserOutingUsedHours(userId) {
            const cur = this.userCurrentLimits[userId];
            if (!cur || !cur.outing) return '0.0';
            return (cur.outing.used_hours ?? 0).toFixed(1);
        },
        getUserHomeUsedHours(userId) {
            const cur = this.userCurrentLimits[userId];
            if (!cur || !cur.home) return '0.0';
            return (cur.home.used_hours ?? 0).toFixed(1);
        },
        getUserOutingRemainingHours(userId) {
            const cur = this.userCurrentLimits[userId];
            if (!cur || !cur.outing) return '0.0';
            const r = cur.outing.remaining_hours;
            if (r != null) return Number(r).toFixed(1);
            return ((cur.outing.limit_hours ?? 0) - (cur.outing.used_hours ?? 0)).toFixed(1);
        },
        getUserHomeRemainingHours(userId) {
            const cur = this.userCurrentLimits[userId];
            if (!cur || !cur.home) return '0.0';
            const r = cur.home.remaining_hours;
            if (r != null) return Number(r).toFixed(1);
            return ((cur.home.limit_hours ?? 0) - (cur.home.used_hours ?? 0)).toFixed(1);
        },

        async fetchUserCurrentLimit(userId) {
            try {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth() + 1;
                const data = await this.apiFetch(`/api/admin/users/${userId}/monthly-limits?year=${year}&month=${month}`);
                const limits = data.limits || [];
                const def = () => ({ limit_hours: 0, used_hours: 0, remaining_hours: 0 });
                const outing = limits.find(l => l.request_type === 'outing') || null;
                const home = limits.find(l => l.request_type === 'home') || null;
                const toRow = (l) => ({
                    limit_hours: l.limit_hours || 0,
                    used_hours: l.used_hours || 0,
                    remaining_hours: l.remaining_hours != null ? l.remaining_hours : ((l.limit_hours || 0) - (l.used_hours || 0))
                });
                this.userCurrentLimits[userId] = {
                    outing: outing ? toRow(outing) : def(),
                    home: home ? toRow(home) : def()
                };
            } catch (error) {
                console.error(`ユーザー${userId}の限度時間取得エラー:`, error);
                this.userCurrentLimits[userId] = {
                    outing: { limit_hours: 0, used_hours: 0, remaining_hours: 0 },
                    home: { limit_hours: 0, used_hours: 0, remaining_hours: 0 }
                };
            }
        },

        // 全利用者の現在月の限度時間・残時間を一括取得（照会用・一覧表示用）
        async fetchMonthlyLimitsSummary() {
            if (!this.users || this.users.length === 0) return;
            if (this.fetchingLimits) return;
            this.fetchingLimits = true;
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth() + 1;
            const def = () => ({ outing: { limit_hours: 0, used_hours: 0, remaining_hours: 0 }, home: { limit_hours: 0, used_hours: 0, remaining_hours: 0 } });
            try {
                const data = await this.apiFetch(`/api/admin/users/monthly-limits-summary?year=${year}&month=${month}`);
                const summary = data.summary || [];
                summary.forEach(row => {
                    this.userCurrentLimits[row.user_id] = {
                        outing: row.outing || def().outing,
                        home: row.home || def().home
                    };
                });
                this.users.forEach(u => {
                    if (!this.userCurrentLimits[u.id]) {
                        this.userCurrentLimits[u.id] = def();
                    }
                });
            } catch (error) {
                console.error('限度時間一覧の取得エラー:', error);
                for (let i = 0; i < this.users.length; i += 3) {
                    const batch = this.users.slice(i, i + 3);
                    await Promise.allSettled(batch.map(user => this.fetchUserCurrentLimit(user.id)));
                    if (i + 3 < this.users.length) await new Promise(r => setTimeout(r, 200));
                }
            } finally {
                this.fetchingLimits = false;
            }
        },

        getMonthlyLimitsSummaryCsvUrl() {
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth() + 1;
            return `/api/admin/users/monthly-limits-summary.csv?year=${year}&month=${month}`;
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
                'reminder_pending_request': '承認待ち依頼リマインド',
                'password_reset': 'パスワードリセット',
                'user_registration_thanks': 'ユーザー登録お礼',
                'guide_registration_thanks': 'ガイド登録お礼',
                'reminder_same_day': '当日リマインド',
                'reminder_day_before': '前日リマインド',
                'reminder_report_missing': '報告書未提出リマインド'
            };
            return labels[key] || key;
        },

        getRecipientLabel(key) {
            if (!key) return '';
            const recipients = {
                'request_notification': 'ガイド',
                'matching_notification': 'ユーザー・ガイド',
                'report_submitted': 'ユーザー',
                'report_approved': 'ガイド',
                'reminder_pending_request': 'ユーザー',
                'password_reset': 'ユーザー・ガイド',
                'user_registration_thanks': 'ユーザー',
                'guide_registration_thanks': 'ガイド',
                'reminder_same_day': 'ユーザー・ガイド',
                'reminder_day_before': 'ユーザー・ガイド',
                'reminder_report_missing': 'ガイド'
            };
            return recipients[key] || '不明';
        },

        getRecipientClass(key) {
            if (!key) return '';
            const classes = {
                'request_notification': 'recipient-guide',
                'matching_notification': 'recipient-both',
                'report_submitted': 'recipient-user',
                'report_approved': 'recipient-guide',
                'reminder_pending_request': 'recipient-user',
                'password_reset': 'recipient-both',
                'user_registration_thanks': 'recipient-user',
                'guide_registration_thanks': 'recipient-guide',
                'reminder_same_day': 'recipient-both',
                'reminder_day_before': 'recipient-both',
                'reminder_report_missing': 'recipient-guide'
            };
            return classes[key] || '';
        },

        getTriggerDescription(key) {
            if (!key) return '';
            const descriptions = {
                'request_notification': '新しい依頼が登録された時',
                'matching_notification': 'マッチングが成立した時',
                'report_submitted': '報告書が提出された時',
                'report_approved': '報告書が承認された時',
                'reminder_pending_request': '承認待ち依頼がある時（リマインド）',
                'password_reset': 'パスワードリセットがリクエストされた時',
                'user_registration_thanks': 'ユーザー登録が完了した時',
                'guide_registration_thanks': 'ガイド登録が完了した時',
                'reminder_same_day': '依頼当日のリマインド',
                'reminder_day_before': '依頼前日のリマインド',
                'reminder_report_missing': '報告書が未提出の場合のリマインド'
            };
            return descriptions[key] || '';
        },

        getNotificationDescription(type) {
            const descriptions = {
                'request': '新しい依頼が登録された際に送信される通知',
                'matching': 'マッチングが成立した際に送信される通知',
                'report': '報告書が提出・承認された際に送信される通知',
                'reminder': '承認待ちの依頼がある場合に送信されるリマインド'
            };
            return descriptions[type] || '';
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
        },
        
        formatDateOnly(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}.${month}.${day}`;
        },
        
        formatTimeOnly(timeStr) {
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
        },
        
        formatRelativeTime(dateStr, timeStr) {
            if (!dateStr) return '';
            
            try {
                // 日付と時刻を結合してDateオブジェクトを作成
                let dateTimeStr = dateStr;
                if (timeStr) {
                    const timeMatch = timeStr.match(/^(\d{1,2}):(\d{2})/);
                    if (timeMatch) {
                        dateTimeStr = `${dateStr}T${timeMatch[1].padStart(2, '0')}:${timeMatch[2]}:00`;
                    }
                }
                
                const targetDate = new Date(dateTimeStr);
                const now = new Date();
                const diffMs = now - targetDate;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                const diffMinutes = Math.floor(diffMs / (1000 * 60));
                
                if (diffDays > 0) {
                    return `${diffDays}日前`;
                } else if (diffHours > 0) {
                    return `${diffHours}時間前`;
                } else if (diffMinutes > 0) {
                    return `${diffMinutes}分前`;
                } else {
                    return 'たった今';
                }
            } catch (e) {
                return '';
            }
        },
        getReportsByMonth() {
            if (!this.reports || this.reports.length === 0) {
                return {};
            }

            const grouped = {};
            
            this.reports.forEach(report => {
                if (!report.actual_date) {
                    // actual_dateがない場合は「日付不明」として分類
                    const key = '日付不明';
                    if (!grouped[key]) {
                        grouped[key] = [];
                    }
                    grouped[key].push(report);
                    return;
                }

                const date = new Date(report.actual_date);
                const year = date.getFullYear();
                const month = date.getMonth() + 1;
                const monthKey = `${year}年${month}月`;

                if (!grouped[monthKey]) {
                    grouped[monthKey] = [];
                }
                grouped[monthKey].push(report);
            });

            // 月別にソート（新しい順）
            const sorted = {};
            const keys = Object.keys(grouped).sort((a, b) => {
                // 「日付不明」は最後に
                if (a === '日付不明') return 1;
                if (b === '日付不明') return -1;
                
                // 年月で比較（新しい順）
                const aMatch = a.match(/(\d+)年(\d+)月/);
                const bMatch = b.match(/(\d+)年(\d+)月/);
                
                if (!aMatch || !bMatch) return 0;
                
                const aYear = parseInt(aMatch[1], 10);
                const aMonth = parseInt(aMatch[2], 10);
                const bYear = parseInt(bMatch[1], 10);
                const bMonth = parseInt(bMatch[2], 10);
                
                if (aYear !== bYear) {
                    return bYear - aYear; // 年が新しい順
                }
                return bMonth - aMonth; // 月が新しい順
            });

            keys.forEach(key => {
                sorted[key] = grouped[key];
            });

            return sorted;
        },

        getAvailableMonths() {
            // 利用可能な月のリストを取得（新しい順）
            const grouped = this.getReportsByMonth();
            return Object.keys(grouped).sort((a, b) => {
                // 「日付不明」は最後に
                if (a === '日付不明') return 1;
                if (b === '日付不明') return -1;
                
                // 年月で比較（新しい順）
                const aMatch = a.match(/(\d+)年(\d+)月/);
                const bMatch = b.match(/(\d+)年(\d+)月/);
                
                if (!aMatch || !bMatch) return 0;
                
                const aYear = parseInt(aMatch[1], 10);
                const aMonth = parseInt(aMatch[2], 10);
                const bYear = parseInt(bMatch[1], 10);
                const bMonth = parseInt(bMatch[2], 10);
                
                if (aYear !== bYear) {
                    return bYear - aYear; // 年が新しい順
                }
                return bMonth - aMonth; // 月が新しい順
            });
        },

        getFilteredReportsByMonth() {
            const allReportsByMonth = this.getReportsByMonth();
            
            // 月が選択されていない場合はすべて表示
            if (!this.selectedReportMonth) {
                return allReportsByMonth;
            }
            
            // 選択された月だけを表示
            const filtered = {};
            if (allReportsByMonth[this.selectedReportMonth]) {
                filtered[this.selectedReportMonth] = allReportsByMonth[this.selectedReportMonth];
            }
            
            return filtered;
        },

        filterReportsByMonth() {
            // フィルタリング処理（Alpine.jsのリアクティビティで自動的に更新される）
            // この関数は主にデバッグや将来の拡張用
        },

        formatReportDate(dateStr) {
            if (!dateStr) return '';
            
            // 日付を年/月/日にフォーマット
            const date = new Date(dateStr);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;
            const day = date.getDate();
            
            return `${year}/${month}/${day}`;
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
        readStatus: null,
        showReadStatusModal: false,

        async init() {
            await this.fetchAnnouncements();
        },

        async fetchReadStatus(announcementId) {
            try {
                const api = window.apiFetch || ((url) => fetch(url, { credentials: 'include', headers: { 'Accept': 'application/json' } }).then(r => r.json()));
                const data = await api(`/api/announcements/admin/${announcementId}/read-status`);
                this.readStatus = data;
                this.showReadStatusModal = true;
            } catch (e) {
                console.error(e);
                alert('既読状況の取得に失敗しました');
            }
        },

        closeReadStatusModal() {
            this.showReadStatusModal = false;
            this.readStatus = null;
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
