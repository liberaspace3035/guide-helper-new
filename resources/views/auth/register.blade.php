@extends('layouts.app')

@section('content')
<div class="register-container" x-data="registerForm()" x-init="init()">
    <div class="register-card">
        <h1>新規登録</h1>
        <p class="register-lead" style="font-size:0.95rem; line-height:1.65; color:#334155; margin:0 0 1rem;">
            One Stepは、一般社団法人With Blindが運営する、視覚障害者向けの外出・自宅での生活支援サービスサイトです。
            同行援護は、外出時の移動や買い物、通院、イベント参加などをガイドが支えるサービスです。
            居宅介護は、自宅での生活の中で必要な支援を行うサービスです。家事援助や育児支援等で、買い物、洗濯、掃除などを支援します。
            千葉県と大阪を拠点に、全国へサービスを拡大中です。ご登録後、面談や必要なお手続きについてメールでご案内します。
        </p>
        <div class="register-progress" aria-label="登録の進捗" style="margin-bottom:1.25rem; padding:0.75rem 1rem; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
            <p style="margin:0 0 0.5rem; font-size:0.9rem; font-weight:600;">所要時間の目安: 約10〜15分</p>
            <ol style="margin:0; padding-left:1.25rem; font-size:0.88rem; line-height:1.5; color:#475569;">
                <li>アカウント種別・基本情報</li>
                <li>詳細情報（利用者またはガイド）</li>
                <li>確認・送信</li>
            </ol>
        </div>
        <!-- ご利用可否の確認に関する注意書き -->
        <div class="registration-notice">
            <div class="notice-icon">⚠</div>
            <div class="notice-content">
                <p class="notice-text">
                    登録後、一般社団法人With Blindの担当者が内容を確認し、利用者・ガイドそれぞれに必要な面談や契約手続きをご案内します。承認後にご利用・ご活動を開始いただけます。
                </p>
                <p class="notice-text">
                    ご利用にはご利用可否の確認があります。ユーザーは「利用契約書」、ガイドは「入社手続」の実施後、運営者による承認を経てご利用いただけます。登録後に運営からメールでご連絡いたします。
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" novalidate @submit="handleSubmit($event)" aria-label="ユーザー登録フォーム">
            @csrf
            @if(!empty($fromEventId) || old('from_event'))
                <input type="hidden" name="from_event" value="{{ old('from_event', $fromEventId ?? '') }}">
            @endif
            @if($errors->any())
                <div class="error-message" id="register-error-summary" role="alert" aria-live="polite" aria-atomic="true">
                    <p><span class="sr-only">入力内容に誤りがあります。該当する項目は以下のとおりです。</span></p>
                    <ul class="error-summary-list" aria-label="誤りがある項目">
                        @foreach($errors->getMessages() as $key => $messages)
                            @php
                                if (str_starts_with($key, 'qualifications.')) {
                                    $attrLabel = '保有資格';
                                } elseif ($key === 'confirmPassword') {
                                    $attrLabel = 'パスワード（確認）';
                                } elseif ($key === 'email_confirmation') {
                                    $attrLabel = 'メールアドレス（確認）';
                                } else {
                                    $attrLabel = __("validation.attributes.{$key}");
                                    if (str_starts_with($attrLabel, 'validation.')) {
                                        $attrLabel = str_replace('_', ' ', preg_replace('/\.\d+\./', ' ', $key));
                                    }
                                }
                            @endphp
                            @foreach($messages as $msg)
                                <li><span class="error-summary-field">{{ $attrLabel }}</span>：{{ $msg }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- 入力画面 -->
            <div x-show="step === 'input'" x-cloak>
            <!-- 基本情報と連絡先を1行に配置 -->
            <div class="form-sections-row">
                <!-- 基本情報セクション -->
                <div class="form-section form-section-half">
                    <h2 class="section-title">基本情報</h2>
                    <div class="form-group name-group">
                        <label>お名前 <span class="required">*</span></label>
                        <div class="name-inputs">
                            <div class="name-input-item">
                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    x-model="formData.last_name"
                                    required
                                    placeholder="姓"
                                    aria-required="true"
                                    :aria-invalid="!!fieldErrors.last_name"
                                    :aria-describedby="fieldErrors.last_name ? 'last_name-error' : null"
                                />
                                <label for="last_name" class="input-label">姓</label>
                                <span id="last_name-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.last_name" x-text="fieldErrors.last_name"></span>
                            </div>
                            <div class="name-input-item">
                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    x-model="formData.first_name"
                                    required
                                    placeholder="名"
                                    aria-required="true"
                                    :aria-invalid="!!fieldErrors.first_name"
                                    :aria-describedby="fieldErrors.first_name ? 'first_name-error' : null"
                                />
                                <label for="first_name" class="input-label">名</label>
                                <span id="first_name-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.first_name" x-text="fieldErrors.first_name"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group name-group">
                        <label>お名前（カナ） <span class="required">*</span></label>
                        <div class="name-inputs">
                            <div class="name-input-item">
                                <input
                                    type="text"
                                    id="last_name_kana"
                                    name="last_name_kana"
                                    x-model="formData.last_name_kana"
                                    placeholder="セイ（全角カタカナで入力）"
                                    pattern="[ァ-ヶー\s]*"
                                    title="姓（カナ）は全角カタカナで入力してください。名前の読み方の部分です。"
                                    required
                                    aria-required="true"
                                    :aria-invalid="!!fieldErrors.last_name_kana"
                                    :aria-describedby="fieldErrors.last_name_kana ? 'last_name_kana-error' : null"
                                />
                                <label for="last_name_kana" class="input-label">姓（カナ）</label>
                                <span id="last_name_kana-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.last_name_kana" x-text="fieldErrors.last_name_kana"></span>
                            </div>
                            <div class="name-input-item">
                                <input
                                    type="text"
                                    id="first_name_kana"
                                    name="first_name_kana"
                                    x-model="formData.first_name_kana"
                                    placeholder="メイ（全角カタカナで入力）"
                                    pattern="[ァ-ヶー\s]*"
                                    title="名（カナ）は全角カタカナで入力してください。名前の読み方の部分です。"
                                    required
                                    aria-required="true"
                                    :aria-invalid="!!fieldErrors.first_name_kana"
                                    :aria-describedby="fieldErrors.first_name_kana ? 'first_name_kana-error' : null"
                                />
                                <label for="first_name_kana" class="input-label">名（カナ）</label>
                                <span id="first_name_kana-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.first_name_kana" x-text="fieldErrors.first_name_kana"></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="birth_year">生年月日 <span class="required">*</span></label>
                            <span id="birth_date-description" class="sr-only">今日より前の日付を選択してください。</span>
                            <div class="form-row">
                                <div class="form-group form-group-third">
                                    <select id="birth_year" x-model="formData.birth_year" @change="updateBirthDate()" required aria-required="true" :aria-invalid="!!fieldErrors.birth_date">
                                        <option value="">年</option>
                                        <template x-for="year in getBirthYears()" :key="year">
                                            <option :value="String(year)" x-text="year + '年'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="form-group form-group-third">
                                    <select id="birth_month" x-model="formData.birth_month" @change="updateBirthDate()" required aria-required="true" :aria-invalid="!!fieldErrors.birth_date">
                                        <option value="">月</option>
                                        <template x-for="month in 12" :key="month">
                                            <option :value="String(month).padStart(2, '0')" x-text="month + '月'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="form-group form-group-third">
                                    <select id="birth_day" x-model="formData.birth_day" @change="updateBirthDate()" required aria-required="true" :aria-invalid="!!fieldErrors.birth_date">
                                        <option value="">日</option>
                                        <template x-for="day in getBirthDays()" :key="day">
                                            <option :value="String(day).padStart(2, '0')" x-text="day + '日'"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" id="birth_date" name="birth_date" :value="formData.birth_date">
                            <span id="birth_date-error" class="error-message-field" role="alert" aria-live="polite" aria-atomic="true" x-show="fieldErrors.birth_date" x-text="fieldErrors.birth_date"></span>
                        </div>
                        <div class="form-group form-group-half">
                            <label for="gender">性別 <span class="required">*</span></label>
                            <select
                                id="gender"
                                name="gender"
                                x-model="formData.gender"
                                required
                                aria-required="true"
                                :aria-invalid="!!fieldErrors.gender"
                                :aria-describedby="fieldErrors.gender ? 'gender-error' : null"
                            >
                                <option value="">選択してください</option>
                                <option value="male">男性</option>
                                <option value="female">女性</option>
                                <option value="other">その他</option>
                                <option value="prefer_not_to_say">回答しない</option>
                            </select>
                            <span id="gender-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.gender" x-text="fieldErrors.gender"></span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="postal_code">郵便番号 <span class="required" aria-label="必須">*</span></label>
                            <input
                                type="text"
                                id="postal_code"
                                name="postal_code"
                                x-model="formData.postal_code"
                                placeholder="例: 123-4567"
                                pattern="\d{3}-\d{4}"
                                required
                                aria-required="true"
                                inputmode="numeric"
                                :aria-invalid="fieldErrors.postal_code ? 'true' : 'false'"
                                :aria-describedby="fieldErrors.postal_code ? 'postal_code-error' : 'postal_code-description'"
                                @blur="validatePostalCode()"
                            />
                            <span id="postal_code-description" class="sr-only">郵便番号をハイフン付きで入力してください（例: 123-4567）</span>
                            <span id="postal_code-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.postal_code" x-text="fieldErrors.postal_code"></span>
                        </div>
                        <div class="form-group form-group-half">
                            <label for="address">住所 <span class="required">*</span></label>
                            <textarea
                                id="address"
                                name="address"
                                x-model="formData.address"
                                rows="2"
                                placeholder="都道府県、市区町村、番地などを入力"
                                required
                                aria-required="true"
                                :aria-invalid="!!fieldErrors.address"
                                :aria-describedby="fieldErrors.address ? 'address-error' : null"
                            ></textarea>
                            <span id="address-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.address" x-text="fieldErrors.address"></span>
                        </div>
                    </div>
                </div>

                <!-- 連絡先とアカウント情報セクション -->
                <div class="form-section form-section-half">
                    <!-- 連絡先セクション -->
                    <div class="form-subsection">
                        <h2 class="section-title">連絡先</h2>
                        <div class="form-group">
                            <label for="email">
                                メールアドレス <span class="required" aria-label="必須">*</span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                x-model="formData.email"
                                required
                                autocomplete="email"
                                aria-required="true"
                                :aria-invalid="fieldErrors.email ? 'true' : 'false'"
                                :aria-describedby="fieldErrors.email ? 'email-error' : 'email-description'"
                                @blur="validateEmail()"
                            />
                            <span id="email-description" class="sr-only">ログインに使用するメールアドレスを入力してください</span>
                            <span id="email-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.email" x-text="fieldErrors.email"></span>
                        </div>
                        <div class="form-group">
                            <label for="email_confirmation">
                                メールアドレス（確認） <span class="required" aria-label="必須">*</span>
                            </label>
                            <input
                                type="email"
                                id="email_confirmation"
                                name="email_confirmation"
                                x-model="formData.email_confirmation"
                                required
                                autocomplete="email"
                                aria-required="true"
                                :aria-invalid="fieldErrors.email_confirmation ? 'true' : 'false'"
                                :aria-describedby="fieldErrors.email_confirmation ? 'email-confirmation-error' : 'email-confirmation-description'"
                                @blur="validateEmailConfirmation()"
                            />
                            <span id="email-confirmation-description" class="sr-only">上記で入力したメールアドレスと同じものを再度入力してください</span>
                            <span id="email-confirmation-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.email_confirmation" x-text="fieldErrors.email_confirmation"></span>
                        </div>
                        <div class="form-group">
                            <label for="phone">電話番号 <span class="required">*</span></label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                x-model="formData.phone"
                                required
                                autocomplete="tel"
                                placeholder="例: 090-1234-5678"
                                pattern="[\d\-\+\(\)\s]+"
                                aria-required="true"
                                :aria-invalid="!!fieldErrors.phone"
                                :aria-describedby="fieldErrors.phone ? 'phone-error' : null"
                            />
                            <span id="phone-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.phone" x-text="fieldErrors.phone"></span>
                        </div>
                    </div>

                    <!-- アカウント情報セクション -->
                    <div class="form-subsection">
                        <h2 class="section-title">アカウント情報</h2>
                        <div class="form-group">
                            <label for="role">登録タイプ <span class="required">*</span></label>
                            <select
                                id="role"
                                name="role"
                                x-model="formData.role"
                                required
                                aria-required="true"
                            >
                                <option value="user">ユーザー（視覚障害者）</option>
                                <option value="guide">ガイドヘルパー</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="password">
                                パスワード <span class="required" aria-label="必須">*</span>
                            </label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                x-model="formData.password"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                aria-required="true"
                                :aria-invalid="fieldErrors.password ? 'true' : 'false'"
                                :aria-describedby="fieldErrors.password ? 'password-error' : 'password-description'"
                                @blur="validatePassword()"
                            />
                            <small id="password-description">6文字以上で入力してください</small>
                            <span id="password-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.password" x-text="fieldErrors.password"></span>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">
                                パスワード（確認） <span class="required" aria-label="必須">*</span>
                            </label>
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                x-model="formData.confirmPassword"
                                required
                                autocomplete="new-password"
                                aria-required="true"
                                :aria-invalid="fieldErrors.confirmPassword ? 'true' : 'false'"
                                :aria-describedby="fieldErrors.confirmPassword ? 'confirm-password-error' : 'confirm-password-description'"
                                @blur="validatePasswordConfirmation()"
                            />
                            <span id="confirm-password-description" class="sr-only">上記で入力したパスワードと同じものを再度入力してください</span>
                            <span id="confirm-password-error" class="error-message-field" role="alert" aria-live="polite" x-show="fieldErrors.confirmPassword" x-text="fieldErrors.confirmPassword"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ロール別の追加項目 -->
            <template x-if="formData.role === 'user'">
                <div class="form-section form-section-full">
                    <h2 class="section-title">利用者情報</h2>
                    
                    <div class="form-group">
                        <label>面談希望日時 <span class="required">*</span></label>
                        <div class="form-row">
                            <div class="form-group form-group-third">
                                <label for="interview_date_1" class="sub-label">第1希望 <span class="required">*</span></label>
                                <input
                                    type="datetime-local"
                                    id="interview_date_1"
                                    name="interview_date_1"
                                    x-model="formData.interview_date_1"
                                    :min="minInterviewDate"
                                    required
                                    aria-required="true"
                                />
                            </div>
                            <div class="form-group form-group-third">
                                <label for="interview_date_2" class="sub-label">第2希望</label>
                                <input
                                    type="datetime-local"
                                    id="interview_date_2"
                                    name="interview_date_2"
                                    x-model="formData.interview_date_2"
                                    :min="minInterviewDate"
                                />
                            </div>
                            <div class="form-group form-group-third">
                                <label for="interview_date_3" class="sub-label">第3希望</label>
                                <input
                                    type="datetime-local"
                                    id="interview_date_3"
                                    name="interview_date_3"
                                    x-model="formData.interview_date_3"
                                    :min="minInterviewDate"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="application_reason">応募のきっかけ <span class="required">*</span></label>
                        <select
                            id="application_reason"
                            x-model="formData.application_reason"
                            required
                            aria-required="true"
                            :aria-describedby="formData.application_reason === 'その他（自由記述）' ? 'application_reason_other_description' : null"
                        >
                            <option value="">選択してください</option>
                            <option value="With Blindのホームページを見て">With Blindのホームページを見て</option>
                            <option value="X（旧Twitter）の投稿を見て">X（旧Twitter）の投稿を見て</option>
                            <option value="Facebookの投稿を見て">Facebookの投稿を見て</option>
                            <option value="Instagramの投稿を見て">Instagramの投稿を見て</option>
                            <option value="知人・友人からの紹介">知人・友人からの紹介</option>
                            <option value="家族からの紹介">家族からの紹介</option>
                            <option value="学校・職場からの紹介">学校・職場からの紹介</option>
                            <option value="他の利用者・ガイドからの紹介">他の利用者・ガイドからの紹介</option>
                            <option value="イベントや説明会に参加して">イベントや説明会に参加して</option>
                            <option value="メディア掲載（テレビ・新聞・Web記事など）を見て">メディア掲載（テレビ・新聞・Web記事など）を見て</option>
                            <option value="検索エンジン（Googleなど）で見つけて">検索エンジン（Googleなど）で見つけて</option>
                            <option value="その他（自由記述）">その他（自由記述）</option>
                        </select>
                        <template x-if="formData.application_reason === 'その他（自由記述）'">
                            <div class="form-group" style="margin-top: 0.75rem;">
                                <label for="application_reason_other" class="input-label">その他の内容を記入してください <span class="required">*</span></label>
                                <textarea
                                    id="application_reason_other"
                                    x-model="formData.application_reason_other"
                                    rows="3"
                                    placeholder="きっかけの内容を記入してください"
                                    aria-required="true"
                                    :required="formData.application_reason === 'その他（自由記述）'"
                                ></textarea>
                                <span id="application_reason_other_description" class="sr-only">その他を選んだ場合は、きっかけの内容を記入してください</span>
                            </div>
                        </template>
                        <input type="hidden" name="application_reason" :value="getApplicationReasonValue()">
                    </div>

                    <div class="form-group">
                        <label for="visual_disability_status">視覚障害の状況 <span class="required">*</span></label>
                        <textarea
                            id="visual_disability_status"
                            name="visual_disability_status"
                            x-model="formData.visual_disability_status"
                            rows="4"
                            required
                            aria-required="true"
                            placeholder="視覚障害の状況を記入してください"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="disability_support_level">
                            障害支援区分 <span class="label-hint">（１から６、またはなしから選択）</span> <span class="required">*</span>
                        </label>
                        <select
                            id="disability_support_level"
                            name="disability_support_level"
                            x-model="formData.disability_support_level"
                            required
                            aria-required="true"
                        >
                            <option value="">選択してください</option>
                            <option value="１">１</option>
                            <option value="２">２</option>
                            <option value="３">３</option>
                            <option value="４">４</option>
                            <option value="５">５</option>
                            <option value="６">６</option>
                            <option value="なし">なし</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="daily_life_situation">普段の生活状況 <span class="required">*</span></label>
                        <div class="info-box">
                            <p class="info-text">普段の生活状況について、以下の点を記入してください：</p>
                            <ul class="info-list">
                                <li>日常生活での困りごと</li>
                                <li>現在の生活スタイル</li>
                                <li>支援が必要な場面</li>
                            </ul>
                        </div>
                        <textarea
                            id="daily_life_situation"
                            name="daily_life_situation"
                            x-model="formData.daily_life_situation"
                            rows="6"
                            required
                            aria-required="true"
                            placeholder="普段の生活状況を記入してください"
                        ></textarea>
                    </div>
                </div>
            </template>

            <template x-if="formData.role === 'guide'">
                <div class="form-section form-section-full">
                    <h2 class="section-title">ガイド情報</h2>
                    
                    <div class="form-group">
                        <label for="application_reason">応募理由 <span class="required">*</span></label>
                        <textarea
                            id="application_reason"
                            name="application_reason"
                            x-model="formData.application_reason"
                            rows="4"
                            required
                            aria-required="true"
                            placeholder="ガイドヘルパーとして応募する理由を記入してください"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="goal">実現したいこと <span class="required">*</span></label>
                        <textarea
                            id="goal"
                            name="goal"
                            x-model="formData.goal"
                            rows="4"
                            required
                            aria-required="true"
                            placeholder="ガイドヘルパーとして実現したいことを記入してください"
                        ></textarea>
                    </div>

                    <div class="form-group" id="register-guide-qualifications">
                        <label>保有資格 <span class="required">*</span></label>
                        <p class="form-help-text">該当する資格をすべて選択してください。外出支援・自宅支援を行うには各種資格が必要です。</p>
                        
                        <div class="qualification-category">
                            <h4 class="qualification-category-title">外出支援（同行援護）に必要な資格</h4>
                            <p class="qualification-category-note">外出支援を行うには、以下のいずれかの資格が必要です。</p>
                            <div class="checkbox-group qualification-checkboxes">
                                @foreach(\App\Models\GuideProfile::OUTING_REQUIRED_QUALIFICATIONS as $key)
                                    <label class="checkbox-label">
                                        <input
                                            type="checkbox"
                                            name="qualifications[]"
                                            value="{{ $key }}"
                                            x-model="formData.qualifications"
                                        />
                                        {{ \App\Models\GuideProfile::QUALIFICATION_OPTIONS[$key] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="qualification-category">
                            <h4 class="qualification-category-title">自宅支援に必要な資格</h4>
                            <p class="qualification-category-note">自宅支援を行うには、以下のいずれかの資格が必要です。</p>
                            <div class="checkbox-group qualification-checkboxes">
                                @foreach(\App\Models\GuideProfile::HOME_REQUIRED_QUALIFICATIONS as $key)
                                    <label class="checkbox-label">
                                        <input
                                            type="checkbox"
                                            name="qualifications[]"
                                            value="{{ $key }}"
                                            x-model="formData.qualifications"
                                        />
                                        {{ \App\Models\GuideProfile::QUALIFICATION_OPTIONS[$key] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="qualification-summary" x-show="formData.qualifications.length > 0">
                            <strong>対応可能な支援:</strong>
                            <span x-show="canSupportOuting()" class="support-badge support-outing">外出支援</span>
                            <span x-show="canSupportHome()" class="support-badge support-home">自宅支援</span>
                            <span x-show="!canSupportOuting() && !canSupportHome()" class="support-badge support-none">資格を選択してください</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>希望勤務条件 <span class="required">*</span></label>
                        <p class="form-help-text">対応可能な曜日と時間帯を選択してください（複数選択可）。</p>
                        <div class="form-group" id="register-guide-available-days">
                            <label>対応可能日 <span class="required">*</span></label>
                            <div class="checkbox-group">
                                @foreach(['月','火','水','木','金','土','日','祝日'] as $day)
                                    <label class="checkbox-label">
                                        <input
                                            type="checkbox"
                                            name="available_days[]"
                                            value="{{ $day }}"
                                            x-model="formData.available_days"
                                        />
                                        {{ $day }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group" id="register-guide-available-times">
                            <label>対応可能時間帯 <span class="required">*</span></label>
                            <div class="checkbox-group">
                                @foreach(['午前から可','午後から可','1日フリー可','その都度調整'] as $time)
                                    <label class="checkbox-label">
                                        <input
                                            type="checkbox"
                                            name="available_times[]"
                                            value="{{ $time }}"
                                            x-model="formData.available_times"
                                        />
                                        {{ $time }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            </div>
            <!-- /入力画面 -->

            <!-- 入力内容確認画面 -->
            <div x-show="step === 'confirm'" x-cloak class="confirm-section" role="region" aria-label="入力内容の確認">
                <div class="confirm-header">
                    <h2 class="confirm-title">入力内容の確認</h2>
                    <p class="confirm-description">内容をご確認のうえ「送信する」を押してください。修正する場合は「修正する」で入力画面に戻れます。</p>
                </div>

                <div class="confirm-block">
                    <h3 class="confirm-block-title">基本情報</h3>
                    <dl class="confirm-list">
                        <div class="confirm-row">
                            <dt class="confirm-label">登録タイプ</dt>
                            <dd class="confirm-value" x-text="formData.role === 'user' ? '利用者' : 'ガイド'"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">お名前</dt>
                            <dd class="confirm-value" x-text="(formData.last_name || '') + ' ' + (formData.first_name || '')"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">お名前（カナ）</dt>
                            <dd class="confirm-value" x-text="(formData.last_name_kana || '') + ' ' + (formData.first_name_kana || '')"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">生年月日</dt>
                            <dd class="confirm-value" x-text="formData.birth_date || '—'"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">性別</dt>
                            <dd class="confirm-value" x-text="getGenderLabel(formData.gender)"></dd>
                        </div>
                    </dl>
                </div>

                <div class="confirm-block">
                    <h3 class="confirm-block-title">連絡先</h3>
                    <dl class="confirm-list">
                        <div class="confirm-row">
                            <dt class="confirm-label">郵便番号</dt>
                            <dd class="confirm-value" x-text="formData.postal_code || '—'"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">住所</dt>
                            <dd class="confirm-value" x-text="formData.address || '—'"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">電話番号</dt>
                            <dd class="confirm-value" x-text="formData.phone || '—'"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">メールアドレス</dt>
                            <dd class="confirm-value" x-text="formData.email || '—'"></dd>
                        </div>
                        <div class="confirm-row">
                            <dt class="confirm-label">パスワード</dt>
                            <dd class="confirm-value">********</dd>
                        </div>
                    </dl>
                </div>

                <template x-if="formData.role === 'user'">
                    <div class="confirm-block">
                        <h3 class="confirm-block-title">利用者情報</h3>
                        <dl class="confirm-list">
                            <div class="confirm-row">
                                <dt class="confirm-label">面談希望日時（第1希望）</dt>
                                <dd class="confirm-value" x-text="formatConfirmDateTime(formData.interview_date_1)"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">面談希望日時（第2希望）</dt>
                                <dd class="confirm-value" x-text="formatConfirmDateTime(formData.interview_date_2) || '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">面談希望日時（第3希望）</dt>
                                <dd class="confirm-value" x-text="formatConfirmDateTime(formData.interview_date_3) || '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">応募のきっかけ</dt>
                                <dd class="confirm-value" x-text="getApplicationReasonValue() || '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">視覚障害の状況</dt>
                                <dd class="confirm-value confirm-text-block" x-text="formData.visual_disability_status || '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">障害支援区分</dt>
                                <dd class="confirm-value" x-text="formData.disability_support_level || '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">普段の生活状況</dt>
                                <dd class="confirm-value confirm-text-block" x-text="formData.daily_life_situation || '—'"></dd>
                            </div>
                        </dl>
                    </div>
                </template>

                <template x-if="formData.role === 'guide'">
                    <div class="confirm-block">
                        <h3 class="confirm-block-title">ガイド情報</h3>
                        <dl class="confirm-list">
                            <div class="confirm-row">
                                <dt class="confirm-label">応募理由</dt>
                                <dd class="confirm-value confirm-text-block" x-text="formData.application_reason || '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">実現したいこと</dt>
                                <dd class="confirm-value confirm-text-block" x-text="formData.goal || '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">保有資格</dt>
                                <dd class="confirm-value">
                                <template x-for="(qual, index) in formData.qualifications" :key="index">
                                    <div class="confirm-qual-item" x-text="getQualificationLabel(qual)"></div>
                                </template>
                                <div class="confirm-support-types">
                                    <span x-show="canSupportOuting()" class="support-badge support-outing">外出支援可</span>
                                    <span x-show="canSupportHome()" class="support-badge support-home">自宅支援可</span>
                                </div>
                                </dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">対応可能日</dt>
                                <dd class="confirm-value" x-text="(formData.available_days || []).length ? formData.available_days.join('・') : '—'"></dd>
                            </div>
                            <div class="confirm-row">
                                <dt class="confirm-label">対応可能時間帯</dt>
                                <dd class="confirm-value" x-text="(formData.available_times || []).length ? formData.available_times.join('・') : '—'"></dd>
                            </div>
                        </dl>
                    </div>
                </template>

                <div class="confirm-actions">
                    <button
                        type="button"
                        class="btn-secondary btn-confirm-back"
                        @click="step = 'input'"
                        aria-label="入力画面に戻る"
                    >
                        修正する
                    </button>
                    <button
                        type="submit"
                        class="btn-primary btn-confirm-submit"
                        :disabled="loading"
                        aria-label="登録を送信する"
                    >
                        <span x-show="!loading">送信する</span>
                        <span x-show="loading">送信中...</span>
                    </button>
                </div>
            </div>
            <!-- /入力内容確認画面 -->

            <div x-show="step === 'input'" id="register-errors-anchor" tabindex="-1">
                <div x-show="error" class="error-message" id="register-error-client" role="alert" aria-live="polite" aria-atomic="true" x-text="error"></div>
                <div class="error-message" role="alert" aria-live="polite" x-show="Object.keys(fieldErrors).length > 0" x-cloak>
                    <p class="error-summary-intro">入力内容をご確認ください。</p>
                    <ul class="error-summary-list">
                        <template x-for="[key, msg] in Object.entries(fieldErrors)" :key="key">
                            <li x-text="msg"></li>
                        </template>
                    </ul>
                </div>
                <button
                    type="button"
                    class="btn-primary btn-block"
                    @click="validateAndGoToConfirm()"
                    aria-label="入力内容を確認する"
                >
                    入力内容を確認する
                </button>
            </div>
        </form>
        <p class="login-link">
            既にアカウントをお持ちの方は
            <a href="{{ route('login') }}">こちらからログイン</a>
        </p>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Register.css') }}">
<style>
    #register-error-summary { margin-bottom: 1rem; }
    #register-errors-anchor { margin-top: 1.5rem; margin-bottom: 0.75rem; }
    .error-summary-intro { margin: 0 0 0.35rem; font-weight: 600; }
    .error-summary-list { margin: 0.25rem 0 0; padding-left: 1.25rem; }
    .error-summary-field { font-weight: 700; }
    .register-card input[aria-invalid="true"],
    .register-card select[aria-invalid="true"],
    .register-card textarea[aria-invalid="true"] {
        border: 2px solid #dc2626 !important;
        background-color: #fff7f7;
    }
</style>
@endpush

@push('scripts')
<script>
function registerForm() {
    return {
        formData: {
            email: @json(old('email', '')),
            email_confirmation: @json(old('email_confirmation', '')),
            password: '',
            confirmPassword: '',
            last_name: @json(old('last_name', '')),
            first_name: @json(old('first_name', '')),
            last_name_kana: @json(old('last_name_kana', '')),
            first_name_kana: @json(old('first_name_kana', '')),
            birth_date: @json(old('birth_date', '')),
            birth_year: '',
            birth_month: '',
            birth_day: '',
            gender: @json(old('gender', '')),
            postal_code: @json(old('postal_code', '')),
            address: @json(old('address', '')),
            phone: @json(old('phone', '')),
            role: @json(old('role', $defaultRole ?? 'user')),
            // ユーザー用
            interview_date_1: @json(old('interview_date_1', '')),
            interview_date_2: @json(old('interview_date_2', '')),
            interview_date_3: @json(old('interview_date_3', '')),
            application_reason: @json(old('application_reason', '')),
            application_reason_other: '',
            visual_disability_status: @json(old('visual_disability_status', '')),
            disability_support_level: @json(old('disability_support_level', '')),
            daily_life_situation: @json(old('daily_life_situation', '')),
            // ガイド用
            goal: @json(old('goal', '')),
            qualifications: @json(old('qualifications', [])),
            available_days: @json(old('available_days', [])),
            available_times: @json(old('available_times', []))
        },
        // 資格マスタ
        qualificationOptions: @json(\App\Models\GuideProfile::QUALIFICATION_OPTIONS),
        outingQualifications: @json(\App\Models\GuideProfile::OUTING_REQUIRED_QUALIFICATIONS),
        homeQualifications: @json(\App\Models\GuideProfile::HOME_REQUIRED_QUALIFICATIONS),
        error: '',
        fieldErrors: {},
        loading: false,
        step: 'input',
        init() {
            this.parseBirthDateToParts();
            const serverErrors = @json($errors->toArray());
            const keyMap = {
                confirmPassword: 'confirmPassword',
                email_confirmation: 'email_confirmation',
            };
            Object.keys(serverErrors || {}).forEach((key) => {
                const target = keyMap[key] || key;
                const messages = serverErrors[key];
                if (Array.isArray(messages) && messages.length > 0) {
                    this.fieldErrors[target] = messages[0];
                }
            });
            this.step = 'input';
            this.$nextTick(() => {
                if (Object.keys(this.fieldErrors).length > 0) {
                    const sum = document.getElementById('register-error-summary');
                    if (sum) {
                        sum.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        this.scrollToFirstError();
                    }
                }
            });
        },
        parseBirthDateToParts() {
            if (!this.formData.birth_date || !this.formData.birth_date.includes('-')) return;
            const [y, m, d] = this.formData.birth_date.split('-');
            this.formData.birth_year = y || '';
            this.formData.birth_month = m || '';
            this.formData.birth_day = d || '';
        },
        updateBirthDate() {
            if (this.formData.birth_year && this.formData.birth_month && this.formData.birth_day) {
                this.formData.birth_date = `${this.formData.birth_year}-${this.formData.birth_month}-${this.formData.birth_day}`;
            } else {
                this.formData.birth_date = '';
            }
        },
        getBirthYears() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let y = currentYear; y >= 1900; y--) years.push(y);
            return years;
        },
        getBirthDays() {
            const y = parseInt(this.formData.birth_year || '0', 10);
            const m = parseInt(this.formData.birth_month || '0', 10);
            if (!y || !m) return Array.from({ length: 31 }, (_, i) => i + 1);
            const lastDay = new Date(y, m, 0).getDate();
            return Array.from({ length: lastDay }, (_, i) => i + 1);
        },
        // 面談日の最小値（今日以降）
        get minInterviewDate() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },
        // 資格取得日の最大値（今日以前）
        get maxQualificationDate() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        canSupportOuting() {
            return this.formData.qualifications.some(q => this.outingQualifications.includes(q));
        },
        canSupportHome() {
            return this.formData.qualifications.some(q => this.homeQualifications.includes(q));
        },
        getQualificationLabel(key) {
            return this.qualificationOptions[key] || key;
        },
        getApplicationReasonValue() {
            if (this.formData.role !== 'user') return this.formData.application_reason || '';
            if (this.formData.application_reason === 'その他（自由記述）') {
                const other = (this.formData.application_reason_other || '').trim();
                return other ? 'その他（自由記述）：' + other : 'その他（自由記述）';
            }
            return this.formData.application_reason || '';
        },
        getGenderLabel(value) {
            const map = { male: '男性', female: '女性', other: 'その他', prefer_not_to_say: '回答しない' };
            return map[value] || value || '—';
        },
        formatConfirmDateTime(str) {
            if (!str) return '';
            const s = String(str).trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
                const [y, m, day] = s.split('-');
                return `${y}/${m}/${day}（時刻は 9:00 として登録されます）`;
            }
            try {
                const d = new Date(s);
                if (isNaN(d.getTime())) return s;
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                const h = String(d.getHours()).padStart(2, '0');
                const min = String(d.getMinutes()).padStart(2, '0');
                return y + '/' + m + '/' + day + ' ' + h + ':' + min;
            } catch (e) { return s; }
        },
        normalizeInterviewOptional(key) {
            let v = this.formData[key];
            if (v === undefined || v === null || String(v).trim() === '') return;
            let s = String(v).trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
                this.formData[key] = s + 'T09:00';
                return;
            }
            if (/^\d{4}-\d{2}-\d{2}T$/.test(s)) {
                this.formData[key] = s + '09:00';
                return;
            }
            if (/^\d{4}-\d{2}-\d{2}T\d{2}$/.test(s)) {
                this.formData[key] = s + ':00';
            }
        },
        validateAndGoToConfirm() {
            this.error = '';
            this.fieldErrors = {};
            // 必須項目（サーバー側と同一）
            if (!this.formData.last_name?.trim()) { this.fieldErrors.last_name = '姓を入力してください'; }
            if (!this.formData.first_name?.trim()) { this.fieldErrors.first_name = '名を入力してください'; }
            if (!this.formData.gender) { this.fieldErrors.gender = '性別を選択してください'; }
            this.validateKana();
            this.validateBirthDate();
            if (!this.formData.postal_code?.trim()) { this.fieldErrors.postal_code = '郵便番号を入力してください'; }
            else { this.validatePostalCode(); }
            if (!this.formData.address?.trim()) { this.fieldErrors.address = '住所を入力してください'; }
            if (!this.formData.phone?.trim()) { this.fieldErrors.phone = '電話番号を入力してください'; }
            else { this.validatePhone(); }
            if (!this.formData.email?.trim()) { this.fieldErrors.email = 'メールアドレスを入力してください'; }
            if (!this.formData.email_confirmation?.trim()) { this.fieldErrors.email_confirmation = 'メールアドレス（確認）を入力してください'; }
            if (!this.formData.password) { this.fieldErrors.password = 'パスワードを入力してください'; }
            if (!this.formData.confirmPassword) { this.fieldErrors.confirmPassword = 'パスワード（確認）を入力してください'; }
            this.validateEmail();
            this.validateEmailConfirmation();
            this.validatePassword();
            this.validatePasswordConfirmation();
            if (Object.keys(this.fieldErrors).length > 0) {
                this.scrollToFirstError();
                return;
            }
            if (this.formData.email !== this.formData.email_confirmation) {
                this.fieldErrors.email_confirmation = 'メールアドレスが一致しません';
                this.scrollToFirstError();
                return;
            }
            if (this.formData.password !== this.formData.confirmPassword) {
                this.fieldErrors.confirmPassword = 'パスワードが一致しません';
                this.scrollToFirstError();
                return;
            }
            if (this.formData.password.length < 6) {
                this.fieldErrors.password = 'パスワードは6文字以上で入力してください';
                this.scrollToFirstError();
                return;
            }
            if (this.formData.role === 'user') {
                this.normalizeInterviewOptional('interview_date_2');
                this.normalizeInterviewOptional('interview_date_3');
                const u = {};
                if (!this.formData.interview_date_1) {
                    u.interview_date_1 = '面談希望日時（第1希望）を入力してください';
                }
                if (!this.formData.application_reason) {
                    u.application_reason = '応募のきっかけを選択してください';
                }
                if (this.formData.application_reason === 'その他（自由記述）' && !this.formData.application_reason_other?.trim()) {
                    u.application_reason_other = 'その他の内容を記入してください';
                }
                if (!this.formData.visual_disability_status) {
                    u.visual_disability_status = '視覚障害の状況を入力してください';
                }
                if (!this.formData.disability_support_level) {
                    u.disability_support_level = '障害支援区分を選択してください';
                }
                if (!this.formData.daily_life_situation) {
                    u.daily_life_situation = '普段の生活状況を入力してください';
                }
                Object.assign(this.fieldErrors, u);
                if (Object.keys(u).length > 0) {
                    this.scrollToFirstError();
                    return;
                }
            } else if (this.formData.role === 'guide') {
                const g = {};
                if (!this.formData.application_reason) {
                    g.application_reason = '応募理由を入力してください';
                }
                if (!this.formData.goal) {
                    g.goal = '実現したいことを入力してください';
                }
                if (!this.formData.qualifications || this.formData.qualifications.length === 0) {
                    g.qualifications = '保有資格を1件以上選択してください';
                } else if (!this.canSupportOuting() && !this.canSupportHome()) {
                    g.qualifications = '外出支援または自宅支援が可能な資格を選択してください';
                }
                if (!this.formData.available_days || this.formData.available_days.length === 0) {
                    g.available_days = '対応可能日を1つ以上選択してください';
                }
                if (!this.formData.available_times || this.formData.available_times.length === 0) {
                    g.available_times = '対応可能時間帯を1つ以上選択してください';
                }
                Object.assign(this.fieldErrors, g);
                if (Object.keys(g).length > 0) {
                    this.scrollToFirstError();
                    return;
                }
            }
            this.step = 'confirm';
            this.$el.querySelector('.confirm-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        validateKana() {
            const kanaPattern = /^[ァ-ヶー\s]+$/;
            if (this.formData.last_name_kana !== undefined && this.formData.last_name_kana !== null) {
                if (!String(this.formData.last_name_kana).trim()) {
                    this.fieldErrors.last_name_kana = '姓（カナ）を入力してください。';
                } else if (!kanaPattern.test(String(this.formData.last_name_kana))) {
                    this.fieldErrors.last_name_kana = '姓（カナ）は全角カタカナで入力してください。';
                } else {
                    delete this.fieldErrors.last_name_kana;
                }
            }
            if (this.formData.first_name_kana !== undefined && this.formData.first_name_kana !== null) {
                if (!String(this.formData.first_name_kana).trim()) {
                    this.fieldErrors.first_name_kana = '名（カナ）を入力してください。';
                } else if (!kanaPattern.test(String(this.formData.first_name_kana))) {
                    this.fieldErrors.first_name_kana = '名（カナ）は全角カタカナで入力してください。';
                } else {
                    delete this.fieldErrors.first_name_kana;
                }
            }
        },
        validateBirthDate() {
            if (!this.formData.birth_date) {
                this.fieldErrors.birth_date = '生年月日を入力してください。';
                return;
            }
            const d = new Date(this.formData.birth_date);
            if (isNaN(d.getTime())) {
                this.fieldErrors.birth_date = '生年月日は正しい日付を入力してください。';
                return;
            }
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const birth = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            if (birth >= today) {
                this.fieldErrors.birth_date = '生年月日は今日より前の日付を入力してください。';
                return;
            }
            delete this.fieldErrors.birth_date;
        },
        validatePhone() {
            const pattern = /^[\d\-\+\(\)\s]+$/;
            if (this.formData.phone !== undefined && this.formData.phone !== null && String(this.formData.phone).trim()) {
                if (!pattern.test(String(this.formData.phone))) {
                    this.fieldErrors.phone = '電話番号は数字・ハイフン・+()のみで入力してください。';
                } else {
                    delete this.fieldErrors.phone;
                }
            }
        },
        scrollToFirstError() {
            const anchor = document.getElementById('register-errors-anchor');
            if (anchor && (this.error || Object.keys(this.fieldErrors).length > 0)) {
                anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (this.error) {
                const alertEl = document.getElementById('register-error-client');
                if (alertEl) {
                    alertEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                if (Object.keys(this.fieldErrors).length === 0) return;
            }
            const firstErrorId = Object.keys(this.fieldErrors)[0];
            if (firstErrorId) {
                const idMap = {
                    last_name: 'last_name',
                    first_name: 'first_name',
                    last_name_kana: 'last_name_kana',
                    first_name_kana: 'first_name_kana',
                    birth_date: 'birth_date',
                    gender: 'gender',
                    postal_code: 'postal_code',
                    address: 'address',
                    phone: 'phone',
                    email: 'email',
                    email_confirmation: 'email_confirmation',
                    password: 'password',
                    confirmPassword: 'confirmPassword',
                    interview_date_1: 'interview_date_1',
                    interview_date_2: 'interview_date_2',
                    interview_date_3: 'interview_date_3',
                    application_reason: 'application_reason',
                    application_reason_other: 'application_reason_other',
                    visual_disability_status: 'visual_disability_status',
                    disability_support_level: 'disability_support_level',
                    daily_life_situation: 'daily_life_situation',
                    goal: 'goal',
                    available_days: 'register-guide-available-days',
                    available_times: 'register-guide-available-times',
                    qualifications: 'register-guide-qualifications',
                };
                const id = idMap[firstErrorId] || firstErrorId;
                let el = document.getElementById(id);
                if (!el && firstErrorId === 'qualifications') {
                    el = document.getElementById('register-guide-qualifications') || document.querySelector('input[name="qualifications[]"]');
                }
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (typeof el.focus === 'function') {
                        el.focus();
                    }
                }
            }
        },
        submitForm() {
            const form = this.$el.querySelector('form');
            if (!form) {
                this.loading = false;
                return;
            }
            this.loading = true;
            const formData = new FormData(form);
            const action = form.getAttribute('action') || form.action;
            const method = (form.getAttribute('method') || form.method || 'post').toUpperCase();

            fetch(action, {
                method: method,
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                return response.text().then(html => {
                    document.open();
                    document.write(html);
                    document.close();
                });
            })
            .catch(err => {
                console.error('送信エラー:', err);
                this.loading = false;
                alert('送信に失敗しました。もう一度お試しください。');
            });
        },
        validatePostalCode() {
            const pattern = /^\d{3}-\d{4}$/;
            if (!this.formData.postal_code?.trim()) return;
            if (!pattern.test(this.formData.postal_code)) {
                this.fieldErrors.postal_code = '郵便番号は「123-4567」の形式で入力してください（ハイフンを含む7桁）';
            } else {
                delete this.fieldErrors.postal_code;
            }
        },
        validateEmail() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!this.formData.email?.trim()) return; // 必須は validateAndGoToConfirm で設定済み
            if (!emailPattern.test(this.formData.email)) {
                this.fieldErrors.email = 'メールアドレスの形式が正しくありません';
            } else {
                delete this.fieldErrors.email;
            }
            // 確認用メールアドレスとの一致チェック
            if (this.formData.email_confirmation && this.formData.email !== this.formData.email_confirmation) {
                this.fieldErrors.email_confirmation = 'メールアドレスが一致しません';
            } else if (this.fieldErrors.email_confirmation === 'メールアドレスが一致しません') {
                delete this.fieldErrors.email_confirmation;
            }
        },
        validateEmailConfirmation() {
            if (this.formData.email && this.formData.email_confirmation && this.formData.email !== this.formData.email_confirmation) {
                this.fieldErrors.email_confirmation = 'メールアドレスが一致しません。上記で入力したメールアドレスと同じものを入力してください';
            } else {
                delete this.fieldErrors.email_confirmation;
            }
        },
        validatePassword() {
            if (!this.formData.password) return; // 必須は validateAndGoToConfirm で設定済み
            if (this.formData.password.length < 6) {
                this.fieldErrors.password = 'パスワードは6文字以上で入力してください';
            } else {
                delete this.fieldErrors.password;
            }
            // 確認用パスワードとの一致チェック
            if (this.formData.confirmPassword && this.formData.password !== this.formData.confirmPassword) {
                this.fieldErrors.confirmPassword = 'パスワードが一致しません';
            } else if (this.fieldErrors.confirmPassword === 'パスワードが一致しません') {
                delete this.fieldErrors.confirmPassword;
            }
        },
        validatePasswordConfirmation() {
            if (this.formData.password && this.formData.confirmPassword && this.formData.password !== this.formData.confirmPassword) {
                this.fieldErrors.confirmPassword = 'パスワードが一致しません。上記で入力したパスワードと同じものを入力してください';
            } else {
                delete this.fieldErrors.confirmPassword;
            }
        },
        handleSubmit(ev) {
            if (this.step === 'confirm') {
                this.error = '';
                this.fieldErrors = {};
                if (this.formData.role === 'user') {
                    if (!this.formData.application_reason) {
                        ev.preventDefault();
                        this.fieldErrors.application_reason = '応募のきっかけが未選択のため送信できません。入力画面に戻って選択してください。';
                        this.step = 'input';
                        this.$nextTick(() => this.scrollToFirstError());
                        return;
                    }
                    if (this.formData.application_reason === 'その他（自由記述）' && !this.formData.application_reason_other?.trim()) {
                        ev.preventDefault();
                        this.fieldErrors.application_reason_other = '「その他」の内容を入力してください。';
                        this.step = 'input';
                        this.$nextTick(() => this.scrollToFirstError());
                        return;
                    }
                }
                this.loading = true;
                return;
            }
            ev.preventDefault();

            this.error = '';
            this.fieldErrors = {};
            
            // バリデーション実行
            this.validatePostalCode();
            this.validateEmail();
            this.validateEmailConfirmation();
            this.validatePassword();
            this.validatePasswordConfirmation();
            
            // エラーがある場合は送信しない
            if (Object.keys(this.fieldErrors).length > 0) {
                this.$nextTick(() => this.scrollToFirstError());
                return;
            }
            
            // メール確認チェック
            if (this.formData.email !== this.formData.email_confirmation) {
                this.fieldErrors.email_confirmation = 'メールアドレスが一致しません';
                this.$nextTick(() => this.scrollToFirstError());
                return;
            }
            
            // パスワード確認チェック
            if (this.formData.password !== this.formData.confirmPassword) {
                this.fieldErrors.confirmPassword = 'パスワードが一致しません';
                this.$nextTick(() => this.scrollToFirstError());
                return;
            }
            
            if (this.formData.password.length < 6) {
                this.fieldErrors.password = 'パスワードは6文字以上で入力してください';
                this.$nextTick(() => this.scrollToFirstError());
                return;
            }

            this.validateAndGoToConfirm();
        }
    }
}
</script>
@endpush



