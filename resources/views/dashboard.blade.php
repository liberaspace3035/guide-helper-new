@extends('layouts.app')

@section('content')
<div class="dashboard" x-data="dashboardData()" x-init="init()">
    <!-- ダッシュボードヘッダー -->
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1><span x-text="greeting"></span>、{{ $user->name }}さん</h1>
            <p class="welcome-message">
                @if($user->isUser())
                    今日も素敵な一日をお過ごしください
                @elseif($user->isGuide())
                    本日もガイド活動をよろしくお願いします
                @elseif($user->isAdmin())
                    システム管理画面へようこそ
                @endif
            </p>
        </div>
    </div>

    <!-- メインコンテンツエリア -->
    <div class="dashboard-content">

        <!-- 重要情報セクション（通知・お知らせ） -->
        <div class="dashboard-alerts">
            <!-- 通知セクション -->
            <template x-if="notifications.length > 0">
                <section class="notifications-section" aria-label="通知">
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

            <!-- 運営からのお知らせセクション -->
            <template x-if="announcements.length > 0">
                <section class="announcements-section" aria-label="運営からのお知らせ">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            運営からのお知らせ
                        </h2>
                        <a href="{{ route('announcements.index') }}" class="view-all-link">
                            すべて見る
                        </a>
                    </div>
                    <div class="announcements-list">
                        <template x-for="announcement in announcements.slice(0, 3)" :key="announcement.id">
                            <div class="announcement-card">
                                <h3 x-text="announcement.title"></h3>
                                <p x-text="announcement.content"></p>
                            </div>
                        </template>
                    </div>
                </section>
            </template>
        </div>

        @if($user->isUser())
            <!-- ユーザー向けダッシュボード -->
            <!-- クイックアクション -->
            <section class="quick-actions-section">
                <h2 class="section-title">クイックアクション</h2>
                <div class="quick-actions">
                    <a href="{{ route('requests.create') }}" class="quick-action-btn primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>新規依頼を作成</span>
                    </a>
                    <a href="{{ route('requests.index') }}" class="quick-action-btn secondary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                        <span>依頼一覧</span>
                    </a>
                </div>
            </section>

            <!-- 承認待ち・修正待ち報告書 -->
            <template x-if="pendingReports.length > 0">
                <section class="pending-reports-section">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span x-text="getPendingReportsTitle()"></span>
                        </h2>
                        <span class="pending-count" x-text="pendingReports.length + '件'"></span>
                    </div>
                    <div class="pending-reports-list">
                        <template x-for="report in pendingReports.slice(0, 3)" :key="report.id">
                            <a :href="`{{ url('/reports') }}/${report.id}`" class="pending-report-item" :class="{'revision-requested': report.status === 'revision_requested'}">
                                <div class="report-info">
                                    <span class="report-type" x-text="report.request_type"></span>
                                    <span class="report-date" x-text="formatDate(report.actual_date)"></span>
                                    <template x-if="report.status === 'revision_requested'">
                                        <span class="report-status-badge revision-badge">修正待ち</span>
                                    </template>
                                    <template x-if="report.status === 'submitted'">
                                        <span class="report-status-badge submitted-badge">承認待ち</span>
                                    </template>
                                </div>
                                <span class="report-guide">ガイド: <span x-text="report.guide_name"></span></span>
                            </a>
                        </template>
                    </div>
                </section>
            </template>

            <!-- 統計・利用状況カード -->
            <div class="dashboard-cards" x-show="stats">
            <div class="card stats-card">
                <div class="card-header">
                    <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 21H3"></path>
                        <path d="M21 21V10"></path>
                        <path d="M3 21V10"></path>
                        <path d="M7 21V14"></path>
                        <path d="M11 21V6"></path>
                        <path d="M15 21V10"></path>
                        <path d="M19 21V4"></path>
                    </svg>
                    <h3>利用状況</h3>
                </div>
                <div class="stats-grid" x-show="stats">
                    <div class="stat-item">
                        <span class="stat-value" x-text="stats?.requests || 0"></span>
                        <span class="stat-label">総依頼数</span>
                    </div>
                    <div class="stat-item highlight">
                        <span class="stat-value" x-text="stats?.activeMatchings || 0"></span>
                        <span class="stat-label">進行中</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" x-text="stats?.completedMatchings || 0"></span>
                        <span class="stat-label">完了</span>
                    </div>
                    <template x-if="stats?.pendingReports > 0">
                        <div class="stat-item alert">
                            <span class="stat-value" x-text="stats.pendingReports"></span>
                            <span class="stat-label">承認待ち</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- 利用時間カード -->
            <template x-if="usageStats">
                <div class="card usage-card">
                    <div class="card-header usage-card-header">
                        <div class="card-header-left">
                            <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <h3>利用時間</h3>
                        </div>
                        <div class="month-selector-header">
                            <select x-model="selectedMonth" @change="fetchMonthStats(selectedMonth)" class="month-select" aria-label="月を選択">
                                <template x-for="option in getMonthOptions()" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="usage-stats">
                        <template x-if="loadingMonthStats">
                            <div class="loading-text">
                                <div class="loading-spinner small"></div>
                            </div>
                        </template>
                        <template x-if="!loadingMonthStats">
                            <div class="usage-content">
                                <p class="usage-total">
                                    <span x-text="(selectedMonthStats || usageStats?.current_month)?.total_hours || 0"></span>
                                    <span class="usage-unit">時間</span>
                                </p>
                                <div class="usage-breakdown">
                                    <div class="usage-bar-item">
                                        <div class="usage-bar-header">
                                            <span class="usage-bar-label">
                                                <span class="usage-dot outing"></span>
                                                外出
                                            </span>
                                            <span class="usage-bar-value">
                                                <span x-text="((selectedMonthStats || usageStats?.current_month)?.by_type?.['外出'] || 0) + '時間'"></span>
                                            </span>
                                        </div>
                                        <div class="usage-bar">
                                            <div class="usage-bar-fill outing" :style="`width: ${Math.min(((selectedMonthStats || usageStats?.current_month)?.by_type?.['外出'] || 0) / Math.max((selectedMonthStats || usageStats?.current_month)?.total_hours || 1, 1) * 100, 100)}%`"></div>
                                        </div>
                                    </div>
                                    <div class="usage-bar-item">
                                        <div class="usage-bar-header">
                                            <span class="usage-bar-label">
                                                <span class="usage-dot home"></span>
                                                自宅
                                            </span>
                                            <span class="usage-bar-value">
                                                <span x-text="((selectedMonthStats || usageStats?.current_month)?.by_type?.['自宅'] || 0) + '時間'"></span>
                                            </span>
                                        </div>
                                        <div class="usage-bar">
                                            <div class="usage-bar-fill home" :style="`width: ${Math.min(((selectedMonthStats || usageStats?.current_month)?.by_type?.['自宅'] || 0) / Math.max((selectedMonthStats || usageStats?.current_month)?.total_hours || 1, 1) * 100, 100)}%`"></div>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="usageStats?.monthly && usageStats.monthly.length > 0">
                                    <div class="usage-table-wrapper" role="region" aria-label="利用時間テーブル">
                                        <table class="usage-table">
                                            <caption>月別利用時間</caption>
                                            <thead>
                                                <tr>
                                                    <th scope="col">月</th>
                                                    <th scope="col">合計時間</th>
                                                    <th scope="col">外出</th>
                                                    <th scope="col">自宅</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="m in usageStats.monthly" :key="m.month">
                                                    <tr>
                                                        <td x-text="m.month"></td>
                                                        <td x-text="(m.total_hours || Math.round((m.total_minutes || 0) / 60 * 10) / 10) + ' 時間'"></td>
                                                        <td x-text="(m.by_type?.['外出'] ?? '-') + (m.by_type?.['外出'] !== undefined ? ' 時間' : '')"></td>
                                                        <td x-text="(m.by_type?.['自宅'] ?? '-') + (m.by_type?.['自宅'] !== undefined ? ' 時間' : '')"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- 月次限度時間カード -->
            <div class="card limit-card">
                <div class="limit-card-header">
                    <div class="limit-card-header-left">
                        <svg class="limit-card-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <div class="limit-card-title-section">
                            <h3>月次限度時間</h3>
                            <template x-if="monthlyLimit">
                                <p class="limit-card-subtitle" x-text="`${monthlyLimit.year || new Date().getFullYear()}年${monthlyLimit.month || (new Date().getMonth() + 1)}月`"></p>
                            </template>
                        </div>
                    </div>
                    <button @click="fetchMonthlyLimit()" class="btn-icon-small" :aria-label="'限度時間を更新'">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </button>
                </div>
                <div class="limit-stats">
                    <template x-if="loadingMonthlyLimit">
                        <div class="loading-text">
                            <div class="loading-spinner small"></div>
                        </div>
                    </template>
                    <template x-if="!loadingMonthlyLimit && monthlyLimit">
                        <div class="limit-content">
                            <div class="limit-summary">
                                <div class="limit-summary-item remaining">
                                    <div class="limit-summary-value">
                                        <span class="limit-number" x-text="(monthlyLimit.remaining_hours || 0).toFixed(1)"></span>
                                        <span class="limit-unit">時間</span>
                                    </div>
                                    <span class="limit-summary-label">残り</span>
                                </div>
                                <div class="limit-summary-divider"></div>
                                <div class="limit-summary-item total">
                                    <div class="limit-summary-value">
                                        <span class="limit-number" x-text="(monthlyLimit.limit_hours || 0).toFixed(1)"></span>
                                        <span class="limit-unit">時間</span>
                                    </div>
                                    <span class="limit-summary-label">限度時間</span>
                                </div>
                            </div>
                            <div class="limit-progress-section">
                                <div class="limit-progress-header">
                                    <span class="limit-progress-label">使用状況</span>
                                    <span class="limit-progress-percentage" 
                                          x-text="`${Math.round(((monthlyLimit.used_hours || 0) / Math.max(monthlyLimit.limit_hours || 1, 1)) * 100)}%`">
                                    </span>
                                </div>
                                <div class="limit-progress-bar">
                                    <div class="limit-progress-fill" 
                                         :class="{
                                           'progress-safe': ((monthlyLimit.used_hours || 0) / Math.max(monthlyLimit.limit_hours || 1, 1)) < 0.7,
                                           'progress-warning': ((monthlyLimit.used_hours || 0) / Math.max(monthlyLimit.limit_hours || 1, 1)) >= 0.7 && ((monthlyLimit.used_hours || 0) / Math.max(monthlyLimit.limit_hours || 1, 1)) < 0.9,
                                           'progress-danger': ((monthlyLimit.used_hours || 0) / Math.max(monthlyLimit.limit_hours || 1, 1)) >= 0.9
                                         }"
                                         :style="`width: ${Math.min(((monthlyLimit.used_hours || 0) / Math.max(monthlyLimit.limit_hours || 1, 1)) * 100, 100)}%`">
                                    </div>
                                </div>
                                <div class="limit-progress-details">
                                    <span class="limit-progress-used">使用: <strong x-text="(monthlyLimit.used_hours || 0).toFixed(1) + '時間'"></strong></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!loadingMonthlyLimit && !monthlyLimit">
                        <div class="limit-content">
                            <p class="limit-empty">限度時間の情報が取得できませんでした</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

            <!-- 予定（マッチング済み）一覧 -->
            <template x-if="matchings.length > 0">
                <section class="matchings-section" aria-label="今後確定している予定">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            今後確定している予定
                        </h2>
                    </div>
                <div class="matchings-table-wrapper" role="region" aria-label="今後確定している予定の一覧">
                    <table class="matchings-table">
                        <caption class="sr-only">今後確定している予定</caption>
                        <thead>
                            <tr>
                                <th scope="col">状態</th>
                                <th scope="col">依頼種別</th>
                                <th scope="col">ガイド</th>
                                <th scope="col">日時</th>
                                <th scope="col">場所</th>
                                <th scope="col">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="matching in matchings" :key="matching.id">
                                <tr>
                                    <td>
                                        <span class="status-badge" :class="getStatusBadgeClass(matching.status)" x-text="getStatusLabel(matching.status)"></span>
                                    </td>
                                    <td x-text="matching.request_type"></td>
                                    <td x-text="matching.guide_name"></td>
                                    <td x-text="formatRequestDateTime(matching.request_date, matching.request_time)"></td>
                                    <td x-text="matching.masked_address"></td>
                                    <td class="table-actions">
                                        <div class="action-buttons">
                                            <a :href="`{{ url('/chat') }}/${matching.id}`" class="action-btn action-btn-chat" :aria-label="`チャット: ${matching.guide_name}`" :title="`${matching.guide_name}さんとチャット`">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                                </svg>
                                            </a>
                                            <a :href="`{{ url('/matchings') }}/${matching.id}`" class="action-btn action-btn-detail" :aria-label="`詳細: ${matching.guide_name}`" title="詳細を見る">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>

            <!-- マッチングがない場合 -->
            <template x-if="matchings.length === 0">
                <section class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <h3>現在進行中のマッチングはありません</h3>
                    <p>新しい依頼を作成して、ガイドとマッチングしましょう</p>
                    <a href="{{ route('requests.create') }}" class="btn-primary">
                        依頼を作成する
                    </a>
                </section>
            </template>
        @endif

        @if($user->isGuide())
            <!-- ガイド向けダッシュボード -->
            <section class="quick-actions-section">
                <h2 class="section-title">クイックアクション</h2>
                <div class="quick-actions">
            <a href="{{ route('guide.requests.index') }}" class="quick-action-btn secondary">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span>依頼一覧</span>
                <template x-if="stats?.availableRequests > 0">
                    <span class="action-badge" x-text="stats.availableRequests"></span>
                </template>
            </a>
            <template x-if="activeMatchings.length > 0">
                <a :href="`{{ url('/guide/reports/new') }}/${activeMatchings[0].id}`" class="quick-action-btn primary">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="11" x2="12" y2="17"></line>
                        <line x1="9" y1="14" x2="15" y2="14"></line>
                    </svg>
                    <span>報告書を作成</span>
                </a>
                </template>
            </div>
        </section>

            <!-- 修正依頼が来た報告書 -->
            <template x-if="revisionRequestedReports.length > 0">
            <section class="pending-reports-section">
                <div class="section-header">
                    <h2>
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        修正依頼が来た報告書
                    </h2>
                    <span class="pending-count" x-text="revisionRequestedReports.length + '件'"></span>
                </div>
                <div class="pending-reports-list">
                    <template x-for="report in revisionRequestedReports.slice(0, 5)" :key="report.id">
                        <a :href="`{{ url('/guide/reports/new') }}/${report.matching_id}`" class="pending-report-item revision-requested">
                            <div class="report-info">
                                <span class="report-type" x-text="report.request_type"></span>
                                <span class="report-date" x-text="formatDate(report.actual_date)"></span>
                                <span class="report-status-badge revision-badge">修正待ち</span>
                            </div>
                            <div class="report-right-info">
                                <span class="report-user">ユーザー: <span x-text="report.user_name"></span></span>
                                <template x-if="report.revision_notes">
                                    <span class="revision-notes-preview" x-text="report.revision_notes.length > 50 ? report.revision_notes.substring(0, 50) + '...' : report.revision_notes"></span>
                                </template>
                            </div>
                        </a>
                    </template>
                </div>
            </section>
            </template>

            <!-- 統計カード -->
            <div class="dashboard-cards" x-show="stats">
            <div class="card stats-card">
                <div class="card-header">
                    <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 21H3"></path>
                        <path d="M21 21V10"></path>
                        <path d="M3 21V10"></path>
                        <path d="M7 21V14"></path>
                        <path d="M11 21V6"></path>
                        <path d="M15 21V10"></path>
                        <path d="M19 21V4"></path>
                    </svg>
                    <h3>利用状況</h3>
                </div>
                <div class="stats-grid" x-show="stats">
                    <div class="stat-item highlight">
                        <span class="stat-value" x-text="stats?.availableRequests || 0"></span>
                        <span class="stat-label">新規依頼</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" x-text="stats?.activeMatchings || 0"></span>
                        <span class="stat-label">進行中</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" x-text="stats?.completedMatchings || 0"></span>
                        <span class="stat-label">完了</span>
                    </div>
                    <template x-if="stats?.pendingReports > 0">
                        <div class="stat-item alert">
                            <span class="stat-value" x-text="stats.pendingReports"></span>
                            <span class="stat-label">要報告書</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ガイド時間カード -->
            <template x-if="usageStats">
                <div class="card usage-card">
                    <div class="card-header">
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <h3>ガイド時間</h3>
                    </div>
                    <div class="usage-stats">
                        <div class="usage-content">
                            <p class="usage-total">
                                <span x-text="usageStats?.current_month?.total_hours || 0"></span>
                                <span class="usage-unit">時間</span>
                            </p>
                            <div class="usage-breakdown">
                                <div class="usage-bar-item">
                                    <div class="usage-bar-header">
                                        <span class="usage-bar-label">
                                            <span class="usage-dot outing"></span>
                                            外出
                                        </span>
                                        <span class="usage-bar-value">
                                            <span x-text="(usageStats?.current_month?.by_type?.['外出'] || 0) + '時間'"></span>
                                        </span>
                                    </div>
                                    <div class="usage-bar">
                                        <div class="usage-bar-fill outing" :style="`width: ${Math.min((usageStats?.current_month?.by_type?.['外出'] || 0) / Math.max(usageStats?.current_month?.total_hours || 1, 1) * 100, 100)}%`"></div>
                                    </div>
                                </div>
                                <div class="usage-bar-item">
                                    <div class="usage-bar-header">
                                        <span class="usage-bar-label">
                                            <span class="usage-dot home"></span>
                                            自宅
                                        </span>
                                        <span class="usage-bar-value">
                                            <span x-text="(usageStats?.current_month?.by_type?.['自宅'] || 0) + '時間'"></span>
                                        </span>
                                    </div>
                                    <div class="usage-bar">
                                        <div class="usage-bar-fill home" :style="`width: ${Math.min((usageStats?.current_month?.by_type?.['自宅'] || 0) / Math.max(usageStats?.current_month?.total_hours || 1, 1) * 100, 100)}%`"></div>
                                    </div>
                                </div>
                            </div>
                            <template x-if="usageStats?.monthly && usageStats.monthly.length > 0">
                                <div class="usage-table-wrapper" role="region" aria-label="ガイド時間テーブル">
                                    <table class="usage-table">
                                        <caption>月別ガイド時間</caption>
                                        <thead>
                                            <tr>
                                                <th scope="col">月</th>
                                                <th scope="col">合計時間</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="m in usageStats.monthly" :key="m.month">
                                                <tr>
                                                    <td x-text="m.month"></td>
                                                    <td x-text="(m.total_hours || Math.round((m.total_minutes || 0) / 60 * 10) / 10) + ' 時間'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
            </div>

            <!-- 進行中のマッチング一覧 -->
            <template x-if="activeMatchings.length > 0">
                <section class="matchings-section" aria-label="マッチング一覧">
                    <div class="section-header">
                        <h2>
                            <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            進行中のマッチング
                        </h2>
                    </div>
                <div class="matchings-list">
                    <template x-for="matching in activeMatchings" :key="matching.id">
                        <div class="matching-card">
                            <div class="matching-header">
                                <span class="status-badge" :class="getStatusBadgeClass(matching.status)" x-text="getStatusLabel(matching.status)"></span>
                                <span class="matching-type" x-text="matching.request_type"></span>
                            </div>
                            <div class="matching-info">
                                <h3 x-text="matching.masked_address"></h3>
                                <div class="matching-details">
                                    <p>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        ユーザー: <span x-text="matching.user_name"></span>
                                    </p>
                                    <p>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span x-text="formatRequestDateTime(matching.request_date, matching.request_time)"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="matching-actions">
                                <a :href="`{{ url('/chat') }}/${matching.id}`" class="btn-primary btn-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    <span>チャット</span>
                                </a>
                                <a :href="`{{ url('/matchings') }}/${matching.id}`" class="btn-secondary btn-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <span>詳細</span>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
            </template>

            <!-- マッチングがない場合 -->
            <template x-if="activeMatchings.length === 0">
                <section class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    <h3>現在進行中のマッチングはありません</h3>
                    <p>新しい依頼を確認して、ガイドを始めましょう</p>
                    <a href="{{ route('guide.requests.index') }}" class="btn-primary">
                        依頼を探す
                    </a>
                </section>
            </template>
        @endif

        @if($user->isAdmin())
            <!-- 管理者向けダッシュボード -->
            <section class="quick-actions-section">
                <h2 class="section-title">管理機能</h2>
                <div class="quick-actions">
                    <a href="{{ route('admin.dashboard') }}" class="quick-action-btn primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        <span>管理画面へ</span>
                    </a>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Dashboard.css') }}">
@endpush

@push('scripts')
<script>
function dashboardData() {
    return {
        greeting: '',
        notifications: @json($notifications ?? []),
        announcements: @json($announcements ?? []),
        stats: @json($stats ?? null),
        matchings: @json($matchings ?? []),
        pendingReports: @json($pendingReports ?? []),
        revisionRequestedReports: @json($revisionRequestedReports ?? []),
        usageStats: @json($usageStats ?? null),
        monthlyLimit: null,
        loadingMonthlyLimit: false,
        get activeMatchings() {
            return this.matchings.filter(m => 
                (m.status === 'matched' || m.status === 'in_progress') && 
                !m.report_completed_at
            );
        },
        init() {
            const hour = new Date().getHours();
            if (hour < 12) this.greeting = 'おはようございます';
            else if (hour < 18) this.greeting = 'こんにちは';
            else this.greeting = 'こんばんは';
            // 月次限度時間を取得
            this.fetchMonthlyLimit();
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('ja-JP', { month: 'long', day: 'numeric', weekday: 'short' });
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
        getPendingReportsTitle() {
            const hasRevisionRequested = this.pendingReports.some(r => r.status === 'revision_requested');
            const hasSubmitted = this.pendingReports.some(r => r.status === 'submitted');
            if (hasRevisionRequested && hasSubmitted) {
                return '承認待ち・修正待ちの報告書';
            } else if (hasRevisionRequested) {
                return '修正待ちの報告書';
            } else {
                return '承認待ちの報告書';
            }
        },
        getStatusBadgeClass(status) {
            const statusMap = {
                'matched': 'badge-info',
                'in_progress': 'badge-warning',
                'completed': 'badge-success',
                'cancelled': 'badge-error'
            };
            return statusMap[status] || 'badge-default';
        },
        getStatusLabel(status) {
            const statusMap = {
                'matched': '今後確定している予定',
                'in_progress': '進行中',
                'completed': '完了',
                'cancelled': 'キャンセル'
            };
            return statusMap[status] || status;
        },
        selectedMonth: (() => {
            const now = new Date();
            return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        })(),
        selectedMonthStats: null,
        loadingMonthStats: false,
        getMonthOptions() {
            const options = [];
            const now = new Date();
            for (let i = 0; i < 12; i++) {
                const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                const label = `${date.getFullYear()}年${date.getMonth() + 1}月`;
                options.push({ value, label });
            }
            return options;
        },
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
        async fetchMonthStats(monthString) {
            if (!monthString) return;
            this.loadingMonthStats = true;
            try {
                const [year, month] = monthString.split('-');
                const data = await this.apiFetch(`/api/reports/usage-stats?year=${year}&month=${month}`);
                this.selectedMonthStats = data.current_month;
            } catch (error) {
                if (error.message !== '認証エラー') {
                    console.error('月別統計取得エラー:', error);
                }
                this.selectedMonthStats = null;
            } finally {
                this.loadingMonthStats = false;
            }
        },
        async fetchMonthlyLimit() {
            this.loadingMonthlyLimit = true;
            try {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth() + 1;
                
                const data = await this.apiFetch(`/api/users/me/monthly-limit?year=${year}&month=${month}`);
                console.log('限度時間データ取得成功:', data);
                this.monthlyLimit = data.limit || null;
            } catch (error) {
                if (error.message !== '認証エラー') {
                    console.error('限度時間取得エラー:', error);
                }
                this.monthlyLimit = null;
            } finally {
                this.loadingMonthlyLimit = false;
            }
        }
    }
}
</script>
@endpush

