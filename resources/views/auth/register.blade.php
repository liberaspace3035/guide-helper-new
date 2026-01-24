@extends('layouts.app')

@section('content')
<div class="register-container" x-data="registerForm()">
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

        <form method="POST" action="{{ route('register') }}" @submit.prevent="handleSubmit" aria-label="ユーザー登録フォーム">
            @csrf
            <div x-show="error" class="error-message" role="alert" aria-live="polite" x-text="error"></div>
            @if($errors->any())
                <div class="error-message" role="alert" aria-live="polite">
                    {{ $errors->first() }}
                </div>
            @endif
            
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
                                />
                                <label for="last_name" class="input-label">姓</label>
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
                                />
                                <label for="first_name" class="input-label">名</label>
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
                                    title="全角カタカナで入力してください"
                                    required
                                    aria-required="true"
                                />
                                <label for="last_name_kana" class="input-label">姓（カナ）</label>
                            </div>
                            <div class="name-input-item">
                                <input
                                    type="text"
                                    id="first_name_kana"
                                    name="first_name_kana"
                                    x-model="formData.first_name_kana"
                                    placeholder="メイ（全角カタカナで入力）"
                                    pattern="[ァ-ヶー\s]*"
                                    title="全角カタカナで入力してください"
                                    required
                                    aria-required="true"
                                />
                                <label for="first_name_kana" class="input-label">名（カナ）</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="birth_date">生年月日 <span class="required">*</span></label>
                            <input
                                type="date"
                                id="birth_date"
                                name="birth_date"
                                x-model="formData.birth_date"
                                required
                                aria-required="true"
                                aria-label="生年月日"
                            />
                        </div>
                        <div class="form-group form-group-half">
                            <label for="gender">性別 <span class="required">*</span></label>
                            <select
                                id="gender"
                                name="gender"
                                x-model="formData.gender"
                                required
                                aria-required="true"
                            >
                                <option value="">選択してください</option>
                                <option value="male">男性</option>
                                <option value="female">女性</option>
                                <option value="other">その他</option>
                                <option value="prefer_not_to_say">回答しない</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="postal_code">郵便番号 <span class="required">*</span></label>
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
                            />
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
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- 連絡先とアカウント情報セクション -->
                <div class="form-section form-section-half">
                    <!-- 連絡先セクション -->
                    <div class="form-subsection">
                        <h2 class="section-title">連絡先</h2>
                        <div class="form-group">
                            <label for="email">メールアドレス <span class="required">*</span></label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                x-model="formData.email"
                                required
                                autocomplete="email"
                                aria-required="true"
                            />
                        </div>
                        <div class="form-group">
                            <label for="email_confirmation">メールアドレス（確認） <span class="required">*</span></label>
                            <input
                                type="email"
                                id="email_confirmation"
                                name="email_confirmation"
                                x-model="formData.email_confirmation"
                                required
                                autocomplete="email"
                                aria-required="true"
                            />
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
                            />
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
                            <label for="password">パスワード <span class="required">*</span></label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                x-model="formData.password"
                                required
                                minlength="6"
                                autocomplete="new-password"
                                aria-required="true"
                            />
                            <small>6文字以上で入力してください</small>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">パスワード（確認） <span class="required">*</span></label>
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                x-model="formData.confirmPassword"
                                required
                                autocomplete="new-password"
                                aria-required="true"
                            />
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
                                />
                            </div>
                            <div class="form-group form-group-third">
                                <label for="interview_date_3" class="sub-label">第3希望</label>
                                <input
                                    type="datetime-local"
                                    id="interview_date_3"
                                    name="interview_date_3"
                                    x-model="formData.interview_date_3"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="application_reason">応募のきっかけ <span class="required">*</span></label>
                        <textarea
                            id="application_reason"
                            name="application_reason"
                            x-model="formData.application_reason"
                            rows="4"
                            required
                            aria-required="true"
                            placeholder="このサービスを知ったきっかけや応募理由を記入してください"
                        ></textarea>
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
                        <input
                            type="text"
                            id="disability_support_level"
                            name="disability_support_level"
                            x-model="formData.disability_support_level"
                            required
                            aria-required="true"
                            placeholder="例: 1, 2, 3, 4, 5, 6"
                        />
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
                                                required
                                                aria-required="true"
                                            />
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-remove"
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
            
            <button
                type="submit"
                class="btn-primary btn-block"
                :disabled="loading"
                aria-label="登録ボタン"
            >
                <span x-show="!loading">登録</span>
                <span x-show="loading">登録中...</span>
            </button>
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
            visual_disability_status: '',
            disability_support_level: '',
            daily_life_situation: '',
            // ガイド用
            goal: '',
            qualifications: [{ name: '', obtained_date: '' }],
            preferred_work_hours: ''
        },
        error: '',
        loading: false,
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
        handleSubmit() {
            this.error = '';
            
            // メール確認チェック
            if (this.formData.email !== this.formData.email_confirmation) {
                this.error = 'メールアドレスが一致しません';
                return;
            }
            
            // パスワード確認チェック
            if (this.formData.password !== this.formData.confirmPassword) {
                this.error = 'パスワードが一致しません';
                return;
            }
            
            if (this.formData.password.length < 6) {
                this.error = 'パスワードは6文字以上である必要があります';
                return;
            }

            // ロール別の必須項目チェック
            if (this.formData.role === 'user') {
                if (!this.formData.interview_date_1) {
                    this.error = '面談希望日時（第1希望）を入力してください';
                    return;
                }
                if (!this.formData.application_reason) {
                    this.error = '応募のきっかけを入力してください';
                    return;
                }
                if (!this.formData.visual_disability_status) {
                    this.error = '視覚障害の状況を入力してください';
                    return;
                }
                if (!this.formData.disability_support_level) {
                    this.error = '障害支援区分を入力してください';
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
            
            this.loading = true;
            this.$el.submit();
        }
    }
}
</script>
@endpush



