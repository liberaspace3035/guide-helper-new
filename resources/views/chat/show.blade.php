@extends('layouts.app')

@section('content')
<div class="chat-container" x-data="chatData()" x-init="init()">
    <div class="chat-header">
        <div class="chat-header-info">
            <a :href="`{{ url('/matchings') }}/${matchingId}`" class="chat-back-link" aria-label="戻る">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="chat-header-content">
                <div class="chat-header-main">
                    <h1>
                        <template x-if="matchingInfo">
                            <span x-text="otherUserName + 'さんとのチャット'"></span>
                        </template>
                        <template x-if="!matchingInfo">
                            <span>チャット相手</span>
                        </template>
                    </h1>
                    <template x-if="matchingInfo">
                        <p class="chat-subtitle">
                            <span x-text="matchingInfo.request_type"></span> - <span x-text="matchingInfo.masked_address"></span>
                        </p>
                    </template>
                </div>
                <template x-if="matchingInfo">
                    <div class="chat-header-status">
                        <span class="status-dot"></span>
                        <span class="status-text">アクティブ</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    <div 
        class="chat-messages" 
        x-ref="messagesContainer"
        role="log" 
        aria-live="polite" 
        aria-label="チャットメッセージ"
    >
        <template x-if="loading && messages.length === 0">
            <div class="chat-loading-container">
                <div class="chat-loading-spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
                <p>メッセージを読み込み中...</p>
            </div>
        </template>

        <template x-if="!loading && messages.length === 0">
            <div class="empty-messages">
                <div class="empty-messages-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h3>まだメッセージがありません</h3>
                <p class="empty-messages-hint">メッセージを送信して会話を始めましょう</p>
            </div>
        </template>

        <template x-if="!loading && messages.length > 0">
            <template x-for="(message, index) in messages" :key="message.id || index">
                <div>
                    <template x-if="shouldShowDateSeparator(message, index > 0 ? messages[index - 1] : null)">
                        <div class="message-date-separator">
                            <span x-text="formatDate(message.created_at)"></span>
                        </div>
                    </template>
                    <div
                        class="message-wrapper"
                        :class="isOwnMessage(message) ? 'message-wrapper-sent' : 'message-wrapper-received'"
                        :data-own-message="isOwnMessage(message)"
                        :data-sender-id="message.sender_id"
                    >
                        <template x-if="!isOwnMessage(message) && showAvatar(index)">
                            <div class="message-avatar" x-text="(message.sender_name || '').charAt(0).toUpperCase()"></div>
                        </template>
                        <div 
                            class="message"
                            :class="isOwnMessage(message) ? 'message-sent' : 'message-received'"
                            :data-own-message="isOwnMessage(message)"
                        >
                            <template x-if="!isOwnMessage(message) && showAvatar(index)">
                                <div class="message-sender-name" x-text="message.sender_name || ''"></div>
                            </template>
                            <div class="message-content" x-text="message.message"></div>
                            <div class="message-footer">
                                <template x-if="showTime(index)">
                                    <div class="message-time" x-text="formatTime(message.created_at)"></div>
                                </template>
                                <template x-if="isOwnMessage(message)">
                                    <div class="message-status">
                                        <template x-if="message.isSending">
                                            <span class="message-status-sending">送信中</span>
                                        </template>
                                        <template x-if="!message.isSending">
                                            <svg class="message-status-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                <path d="M3 7L6 10L13 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <template x-if="isOwnMessage(message) && showAvatar(index)">
                            <div class="message-avatar message-avatar-own" x-text="'{{ auth()->user()->name }}'.charAt(0).toUpperCase()"></div>
                        </template>
                    </div>
                </div>
            </template>
            <div x-ref="messagesEnd"></div>
        </template>
    </div>
    
    <form @submit.prevent="sendMessage()" class="chat-input-form" aria-label="メッセージ送信フォーム">
        <div class="chat-input-wrapper">
            <button
                type="button"
                @click="isRecording ? stopRecording() : startRecording()"
                class="chat-voice-button"
                :class="{ 'chat-voice-button-recording': isRecording }"
                aria-label="音声入力"
                title="音声入力"
            >
                <template x-if="isRecording">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="6" y="6" width="12" height="12" rx="2"></rect>
                    </svg>
                </template>
                <template x-if="!isRecording">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                        <line x1="12" y1="19" x2="12" y2="23"></line>
                        <line x1="8" y1="23" x2="16" y2="23"></line>
                    </svg>
                </template>
            </button>
            <input
                type="text"
                x-model="newMessage"
                :placeholder="isRecording ? '音声入力中...' : 'メッセージを入力...'"
                class="chat-input"
                aria-label="メッセージ入力"
                :disabled="sending"
            />
            <button
                type="submit"
                :disabled="sending || !newMessage.trim()"
                class="chat-send-button"
                aria-label="メッセージを送信"
            >
                <template x-if="sending">
                    <span class="chat-sending">送信中...</span>
                </template>
                <template x-if="!sending">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </template>
            </button>
        </div>
        <template x-if="isRecording">
            <div class="chat-recording-indicator">
                <span class="chat-recording-dot"></span>
                <span>音声入力中...</span>
            </div>
        </template>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Chat.css') }}">
@endpush

@push('scripts')
<script>
function chatData() {
    return {
        matchingId: {{ $matchingId }},
        messages: [],
        newMessage: '',
        loading: true,
        sending: false,
        matchingInfo: null,
        isRecording: false,
        recognition: null,
        processedResultIndices: new Set(), // 処理済みの結果インデックスを追跡
        interimText: '', // 中間結果を一時保存（表示用）
        userRole: '{{ auth()->user()->role }}',
        userId: {{ auth()->id() }},
        get otherUserName() {
            if (!this.matchingInfo) return 'チャット相手';
            if (this.userRole === 'user') {
                return this.matchingInfo.guide_name || 'チャット相手';
            } else {
                return this.matchingInfo.user_name || 'チャット相手';
            }
        },
        init() {
            console.log('Chat init - userId:', this.userId, 'type:', typeof this.userId);
            this.fetchMatchingInfo();
            this.fetchMessages();
            // 定期的にメッセージを取得（ポーリング）
            this.interval = setInterval(() => this.fetchMessages(false), 3000);
            // チャットページを開いたときに未読数を更新
            window.dispatchEvent(new CustomEvent('chat-opened'));
        },
        async fetchMatchingInfo() {
            try {
                const response = await fetch('/api/matchings/my-matchings', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(response);
                    if (!shouldContinue) {
                        return;
                    }
                }
                
                if (response.ok) {
                    const data = await response.json();
                    const matching = data.matchings?.find(m => m.id === parseInt(this.matchingId));
                    if (matching) {
                        this.matchingInfo = matching;
                    }
                }
            } catch (error) {
                console.error('マッチング情報取得エラー:', error);
            }
        },
        async fetchMessages(showLoading = false) {
            try {
                if (showLoading) this.loading = true;
                const response = await fetch(`/api/chat/messages/${this.matchingId}`, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(response);
                    if (!shouldContinue) {
                        return;
                    }
                }
                
                if (response.ok) {
                    const data = await response.json();
                    const previousLength = this.messages.length;
                    this.messages = (data.messages || []).map(msg => ({
                        ...msg,
                        isSending: false
                    }));
                }
            } catch (error) {
                console.error('メッセージ取得エラー:', error);
            } finally {
                this.loading = false;
            }
        },
        async sendMessage() {
            if (!this.newMessage.trim() || this.sending) return;

            const messageToSend = this.newMessage.trim();
            this.newMessage = '';
            this.sending = true;
            
            // 楽観的更新: 送信中のメッセージを一時的に表示
            const tempMessage = {
                id: `temp-${Date.now()}`,
                sender_id: this.userId,
                sender_name: '{{ auth()->user()->name }}',
                message: messageToSend,
                created_at: new Date().toISOString(),
                isSending: true
            };
            
            this.messages.push(tempMessage);

            try {
                console.log('Current userId from Alpine:', this.userId);
                const response = await fetch('/api/chat/messages', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        matching_id: this.matchingId,
                        message: messageToSend
                    })
                });
                
                // 419/401エラーのハンドリング
                if (window.handleApiResponse) {
                    const shouldContinue = await window.handleApiResponse(response);
                    if (!shouldContinue) {
                        // 送信失敗時は一時メッセージを削除
                        this.messages = this.messages.filter(m => m.id !== tempMessage.id);
                        return;
                    }
                }
                
                if (response.ok) {
                    // メッセージ一覧を更新（一時メッセージを実際のメッセージに置き換え）
                    await this.fetchMessages(false);
                } else {
                    // エラー時は一時メッセージを削除
                    this.messages = this.messages.filter(m => m.id !== tempMessage.id);
                    this.newMessage = messageToSend; // メッセージを復元
                    alert('メッセージの送信に失敗しました');
                }
            } catch (error) {
                // エラー時は一時メッセージを削除
                this.messages = this.messages.filter(m => m.id !== tempMessage.id);
                this.newMessage = messageToSend; // メッセージを復元
                alert('メッセージの送信に失敗しました');
                console.error(error);
            } finally {
                this.sending = false;
            }
        },
        scrollToBottom(smooth = false) {
            if (this.$refs.messagesContainer) {
                if (smooth) {
                    this.$refs.messagesContainer.scrollTo({
                        top: this.$refs.messagesContainer.scrollHeight,
                        behavior: 'smooth'
                    });
                } else {
                    this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
                }
            }
        },
        isOwnMessage(message) {
            if (!message || !message.sender_id) return false;
            const msgSenderId = Number(message.sender_id);
            const currentUserId = Number(this.userId);
            const isOwn = msgSenderId === currentUserId;

            return isOwn;
        },
        formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            
            if (messageDate.getTime() === today.getTime()) {
                return date.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });
            } else {
                return date.toLocaleString('ja-JP', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            
            if (messageDate.getTime() === today.getTime()) {
                return '今日';
            } else if (messageDate.getTime() === yesterday.getTime()) {
                return '昨日';
            } else {
                return date.toLocaleDateString('ja-JP', { month: 'long', day: 'numeric', weekday: 'short' });
            }
        },
        shouldShowDateSeparator(currentMessage, previousMessage) {
            if (!previousMessage) return true;
            const currentDate = new Date(currentMessage.created_at);
            const previousDate = new Date(previousMessage.created_at);
            return currentDate.toDateString() !== previousDate.toDateString();
        },
        showAvatar(index) {
            if (index === 0) return true;
            const currentMessage = this.messages[index];
            const previousMessage = this.messages[index - 1];
            if (!currentMessage || !previousMessage) return true;
            return parseInt(previousMessage.sender_id) !== parseInt(currentMessage.sender_id);
        },
        showTime(index) {
            if (index === this.messages.length - 1) return true;
            const currentMessage = this.messages[index];
            const nextMessage = this.messages[index + 1];
            if (!currentMessage || !nextMessage) return true;
            const currentTime = new Date(currentMessage.created_at).getTime();
            const nextTime = new Date(nextMessage.created_at).getTime();
            return (nextTime - currentTime) > 300000; // 5分以上
        },
        initSpeechRecognition() {
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                this.recognition = new SpeechRecognition();
                this.recognition.lang = 'ja-JP';
                this.recognition.continuous = true;
                this.recognition.interimResults = true;

                this.recognition.onresult = (event) => {
                    let finalTranscript = '';
                    
                    // 確定結果のみを処理（重複を防ぐ）
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const result = event.results[i];
                        const transcript = result[0].transcript;
                        
                        if (result.isFinal) {
                            // 確定結果は一度だけ追加（重複チェック）
                            if (!this.processedResultIndices.has(i)) {
                                finalTranscript += transcript;
                                this.processedResultIndices.add(i);
                            }
                        } else {
                            // 中間結果は表示用のみ（追加しない）
                            this.interimText = transcript;
                        }
                    }
                    
                    // 確定結果のみを追加（スペースを適切に追加）
                    if (finalTranscript) {
                        const currentText = this.newMessage.trim();
                        // 既存のテキストの末尾が空白でない場合、スペースを追加
                        const separator = currentText && 
                            !currentText.endsWith(' ') && 
                            !currentText.endsWith('\n') && 
                            !currentText.endsWith('。') && 
                            !currentText.endsWith('、') 
                            ? ' ' : '';
                        this.newMessage = currentText + separator + finalTranscript;
                        // 中間結果をクリア
                        this.interimText = '';
                    }
                };

                this.recognition.onerror = (event) => {
                    console.error('音声認識エラー:', event.error);
                    this.isRecording = false;
                    this.interimText = '';
                };

                this.recognition.onend = () => {
                    this.isRecording = false;
                    this.interimText = '';
                    // 処理済み結果をリセット（次回の録音に備える）
                    this.processedResultIndices = new Set();
                };
                
                this.recognition.onstart = () => {
                    // 開始時に処理済み結果をリセット
                    this.processedResultIndices = new Set();
                    this.interimText = '';
                };
            } else {
                alert('お使いのブラウザは音声認識に対応していません');
            }
        },
        startRecording() {
            // 処理済み結果をリセット
            this.processedResultIndices = new Set();
            this.interimText = '';
            
            if (!this.recognition) {
                this.initSpeechRecognition();
            }
            if (this.recognition) {
                try {
                    this.recognition.start();
                    this.isRecording = true;
                } catch (error) {
                    console.error('音声認識開始エラー:', error);
                    this.initSpeechRecognition();
                    if (this.recognition) {
                        this.recognition.start();
                        this.isRecording = true;
                    }
                }
            }
        },
        stopRecording() {
            if (this.recognition && this.isRecording) {
                this.recognition.stop();
                this.isRecording = false;
                this.interimText = '';
            }
        }
    }
}
</script>
@endpush
