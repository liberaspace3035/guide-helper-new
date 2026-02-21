@extends('layouts.app')

@section('content')
<div class="register-container" x-data="registerForm()" x-init="init()">
    <div class="register-card">
        <h1>新規登録</h1>
        <!-- 審査に関する注意書き -->
        <div class="registration-notice">
            <div class="notice-icon">⚠</div>
            <div class="notice-content">
                <p class="notice-text">
                    利用には審査があります。ユーザーは「利用契約書」、ガイドは「入社手続」の実施後、運営者による承認を経てご利用いただけます。
                </p>
                <p class="notice-text">
                    登録後に運営からメールでご連絡いたします。
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" @submit="handleSubmit($event)" aria-label="ユーザー登録フォーム">
            @csrf
            <div x-show="error" class="error-message" id="register-error-client" role="alert" aria-live="polite" aria-atomic="true" x-text="error"></div>
            @if($errors->any())
                <div class="error-message" id="register-error-summary" role="alert" aria-live="polite" aria-atomic="true">
                    <p><span class="sr-only">入力内容に誤りがあります。該当する項目は以下のとおりです。</span></p>
                    <ul class="error-summary-list" aria-label="誤りがある項目">
                        @foreach($errors->keys() as $key)
                            @php
                                $attrLabel = __("validation.attributes.{$key}");
                                if (str_starts_with($attrLabel, 'validation.')) {
                                    $attrLabel = preg_replace('/\.\d+\./', ' ', $key);
                                    $attrLabel = str_replace('_', ' ', $attrLabel);
                                }
                            @endphp
                            <li><span class="error-summary-field">{{ $attrLabel }}</span>：{{ $errors->first($key) }}</li>
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
                            <label for="birth_date">生年月日 <span class="required">*</span></label>
                            <span id="birth_date-description" class="sr-only">今日より前の日付を入力してください。18歳以上である必要があります。</span>
                            <input
                                type="date"
                                id="birth_date"
                                name="birth_date"
                                x-model="formData.birth_date"
                                required
                                aria-required="true"
                                aria-label="生年月日"
                                :aria-invalid="!!fieldErrors.birth_date"
                                :aria-describedby="fieldErrors.birth_date ? 'birth_date-description birth_date-error' : 'birth_date-description'"
                            />
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
                        <label for="disability_support_level">障害支援区分 <span class="required">*</span></label>
                        <select
                            id="disability_support_level"
                            name="disability_support_level"
                            x-model="formData.disability_support_level"
                            required
                            aria-required="true"
                            aria-describedby="disability_support_level-desc"
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
                        <span id="disability_support_level-desc" class="sr-only">１から６、またはなしから選択</span>
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

                    <div class="form-group">
                        <label>保有資格（最大3件） <span class="required">*</span></label>
                        <div class="qualifications-list">
                            <template x-for="(qual, index) in formData.qualifications" :key="index">
                                <div class="qualification-item">
                                    <div class="form-row">
                                        <div class="form-group form-group-half">
                                            <label :for="`qual_name_${index}`" class="sub-label">資格名 <span class="required">*</span></label>
                                            <input
                                                type="text"
                                                :id="`qual_name_${index}`"
                                                :name="`qualifications[${index}][name]`"
                                                x-model="qual.name"
                                                required
                                                aria-required="true"
                                                placeholder="例: 同行援護従業者養成研修修了"
                                            />
                                        </div>
                                        <div class="form-group form-group-half">
                                            <label :for="`qual_date_${index}`" class="sub-label">取得年月日 <span class="required">*</span></label>
                                            <input
                                                type="date"
                                                :id="`qual_date_${index}`"
                                                :name="`qualifications[${index}][obtained_date]`"
                                                x-model="qual.obtained_date"
                                                :max="maxQualificationDate"
                                                required
                                                aria-required="true"
                                            />
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-danger btn-sm"
                                        @click="removeQualification(index)"
                                        x-show="formData.qualifications.length > 1"
                                        aria-label="資格を削除"
                                    >
                                        削除
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button
                            type="button"
                            class="btn-secondary btn-sm"
                            @click="addQualification()"
                            x-show="formData.qualifications.length < 3"
                            aria-label="資格を追加"
                        >
                            + 資格を追加
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="preferred_work_hours">希望勤務時間 <span class="required">*</span></label>
                        <textarea
                            id="preferred_work_hours"
                            name="preferred_work_hours"
                            x-model="formData.preferred_work_hours"
                            rows="3"
                            required
                            aria-required="true"
                            placeholder="例: 平日 9:00-17:00、土日 10:00-16:00"
                        ></textarea>
                    </div>
                </div>
            </template>

            </div>
            <!-- /入力画面 -->

            <!-- 入力内容確認画面 -->
            <div x-show="step === 'confirm'" x-cloak class="confirm-section" role="region" aria-label="入力内容の確認">
                <div class="confirm-header">
                    <h2 class="confirm-title">入力内容の確認</h2>
                    <p class="confirm-description">以下の内容に誤りがなければ「送信する」を押してください。修正する場合は「修正する」から入力画面に戻れます。</p>
                </div>

                <div class="confirm-block">
                    <h3 class="confirm-block-title">基本情報</h3>
                    <div class="confirm-list">
                        <div class="confirm-row"><span class="confirm-label">登録種別</span><span class="confirm-value" x-text="formData.role === 'user' ? '利用者' : 'ガイド'"></span></div>
                        <div class="confirm-row"><span class="confirm-label">お名前</span><span class="confirm-value" x-text="(formData.last_name || '') + ' ' + (formData.first_name || '')"></span></div>
                        <div class="confirm-row"><span class="confirm-label">お名前（カナ）</span><span class="confirm-value" x-text="(formData.last_name_kana || '') + ' ' + (formData.first_name_kana || '')"></span></div>
                        <div class="confirm-row"><span class="confirm-label">生年月日</span><span class="confirm-value" x-text="formData.birth_date || '—'"></span></div>
                        <div class="confirm-row"><span class="confirm-label">性別</span><span class="confirm-value" x-text="getGenderLabel(formData.gender)"></span></div>
                    </div>
                </div>

                <div class="confirm-block">
                    <h3 class="confirm-block-title">連絡先</h3>
                    <div class="confirm-list">
                        <div class="confirm-row"><span class="confirm-label">郵便番号</span><span class="confirm-value" x-text="formData.postal_code || '—'"></span></div>
                        <div class="confirm-row"><span class="confirm-label">住所</span><span class="confirm-value" x-text="formData.address || '—'"></span></div>
                        <div class="confirm-row"><span class="confirm-label">電話番号</span><span class="confirm-value" x-text="formData.phone || '—'"></span></div>
                        <div class="confirm-row"><span class="confirm-label">メールアドレス</span><span class="confirm-value" x-text="formData.email || '—'"></span></div>
                        <div class="confirm-row"><span class="confirm-label">パスワード</span><span class="confirm-value">********</span></div>
                    </div>
                </div>

                <template x-if="formData.role === 'user'">
                    <div class="confirm-block">
                        <h3 class="confirm-block-title">利用者情報</h3>
                        <div class="confirm-list">
                            <div class="confirm-row"><span class="confirm-label">面談希望日時（第1希望）</span><span class="confirm-value" x-text="formatConfirmDateTime(formData.interview_date_1)"></span></div>
                            <div class="confirm-row"><span class="confirm-label">面談希望日時（第2希望）</span><span class="confirm-value" x-text="formatConfirmDateTime(formData.interview_date_2) || '—'"></span></div>
                            <div class="confirm-row"><span class="confirm-label">面談希望日時（第3希望）</span><span class="confirm-value" x-text="formatConfirmDateTime(formData.interview_date_3) || '—'"></span></div>
                            <div class="confirm-row"><span class="confirm-label">応募のきっかけ</span><span class="confirm-value" x-text="getApplicationReasonValue() || '—'"></span></div>
                            <div class="confirm-row"><span class="confirm-label">視覚障害の状況</span><span class="confirm-value confirm-text-block" x-text="formData.visual_disability_status || '—'"></span></div>
                            <div class="confirm-row"><span class="confirm-label">障害支援区分</span><span class="confirm-value" x-text="formData.disability_support_level || '—'"></span></div>
                            <div class="confirm-row"><span class="confirm-label">普段の生活状況</span><span class="confirm-value confirm-text-block" x-text="formData.daily_life_situation || '—'"></span></div>
                        </div>
                    </div>
                </template>

                <template x-if="formData.role === 'guide'">
                    <div class="confirm-block">
                        <h3 class="confirm-block-title">ガイド情報</h3>
                        <div class="confirm-list">
                            <div class="confirm-row"><span class="confirm-label">応募理由</span><span class="confirm-value confirm-text-block" x-text="formData.application_reason || '—'"></span></div>
                            <div class="confirm-row"><span class="confirm-label">実現したいこと</span><span class="confirm-value confirm-text-block" x-text="formData.goal || '—'"></span></div>
                            <div class="confirm-row"><span class="confirm-label">保有資格</span><span class="confirm-value">
                                <template x-for="(qual, index) in formData.qualifications" :key="index">
                                    <div class="confirm-qual-item" x-text="(qual.name || '') + (qual.obtained_date ? '（' + qual.obtained_date + '）' : '')"></div>
                                </template>
                            </span></div>
                            <div class="confirm-row"><span class="confirm-label">希望勤務時間</span><span class="confirm-value confirm-text-block" x-text="formData.preferred_work_hours || '—'"></span></div>
                        </div>
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

            <div x-show="step === 'input'">
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
@endpush

@push('scripts')
<script>
function registerForm() {
    return {
        formData: {
            email: '',
            email_confirmation: '',
            password: '',
            confirmPassword: '',
            last_name: '',
            first_name: '',
            last_name_kana: '',
            first_name_kana: '',
            birth_date: '',
            gender: '',
            postal_code: '',
            address: '',
            phone: '',
            role: 'user',
            // ユーザー用
            interview_date_1: '',
            interview_date_2: '',
            interview_date_3: '',
            application_reason: '',
            application_reason_other: '',
            visual_disability_status: '',
            disability_support_level: '',
            daily_life_situation: '',
            // ガイド用
            goal: '',
            qualifications: [{ name: '', obtained_date: '' }],
            preferred_work_hours: ''
        },
        error: '',
        fieldErrors: {},
        loading: false,
        step: 'input',
        init() {
            // サーバーサイドのエラーをfieldErrorsに設定
            @if($errors->has('postal_code'))
                this.fieldErrors.postal_code = '{{ addslashes($errors->first('postal_code')) }}';
            @endif
            @if($errors->has('email'))
                this.fieldErrors.email = '{{ addslashes($errors->first('email')) }}';
            @endif
            @if($errors->has('email_confirmation'))
                this.fieldErrors.email_confirmation = '{{ addslashes($errors->first('email_confirmation')) }}';
            @endif
            @if($errors->has('password'))
                this.fieldErrors.password = '{{ addslashes($errors->first('password')) }}';
            @endif
            @if($errors->has('confirmPassword'))
                this.fieldErrors.confirmPassword = '{{ addslashes($errors->first('confirmPassword')) }}';
            @endif
            @if($errors->has('last_name_kana'))
                this.fieldErrors.last_name_kana = '{{ addslashes($errors->first('last_name_kana')) }}';
            @endif
            @if($errors->has('first_name_kana'))
                this.fieldErrors.first_name_kana = '{{ addslashes($errors->first('first_name_kana')) }}';
            @endif
            @if($errors->has('birth_date'))
                this.fieldErrors.birth_date = '{{ addslashes($errors->first('birth_date')) }}';
            @endif
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
        addQualification() {
            if (this.formData.qualifications.length < 3) {
                this.formData.qualifications.push({ name: '', obtained_date: '' });
            }
        },
        removeQualification(index) {
            if (this.formData.qualifications.length > 1) {
                this.formData.qualifications.splice(index, 1);
            }
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
            try {
                const d = new Date(str);
                if (isNaN(d.getTime())) return str;
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                const h = String(d.getHours()).padStart(2, '0');
                const min = String(d.getMinutes()).padStart(2, '0');
                return y + '/' + m + '/' + day + ' ' + h + ':' + min;
            } catch (e) { return str; }
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
                if (!this.formData.interview_date_1) { this.error = '面談希望日時（第1希望）を入力してください'; this.scrollToFirstError(); return; }
                if (!this.formData.application_reason) { this.error = '応募のきっかけを選択してください'; this.scrollToFirstError(); return; }
                if (this.formData.application_reason === 'その他（自由記述）' && !this.formData.application_reason_other?.trim()) { this.error = 'その他の内容を記入してください'; this.scrollToFirstError(); return; }
                if (!this.formData.visual_disability_status) { this.error = '視覚障害の状況を入力してください'; this.scrollToFirstError(); return; }
                if (!this.formData.disability_support_level) { this.error = '障害支援区分を選択してください'; this.scrollToFirstError(); return; }
                if (!this.formData.daily_life_situation) { this.error = '普段の生活状況を入力してください'; this.scrollToFirstError(); return; }
            } else if (this.formData.role === 'guide') {
                if (!this.formData.application_reason) { this.error = '応募理由を入力してください'; this.scrollToFirstError(); return; }
                if (!this.formData.goal) { this.error = '実現したいことを入力してください'; this.scrollToFirstError(); return; }
                if (!this.formData.qualifications || this.formData.qualifications.length === 0) { this.error = '保有資格を1件以上入力してください'; this.scrollToFirstError(); return; }
                for (let i = 0; i < this.formData.qualifications.length; i++) {
                    if (!this.formData.qualifications[i].name || !this.formData.qualifications[i].obtained_date) {
                        this.error = '保有資格の資格名と取得年月日を入力してください';
                        this.scrollToFirstError();
                        return;
                    }
                }
                if (!this.formData.preferred_work_hours) { this.error = '希望勤務時間を入力してください'; this.scrollToFirstError(); return; }
            }
            this.step = 'confirm';
            this.$el.querySelector('.confirm-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        validateKana() {
            const kanaPattern = /^[ァ-ヶー\s]+$/;
            if (this.formData.last_name_kana !== undefined && this.formData.last_name_kana !== null) {
                if (!String(this.formData.last_name_kana).trim()) {
                    this.fieldErrors.last_name_kana = '【名前の読み・姓（カナ）】を入力してください。';
                } else if (!kanaPattern.test(String(this.formData.last_name_kana))) {
                    this.fieldErrors.last_name_kana = '【名前の読み・姓（カナ）】全角カタカナで入力してください。';
                } else {
                    delete this.fieldErrors.last_name_kana;
                }
            }
            if (this.formData.first_name_kana !== undefined && this.formData.first_name_kana !== null) {
                if (!String(this.formData.first_name_kana).trim()) {
                    this.fieldErrors.first_name_kana = '【名前の読み・名（カナ）】を入力してください。';
                } else if (!kanaPattern.test(String(this.formData.first_name_kana))) {
                    this.fieldErrors.first_name_kana = '【名前の読み・名（カナ）】全角カタカナで入力してください。';
                } else {
                    delete this.fieldErrors.first_name_kana;
                }
            }
        },
        validateBirthDate() {
            if (!this.formData.birth_date) {
                this.fieldErrors.birth_date = '【生年月日】を入力してください。';
                return;
            }
            const d = new Date(this.formData.birth_date);
            if (isNaN(d.getTime())) {
                this.fieldErrors.birth_date = '【生年月日】は正しい日付を入力してください。';
                return;
            }
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const birth = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            if (birth >= today) {
                this.fieldErrors.birth_date = '【生年月日】未来の日付は入力できません。今日より前の日付を入力してください。';
                return;
            }
            const age = Math.floor((today - birth) / (365.25 * 24 * 60 * 60 * 1000));
            if (age < 18) {
                this.fieldErrors.birth_date = '【生年月日】から計算した年齢は18歳以上である必要があります。';
                return;
            }
            if (age > 120) {
                this.fieldErrors.birth_date = '【生年月日】から計算した年齢は120歳以下である必要があります。';
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
            // 一般エラー（this.error）のみの場合はそのメッセージへスクロール（読み上げは role="alert" aria-live で行われる）
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
                    confirmPassword: 'confirmPassword'
                };
                const id = idMap[firstErrorId] || firstErrorId;
                const el = document.getElementById(id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.focus();
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
            // 確認画面からの送信: そのまま送信させる（prevent しない）
            if (this.step === 'confirm') {
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
                return;
            }
            
            // メール確認チェック
            if (this.formData.email !== this.formData.email_confirmation) {
                this.fieldErrors.email_confirmation = 'メールアドレスが一致しません';
                return;
            }
            
            // パスワード確認チェック
            if (this.formData.password !== this.formData.confirmPassword) {
                this.fieldErrors.confirmPassword = 'パスワードが一致しません';
                return;
            }
            
            if (this.formData.password.length < 6) {
                this.fieldErrors.password = 'パスワードは6文字以上で入力してください';
                return;
            }

            // ロール別の必須項目チェック
            if (this.formData.role === 'user') {
                if (!this.formData.interview_date_1) {
                    this.error = '面談希望日時（第1希望）を入力してください';
                    return;
                }
                if (!this.formData.application_reason) {
                    this.error = '応募のきっかけを選択してください';
                    return;
                }
                if (this.formData.application_reason === 'その他（自由記述）' && !this.formData.application_reason_other?.trim()) {
                    this.error = 'その他の内容を記入してください';
                    return;
                }
                if (!this.formData.visual_disability_status) {
                    this.error = '視覚障害の状況を入力してください';
                    return;
                }
                if (!this.formData.disability_support_level) {
                    this.error = '障害支援区分を選択してください';
                    return;
                }
                if (!this.formData.daily_life_situation) {
                    this.error = '普段の生活状況を入力してください';
                    return;
                }
            } else if (this.formData.role === 'guide') {
                if (!this.formData.application_reason) {
                    this.error = '応募理由を入力してください';
                    return;
                }
                if (!this.formData.goal) {
                    this.error = '実現したいことを入力してください';
                    return;
                }
                if (!this.formData.qualifications || this.formData.qualifications.length === 0) {
                    this.error = '保有資格を1件以上入力してください';
                    return;
                }
                // 資格の必須項目チェック
                for (let i = 0; i < this.formData.qualifications.length; i++) {
                    if (!this.formData.qualifications[i].name || !this.formData.qualifications[i].obtained_date) {
                        this.error = '保有資格の資格名と取得年月日を入力してください';
                        return;
                    }
                }
                if (!this.formData.preferred_work_hours) {
                    this.error = '希望勤務時間を入力してください';
                    return;
                }
            }

            this.validateAndGoToConfirm();
        }
    }
}
</script>
@endpush



