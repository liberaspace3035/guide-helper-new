// チャットページ
import React, { useEffect, useState, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import axios from 'axios';
import { useAuth } from '../contexts/AuthContext';
import './Chat.css';

const Chat = () => {
  const { matchingId } = useParams();
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [matchingInfo, setMatchingInfo] = useState(null);
  const [isRecording, setIsRecording] = useState(false);
  const messagesEndRef = useRef(null);
  const messagesContainerRef = useRef(null);
  const recognitionRef = useRef(null);
  const { user, isUser, isGuide } = useAuth();

  useEffect(() => {
    fetchMatchingInfo();
    fetchMessages();
    // 定期的にメッセージを取得（ポーリング）
    const interval = setInterval(fetchMessages, 3000);
    // チャットページを開いたときに未読数を更新するイベントを発火
    window.dispatchEvent(new CustomEvent('chat-opened'));
    return () => clearInterval(interval);
  }, [matchingId]);

  useEffect(() => {
    // 初期読み込み時のみ即座にスクロール
    if (messages.length > 0 && !loading) {
      const timer = setTimeout(() => scrollToBottom(false), 100);
      return () => clearTimeout(timer);
    }
  }, [loading]);

  useEffect(() => {
    // 新しいメッセージが追加された場合のみスクロール
    if (messages.length > 0) {
      const lastMessage = messages[messages.length - 1];
      if (lastMessage.sender_id === user?.id || !lastMessage.isSending) {
        setTimeout(() => scrollToBottom(true), 100);
      }
    }
  }, [messages.length]);

  const fetchMatchingInfo = async () => {
    try {
      const response = await axios.get('/matchings/my-matchings');
      const matching = response.data.matchings.find(m => m.id === parseInt(matchingId));
      if (matching) {
        setMatchingInfo(matching);
      }
    } catch (error) {
      console.error('マッチング情報取得エラー:', error);
    }
  };

  const fetchMessages = async (showLoading = false) => {
    try {
      if (showLoading) setLoading(true);
      const response = await axios.get(`/chat/messages/${matchingId}`);
      const previousLength = messages.length;
      setMessages(response.data.messages);
      // 新しいメッセージが追加された場合のみスクロール
      if (response.data.messages.length > previousLength) {
        setTimeout(() => scrollToBottom(true), 100);
      }
    } catch (error) {
      console.error('メッセージ取得エラー:', error);
      if (error.response?.status === 403 || error.response?.status === 404) {
        alert('このチャットにアクセスする権限がありません');
      }
    } finally {
      setLoading(false);
    }
  };

  const scrollToBottom = (smooth = false) => {
    if (messagesContainerRef.current) {
      if (smooth) {
        messagesContainerRef.current.scrollTo({
          top: messagesContainerRef.current.scrollHeight,
          behavior: 'smooth'
        });
      } else {
        messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight;
      }
    }
  };

  const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    
    if (messageDate.getTime() === today.getTime()) {
      return date.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });
    } else {
      return date.toLocaleString('ja-JP', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
  };

  const formatDate = (dateString) => {
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
  };

  const shouldShowDateSeparator = (currentMessage, previousMessage) => {
    if (!previousMessage) return true;
    const currentDate = new Date(currentMessage.created_at);
    const previousDate = new Date(previousMessage.created_at);
    return currentDate.toDateString() !== previousDate.toDateString();
  };

  // 音声認識の初期化
  const initSpeechRecognition = () => {
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
      const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      recognitionRef.current = new SpeechRecognition();
      recognitionRef.current.lang = 'ja-JP';
      recognitionRef.current.continuous = true;
      recognitionRef.current.interimResults = true;

      recognitionRef.current.onresult = (event) => {
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

        setNewMessage(prev => {
          const currentText = prev.trim();
          const newText = finalTranscript + (interimTranscript ? ' ' + interimTranscript : '');
          return currentText ? currentText + ' ' + newText : newText;
        });
      };

      recognitionRef.current.onerror = (event) => {
        console.error('音声認識エラー:', event.error);
        setIsRecording(false);
      };

      recognitionRef.current.onend = () => {
        setIsRecording(false);
      };
    } else {
      alert('お使いのブラウザは音声認識に対応していません');
    }
  };

  const startRecording = () => {
    if (recognitionRef.current) {
      try {
        recognitionRef.current.start();
        setIsRecording(true);
      } catch (error) {
        console.error('音声認識開始エラー:', error);
        initSpeechRecognition();
        if (recognitionRef.current) {
          recognitionRef.current.start();
          setIsRecording(true);
        }
      }
    } else {
      initSpeechRecognition();
      if (recognitionRef.current) {
        recognitionRef.current.start();
        setIsRecording(true);
      }
    }
  };

  const stopRecording = () => {
    if (recognitionRef.current && isRecording) {
      recognitionRef.current.stop();
      setIsRecording(false);
    }
  };

  const handleSend = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || sending) return;

    const messageToSend = newMessage.trim();
    setNewMessage('');
    setSending(true);
    
    // 楽観的更新: 送信中のメッセージを一時的に表示
    const tempMessage = {
      id: `temp-${Date.now()}`,
      sender_id: user?.id,
      sender_name: user?.name || 'あなた',
      message: messageToSend,
      created_at: new Date().toISOString(),
      isSending: true
    };
    
    setMessages(prev => [...prev, tempMessage]);
    setTimeout(() => scrollToBottom(true), 50);

    try {
      await axios.post('/chat/messages', {
        matching_id: parseInt(matchingId),
        message: messageToSend
      });
      // メッセージ一覧を更新（一時メッセージを実際のメッセージに置き換え）
      fetchMessages();
    } catch (error) {
      // エラー時は一時メッセージを削除
      setMessages(prev => prev.filter(m => m.id !== tempMessage.id));
      setNewMessage(messageToSend); // メッセージを復元
      alert('メッセージの送信に失敗しました');
      console.error(error);
    } finally {
      setSending(false);
    }
  };

  if (loading && messages.length === 0) {
    return (
      <div className="chat-loading-container">
        <div className="chat-loading-spinner">
          <div className="spinner-ring"></div>
          <div className="spinner-ring"></div>
          <div className="spinner-ring"></div>
        </div>
        <p>メッセージを読み込み中...</p>
      </div>
    );
  }

  const otherUserName = matchingInfo 
    ? (isUser ? matchingInfo.guide_name : matchingInfo.user_name)
    : 'チャット相手';

  return (
    <div className="chat-container">
      <div className="chat-header">
        <div className="chat-header-info">
          <Link to={`/matchings/${matchingId}`} className="chat-back-link" aria-label="戻る">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
          </Link>
          <div className="chat-header-content">
            <div className="chat-header-main">
              <h1>{otherUserName}さんとのチャット</h1>
              {matchingInfo && (
                <p className="chat-subtitle">
                  {matchingInfo.request_type} - {matchingInfo.masked_address}
                </p>
              )}
            </div>
            {matchingInfo && (
              <div className="chat-header-status">
                <span className="status-dot"></span>
                <span className="status-text">アクティブ</span>
              </div>
            )}
          </div>
        </div>
      </div>
      
      <div 
        className="chat-messages" 
        ref={messagesContainerRef}
        role="log" 
        aria-live="polite" 
        aria-label="チャットメッセージ"
      >
        {messages.length === 0 ? (
          <div className="empty-messages">
            <div className="empty-messages-icon">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <h3>まだメッセージがありません</h3>
            <p className="empty-messages-hint">メッセージを送信して会話を始めましょう</p>
          </div>
        ) : (
          <>
            {messages.map((message, index) => {
              const isOwnMessage = message.sender_id === user?.id;
              const previousMessage = index > 0 ? messages[index - 1] : null;
              const showAvatar = index === 0 || messages[index - 1].sender_id !== message.sender_id;
              const showTime = index === messages.length - 1 || 
                new Date(messages[index + 1].created_at).getTime() - new Date(message.created_at).getTime() > 300000; // 5分以上
              const showDateSeparator = shouldShowDateSeparator(message, previousMessage);
              
              return (
                <React.Fragment key={message.id}>
                  {showDateSeparator && (
                    <div className="message-date-separator">
                      <span>{formatDate(message.created_at)}</span>
                    </div>
                  )}
                  <div
                    className={`message-wrapper ${isOwnMessage ? 'message-wrapper-sent' : 'message-wrapper-received'} ${message.isSending ? 'message-sending' : ''}`}
                  >
                    {!isOwnMessage && showAvatar && (
                      <div className="message-avatar">
                        {message.sender_name.charAt(0).toUpperCase()}
                      </div>
                    )}
                    <div className={`message ${isOwnMessage ? 'message-sent' : 'message-received'}`}>
                      {!isOwnMessage && showAvatar && (
                        <div className="message-sender-name">{message.sender_name}</div>
                      )}
                      <div className="message-content">{message.message}</div>
                      <div className="message-footer">
                        {showTime && (
                          <div className="message-time">{formatTime(message.created_at)}</div>
                        )}
                        {isOwnMessage && (
                          <div className="message-status">
                            {message.isSending ? (
                              <span className="message-status-sending">送信中</span>
                            ) : (
                              <svg className="message-status-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 7L6 10L13 3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                              </svg>
                            )}
                          </div>
                        )}
                      </div>
                    </div>
                    {isOwnMessage && showAvatar && (
                      <div className="message-avatar message-avatar-own">
                        {user?.name?.charAt(0).toUpperCase() || 'U'}
                      </div>
                    )}
                  </div>
                </React.Fragment>
              );
            })}
            <div ref={messagesEndRef} />
          </>
        )}
      </div>
      
      <form onSubmit={handleSend} className="chat-input-form" aria-label="メッセージ送信フォーム">
        <div className="chat-input-wrapper">
          <button
            type="button"
            onClick={isRecording ? stopRecording : startRecording}
            className={`chat-voice-button ${isRecording ? 'chat-voice-button-recording' : ''}`}
            aria-label={isRecording ? '音声入力を停止' : '音声入力を開始'}
            title={isRecording ? '音声入力を停止' : '音声入力を開始'}
          >
            {isRecording ? (
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <rect x="6" y="6" width="12" height="12" rx="2"></rect>
              </svg>
            ) : (
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                <line x1="12" y1="19" x2="12" y2="23"></line>
                <line x1="8" y1="23" x2="16" y2="23"></line>
              </svg>
            )}
          </button>
          <input
            type="text"
            value={newMessage}
            onChange={(e) => setNewMessage(e.target.value)}
            placeholder={isRecording ? "音声入力中..." : "メッセージを入力..."}
            className="chat-input"
            aria-label="メッセージ入力"
            autoFocus
          />
          <button
            type="submit"
            disabled={sending || !newMessage.trim()}
            className="chat-send-button"
            aria-label="メッセージを送信"
          >
            {sending ? (
              <span className="chat-sending">送信中...</span>
            ) : (
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
            )}
          </button>
        </div>
        {isRecording && (
          <div className="chat-recording-indicator">
            <span className="chat-recording-dot"></span>
            <span>音声入力中...</span>
          </div>
        )}
      </form>
    </div>
  );
};

export default Chat;

