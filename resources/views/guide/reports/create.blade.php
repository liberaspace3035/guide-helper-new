@extends('layouts.app')

@section('content')
<div class="report-form-container" x-data="reportForm()" x-init="init()">
    <h1 x-text="existingReport ? '報告書編集' : '報告書作成'"></h1>
    <div x-show="error" class="error-message" role="alert" x-text="error"></div>
    @if($errors->any())
        <div class="error-message" role="alert">
            {{ $errors->first() }}
        </div>
    @endif
    <!-- 修正依頼内容の表示 -->
    <template x-if="existingReport && existingReport.status === 'revision_requested' && existingReport.revision_notes">
        <div class="revision-request-alert" role="alert">
            <div class="revision-request-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h3>修正依頼があります</h3>
            </div>
            <div class="revision-request-content">
                <p class="revision-request-label">修正依頼内容:</p>
                <p class="revision-request-notes" x-text="existingReport.revision_notes"></p>
            </div>
        </div>
    </template>
    <form class="report-form" aria-label="報告書フォーム" @submit.prevent="handleSave">
        <input type="hidden" name="matching_id" :value="matchingId">
        
        <div class="form-group">
            <label for="service_content">サービス内容 <span class="required">*</span></label>
            <textarea
                id="service_content"
                name="service_content"
                x-model="formData.service_content"
                rows="6"
                placeholder="実施したサービス内容を記入してください"
                required
                aria-required="true"
            ></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="actual_date">実施日</label>
                <input
                    type="date"
                    id="actual_date"
                    name="actual_date"
                    x-model="formData.actual_date"
                    :readonly="true"
                    disabled
                    style="background-color: #f5f5f5; cursor: not-allowed;"
                    aria-label="実施日（変更不可）"
                />
                <small class="form-text" style="color: var(--text-secondary); margin-top: 4px; display: block;">
                    実施日は依頼日に基づいて自動設定され、変更できません
                </small>
            </div>
            <div class="form-group">
                <label for="actual_start_time">開始時刻 <span class="required">*</span></label>
                <input
                    type="time"
                    id="actual_start_time"
                    name="actual_start_time"
                    x-model="formData.actual_start_time"
                    required
                    aria-required="true"
                />
            </div>
            <div class="form-group">
                <label for="actual_end_time">終了時刻 <span class="required">*</span></label>
                <input
                    type="time"
                    id="actual_end_time"
                    name="actual_end_time"
                    x-model="formData.actual_end_time"
                    required
                    aria-required="true"
                />
            </div>
        </div>

        <div class="form-group">
            <label for="report_content">報告欄（自由記入）</label>
            <textarea
                id="report_content"
                name="report_content"
                x-model="formData.report_content"
                rows="8"
                placeholder="実施内容の詳細、気づいた点、改善点などを自由に記入してください"
            ></textarea>
        </div>

        <div class="form-actions">
            <button
                type="button"
                @click="handleSave"
                class="btn-secondary"
                :disabled="saving"
                aria-label="下書き保存"
            >
                <span x-show="!saving">下書き保存</span>
                <span x-show="saving">保存中...</span>
            </button>
            <button
                type="button"
                @click="handleSubmit"
                class="btn-primary"
                :disabled="saving"
                aria-label="報告書を提出"
            >
                <span x-show="!saving">報告書を提出</span>
                <span x-show="saving">提出中...</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/ReportForm.css') }}">
@endpush

@push('scripts')
<script>
function reportForm() {
    return {
        matchingId: {{ $matchingId }},
        existingReport: @json($existingReport),
        formData: {
            service_content: '',
            report_content: '',
            actual_date: '',
            actual_start_time: '',
            actual_end_time: ''
        },
        saving: false,
        error: '',
        init() {
            if (this.existingReport) {
                // 既存の報告書の内容をフォームに反映
                this.formData = {
                    service_content: this.existingReport.service_content || '',
                    report_content: this.existingReport.report_content || '',
                    actual_date: this.existingReport.actual_date_formatted || this.existingReport.actual_date || '',
                    actual_start_time: this.existingReport.actual_start_time_formatted || (this.existingReport.actual_start_time ? (typeof this.existingReport.actual_start_time === 'string' ? this.existingReport.actual_start_time.substring(0, 5) : '') : ''),
                    actual_end_time: this.existingReport.actual_end_time_formatted || (this.existingReport.actual_end_time ? (typeof this.existingReport.actual_end_time === 'string' ? this.existingReport.actual_end_time.substring(0, 5) : '') : '')
                };
            } else {
                // マッチング情報から初期値を設定
                const matching = @json($matching);
                if (matching && matching.request_date) {
                    this.formData.actual_date = matching.request_date;
                }
            }
        },
        async handleSave() {
            this.saving = true;
            this.error = '';
            
            // 必須項目のバリデーション（自由入力の欄以外）
            if (!this.formData.service_content || this.formData.service_content.trim() === '') {
                this.error = 'サービス内容は必須入力です。';
                this.saving = false;
                return;
            }
            
            if (!this.formData.actual_start_time) {
                this.error = '開始時刻は必須入力です。';
                this.saving = false;
                return;
            }
            
            if (!this.formData.actual_end_time) {
                this.error = '終了時刻は必須入力です。';
                this.saving = false;
                return;
            }
            
            // 開始時刻と終了時刻の検証
            if (this.formData.actual_start_time >= this.formData.actual_end_time) {
                this.error = '終了時刻は開始時刻より後である必要があります。';
                this.saving = false;
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('matching_id', this.matchingId);
                formData.append('service_content', this.formData.service_content);
                formData.append('report_content', this.formData.report_content);
                formData.append('actual_date', this.formData.actual_date);
                formData.append('actual_start_time', this.formData.actual_start_time);
                formData.append('actual_end_time', this.formData.actual_end_time);
                formData.append('_token', '{{ csrf_token() }}');

                const response = await fetch('{{ route("guide.reports.store") }}', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin', // セッションクッキーを送信（必須）
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    // 新規作成の場合、作成された報告書のIDを保存
                    if (data.report_id && !this.existingReport) {
                        this.existingReport = { id: data.report_id };
                    }
                    // 下書き保存の場合はリダイレクトしない（提出時に使用するため）
                    if (!data.auto_submit) {
                        return data;
                    }
                    alert('報告書が保存されました');
                    window.location.href = '{{ route("dashboard") }}';
                } else {
                    // エラーレスポンスの詳細を確認
                    let errorMessage = '報告書の保存に失敗しました';
                    try {
                        const errorData = await response.json();
                        
                        // Laravelのバリデーションエラー（422）の処理
                        if (response.status === 422 && errorData.errors) {
                            // バリデーションエラーメッセージを構築
                            const errorMessages = [];
                            for (const field in errorData.errors) {
                                if (errorData.errors[field] && Array.isArray(errorData.errors[field])) {
                                    errorMessages.push(...errorData.errors[field]);
                                }
                            }
                            errorMessage = errorMessages.length > 0 
                                ? errorMessages.join('\n') 
                                : '必須項目が不足しています。入力内容を確認してください。';
                        } else if (errorData.message) {
                            errorMessage = errorData.message;
                        } else if (errorData.error) {
                            errorMessage = errorData.error;
                        }
                        console.error('保存エラー:', errorData);
                    } catch (e) {
                        console.error('レスポンス解析エラー:', e);
                        errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                    }
                    this.error = errorMessage;
                    throw new Error(errorMessage);
                }
            } catch (err) {
                this.error = err.message || '報告書の保存に失敗しました';
                throw err; // 提出時にエラーを伝播するため
            } finally {
                this.saving = false;
            }
        },
        async handleSubmit() {
            this.saving = true;
            this.error = '';
            
            // 提出前に必須項目のバリデーション（自由入力の欄以外）
            if (!this.formData.service_content || this.formData.service_content.trim() === '') {
                this.error = '報告書を提出するには、サービス内容を入力してください。';
                this.saving = false;
                return;
            }
            
            if (!this.formData.actual_start_time) {
                this.error = '報告書を提出するには、開始時刻を入力してください。';
                this.saving = false;
                return;
            }
            
            if (!this.formData.actual_end_time) {
                this.error = '報告書を提出するには、終了時刻を入力してください。';
                this.saving = false;
                return;
            }
            
            // 開始時刻と終了時刻の検証
            if (this.formData.actual_start_time >= this.formData.actual_end_time) {
                this.error = '終了時刻は開始時刻より後である必要があります。';
                this.saving = false;
                return;
            }

            if (!confirm('報告書を提出しますか？提出後はユーザーの承認が必要です。')) {
                this.saving = false;
                return;
            }

            try {
                // まず保存（新規作成の場合は報告書IDを取得）
                const saveResult = await this.handleSave();
                
                // 報告書IDを取得（既存の場合は既存ID、新規の場合は保存結果から取得）
                let reportId;
                if (this.existingReport && this.existingReport.id) {
                    reportId = this.existingReport.id;
                } else if (saveResult && saveResult.report_id) {
                    reportId = saveResult.report_id;
                } else {
                    throw new Error('報告書IDが取得できませんでした。保存に失敗した可能性があります。');
                }
                
                // その後提出
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'POST');

                const response = await fetch(`/guide/reports/${reportId}/submit`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin', // セッションクッキーを送信（必須）
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    alert('報告書が提出されました');
                    window.location.href = '{{ route("dashboard") }}';
                } else {
                    // エラーレスポンスの詳細を確認
                    let errorMessage = '報告書の提出に失敗しました';
                    try {
                        const errorData = await response.json();
                        
                        // バリデーションエラーやその他のエラーの処理
                        if (response.status === 422 && errorData.errors) {
                            // バリデーションエラーメッセージを構築
                            const errorMessages = [];
                            for (const field in errorData.errors) {
                                if (errorData.errors[field] && Array.isArray(errorData.errors[field])) {
                                    errorMessages.push(...errorData.errors[field]);
                                }
                            }
                            errorMessage = errorMessages.length > 0 
                                ? errorMessages.join('\n') 
                                : '必須項目が不足しています。入力内容を確認してください。';
                        } else if (errorData.message) {
                            errorMessage = errorData.message;
                        } else if (errorData.error) {
                            errorMessage = errorData.error;
                        }
                        console.error('提出エラー:', errorData);
                    } catch (e) {
                        console.error('レスポンス解析エラー:', e);
                        errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                    }
                    this.error = errorMessage;
                }
            } catch (err) {
                // handleSave()からエラーが伝播した場合の処理
                const errorMsg = err.message || '報告書の提出に失敗しました';
                
                // 必須項目不足の場合は、より具体的なメッセージを表示
                if (errorMsg.includes('必須') || errorMsg.includes('required') || errorMsg.includes('不足')) {
                    this.error = errorMsg + ' 報告書を提出するには、全ての必須項目を入力してください。';
                } else {
                    this.error = errorMsg;
                }
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush



