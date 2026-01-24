@extends('layouts.app')

@section('content')
<div class="request-form-container" x-data="requestForm()" x-init="init()">
    <h1>依頼作成</h1>
    <form method="POST" action="{{ route('requests.store') }}" @submit.prevent="handleSubmit($event)" x-ref="requestForm" class="request-form" aria-label="依頼作成フォーム">
        @csrf
        <div x-show="error" class="error-message full-width" role="alert" aria-live="polite" x-text="error"></div>
        @if($errors->any())
            <div class="error-message full-width" role="alert" aria-live="polite">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="form-group">
            <label for="request_type">依頼タイプ <span class="required">*</span></label>
            <select
                id="request_type"
                name="request_type"
                x-model="formData.request_type"
                required
                aria-required="true"
            >
                <option value="outing">外出</option>
                <option value="home">自宅</option>
            </select>
        </div>

        <div class="form-group">
            <label for="nominated_guide_id">指名ガイド（任意）</label>
            <select
                id="nominated_guide_id"
                name="nominated_guide_id"
                x-model="formData.nominated_guide_id"
            >
                <option value="">指名しない</option>
                <template x-for="guide in availableGuides" :key="guide.id">
                    <option :value="guide.id" x-text="guide.name + (guide.introduction ? ' - ' + guide.introduction.substring(0, 30) + '...' : '')"></option>
                </template>
            </select>
            <small>特定のガイドを指名して依頼を投稿できます。指名した場合、そのガイドに優先的に通知されます。</small>
        </div>

        <template x-if="formData.request_type === 'outing'">
            <div>
                <div class="form-group">
                    <label for="destination_address">目的地 <span class="required">*</span></label>
                    <input
                        type="text"
                        id="destination_address"
                        name="destination_address"
                        x-model="formData.destination_address"
                        required
                        placeholder="例: 東京都渋谷区青山１－１－１"
                        aria-required="true"
                    />
                    <small>詳細な住所を入力してください（ガイドには大まかな地域のみ表示されます）</small>
                </div>
                <div class="form-group">
                    <label for="meeting_place">待ち合わせ場所 <span class="required">*</span></label>
                    <input
                        type="text"
                        id="meeting_place"
                        name="meeting_place"
                        x-model="formData.meeting_place"
                        required
                        placeholder="例: 渋谷駅ハチ公前"
                        aria-required="true"
                    />
                    <small>ガイドとの待ち合わせ場所を入力してください</small>
                </div>
            </div>
        </template>

        <template x-if="formData.request_type === 'home'">
            <div>
                <div class="form-group">
                    <label for="destination_address">場所 <span class="required">*</span></label>
                    <input
                        type="text"
                        id="destination_address"
                        name="destination_address"
                        x-model="formData.destination_address"
                        required
                        placeholder="例: 東京都渋谷区青山１－１－１"
                        aria-required="true"
                    />
                    <small>詳細な住所を入力してください（ガイドには大まかな地域のみ表示されます）</small>
                </div>
                <div class="form-group">
                    <label for="meeting_place">集合場所 <span class="required">*</span></label>
                    <input
                        type="text"
                        id="meeting_place"
                        name="meeting_place"
                        x-model="formData.meeting_place"
                        required
                        placeholder="例: 玄関前"
                        aria-required="true"
                    />
                    <small>ガイドとの集合場所を入力してください</small>
                </div>
            </div>
        </template>

        <div class="form-group full-width">
            <label for="service_content">サービス内容 <span class="required">*</span></label>
            <div class="textarea-with-voice">
                <textarea
                    id="service_content"
                    name="service_content"
                    x-model="formData.service_content"
                    required
                    rows="4"
                    placeholder="『買い物』『代筆』など、具体的なサービス内容を記載してください"
                    aria-required="true"
                ></textarea>
                <button
                    type="button"
                    class="voice-input-btn"
                    :class="{ 'recording': isRecording }"
                    @click="toggleVoiceInput"
                    :disabled="!isVoiceInputSupported"
                    :title="isRecording ? '音声入力を停止' : '音声入力'"
                    aria-label="音声入力"
                >
                    <template x-if="!isRecording">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                            <line x1="12" y1="19" x2="12" y2="23"></line>
                            <line x1="8" y1="23" x2="16" y2="23"></line>
                        </svg>
                    </template>
                    <template x-if="isRecording">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="6" y="6" width="12" height="12" rx="2"></rect>
                        </svg>
                    </template>
                </button>
            </div>
            <template x-if="isRecording">
                <div class="voice-recording-indicator">
                    <span class="recording-dot"></span>
                    <span>音声認識中...</span>
                </div>
            </template>
        </div>

        <div class="form-group full-width">
            <h3 class="section-subtitle">希望するガイドについて <span class="required">*</span></h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="guide_gender">希望するガイドの性別 <span class="required">*</span></label>
                    <select
                        id="guide_gender"
                        name="guide_gender"
                        x-model="formData.guide_gender"
                        required
                        aria-required="true"
                    >
                        <option value="none">選択しない（どの性別でも構わない）</option>
                        <option value="male">男性</option>
                        <option value="female">女性</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guide_age">希望するガイドの年代 <span class="required">*</span></label>
                    <select
                        id="guide_age"
                        name="guide_age"
                        x-model="formData.guide_age"
                        required
                        aria-required="true"
                    >
                        <option value="none">選択しない（どの年代でも構わない）</option>
                        <option value="20s">20代</option>
                        <option value="30s">30代</option>
                        <option value="40s">40代</option>
                        <option value="50s">50代</option>
                        <option value="60s">60代</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="request_date">希望日 <span class="required">*</span></label>
                <input
                    type="date"
                    id="request_date"
                    name="request_date"
                    x-model="formData.request_date"
                    required
                    :min="new Date().toISOString().split('T')[0]"
                    aria-required="true"
                />
            </div>

            <div class="form-group">
                <label for="start_time">希望時間 <span class="required">*</span></label>
                <div class="time-input-group">
                    <input
                        type="time"
                        id="start_time"
                        name="start_time"
                        x-model="formData.start_time"
                        required
                        placeholder="hh:mm"
                        aria-required="true"
                    />
                    <span class="time-separator">～</span>
                    <input
                        type="time"
                        id="end_time"
                        name="end_time"
                        x-model="formData.end_time"
                        required
                        placeholder="hh:mm"
                        aria-required="true"
                    />
                </div>
                <small>「hh:mm」形式で入力してください（例: 14:00～16:00）</small>
            </div>
        </div>

        <div class="form-actions full-width">
            <button
                type="submit"
                class="btn-primary"
                :disabled="loading"
                aria-label="依頼を送信"
            >
                <span x-show="!loading">依頼を送信</span>
                <span x-show="loading">送信中...</span>
            </button>
            <a href="{{ route('requests.index') }}" class="btn-secondary" aria-label="キャンセル">
                キャンセル
            </a>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/RequestForm.css') }}">
@endpush

@push('scripts')
<script>
function requestForm() {
    const getDefaultDateTime = () => {
        const now = new Date();
        const toHM = (d) => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
        const start = new Date(now.getTime() + 2 * 60 * 60 * 1000);
        const end = new Date(start.getTime() + 60 * 60 * 1000);
        return {
            request_date: now.toISOString().split('T')[0],
            start_time: toHM(start),
            end_time: toHM(end)
        };
    };

    const defaultDateTime = getDefaultDateTime();

    return {
        formData: {
            request_type: 'outing',
            destination_address: '',
            meeting_place: '',
            service_content: '',
            request_date: defaultDateTime.request_date,
            start_time: defaultDateTime.start_time,
            end_time: defaultDateTime.end_time,
            guide_gender: 'none',
            guide_age: 'none',
            nominated_guide_id: ''
        },
        error: '',
        loading: false,
        isVoiceInput: false,
        isRecording: false,
        isVoiceInputSupported: false,
        recognition: null,
        availableGuides: [],
        guidesLoading: false,
        async init() {
            // 音声認識のサポート確認
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                this.isVoiceInputSupported = true;
                this.initSpeechRecognition();
            }
            
            // ガイド一覧を取得
            await this.fetchAvailableGuides();
        },
        async fetchAvailableGuides() {
            this.guidesLoading = true;
            try {
                const token = localStorage.getItem('token');
                const response = await fetch('/api/guides/available', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.availableGuides = data.guides || [];
                } else {
                    console.error('ガイド一覧取得エラー:', response.statusText);
                }
            } catch (error) {
                console.error('ガイド一覧取得エラー:', error);
            } finally {
                this.guidesLoading = false;
            }
        },
        initSpeechRecognition() {
            if (!this.isVoiceInputSupported) return;
            
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'ja-JP';
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            
            this.recognition.onresult = (event) => {
                let interimTranscript = '';
                let finalTranscript = '';
                
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        finalTranscript += transcript;
                    } else {
                        interimTranscript += transcript;
                    }
                }
                
                // サービス内容に音声認識結果を追加
                this.formData.service_content += finalTranscript + (interimTranscript ? ' ' + interimTranscript : '');
            };
            
            this.recognition.onerror = (event) => {
                console.error('音声認識エラー:', event.error);
                this.isRecording = false;
                if (event.error === 'no-speech') {
                    alert('音声が検出されませんでした。もう一度お試しください。');
                } else if (event.error === 'not-allowed') {
                    alert('マイクの使用が許可されていません。ブラウザの設定を確認してください。');
                }
            };
            
            this.recognition.onend = () => {
                this.isRecording = false;
            };
        },
        toggleVoiceInput() {
            if (!this.isVoiceInputSupported) {
                alert('お使いのブラウザは音声認識に対応していません');
                return;
            }
            
            if (this.isRecording) {
                this.stopRecording();
            } else {
                this.startRecording();
            }
        },
        startRecording() {
            if (this.recognition) {
                try {
                    this.recognition.start();
                    this.isRecording = true;
                    this.isVoiceInput = true;
                } catch (error) {
                    console.error('音声認識開始エラー:', error);
                    alert('音声認識を開始できませんでした。もう一度お試しください。');
                }
            } else {
                this.initSpeechRecognition();
                if (this.recognition) {
                    try {
                        this.recognition.start();
                        this.isRecording = true;
                        this.isVoiceInput = true;
                    } catch (error) {
                        console.error('音声認識開始エラー:', error);
                        alert('音声認識を開始できませんでした。もう一度お試しください。');
                    }
                }
            }
        },
        stopRecording() {
            if (this.recognition && this.isRecording) {
                this.recognition.stop();
                this.isRecording = false;
            }
        },
        handleSubmit(event) {
            this.error = '';
            
            // バリデーション1: ガイドの性別・年代が選択されているか
            if (this.formData.guide_gender === 'none' || this.formData.guide_age === 'none') {
                this.error = '希望するガイドの性別と年代を選択してください';
                return;
            }

            // バリデーション2: 開始時刻 < 終了時刻か（日付を考慮）
            if (this.formData.start_time && this.formData.end_time) {
                // 時刻を分単位に変換する関数
                const timeToMinutes = (timeStr) => {
                    if (!timeStr || !timeStr.includes(':')) return 0;
                    const [hours, minutes] = timeStr.split(':').map(Number);
                    if (isNaN(hours) || isNaN(minutes)) return 0;
                    return hours * 60 + minutes;
                };
                
                const startMinutes = timeToMinutes(this.formData.start_time);
                let endMinutes = timeToMinutes(this.formData.end_time);
                
                // 開始時刻または終了時刻が無効な場合
                if (startMinutes === 0 && this.formData.start_time !== '00:00') {
                    this.error = '開始時刻が無効です';
                    return;
                }
                if (endMinutes === 0 && this.formData.end_time !== '00:00') {
                    this.error = '終了時刻が無効です';
                    return;
                }
                
                // 終了時刻が開始時刻より小さい場合、翌日とみなす（24時間を加算）
                // 例: 23:55 → 1:05 の場合、1:05は翌日の1:05として扱う
                if (endMinutes < startMinutes) {
                    endMinutes += 24 * 60; // 24時間 = 1440分を加算
                } else if (endMinutes === startMinutes) {
                    // 開始時刻と終了時刻が同じ場合はエラー
                    this.error = '終了時刻は開始時刻より後である必要があります';
                    return;
                }
                
                // 実際の時間差を計算
                const durationMinutes = endMinutes - startMinutes;
                
                // 24時間（1440分）を超える場合はエラー
                if (durationMinutes > 24 * 60) {
                    this.error = '依頼時間は24時間以内である必要があります';
                    return;
                }
                
                // 時間差が0以下の場合はエラー（念のため）
                if (durationMinutes <= 0) {
                    this.error = '終了時刻は開始時刻より後である必要があります';
                    return;
                }
            }

            // バリデーション3: 外出タイプの場合、待ち合わせ場所が入力されているか
            if (this.formData.request_type === 'outing' && !this.formData.meeting_place) {
                this.error = '待ち合わせ場所を入力してください';
                return;
            }

            // バリデーションを通過したら送信
            this.loading = true;
            
            // フォーム要素を取得して送信
            const form = this.$refs.requestForm || event.target.closest('form');
            if (form) {
                // 少し遅延を入れて、ローディング状態がUIに反映されるようにする
                setTimeout(() => {
                    form.submit();
                }, 100);
            } else {
                // フォームが見つからない場合のフォールバック
                console.error('フォーム要素が見つかりません');
                this.error = 'フォームの送信に失敗しました。ページをリロードしてください。';
                this.loading = false;
            }
        }
    }
}
</script>
@endpush

