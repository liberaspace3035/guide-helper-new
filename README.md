# ガイドヘルパーマッチングアプリケーション

視覚障害者とガイドヘルパーのマッチングアプリケーションです。

## 技術スタック

- **フロントエンド**: React + Vite（既存）、Blade + Alpine.js（Laravel移行版）
- **バックエンド**: Laravel 11
- **データベース**: PostgreSQL
- **認証**: Session（Laravel標準認証）

## クイックスタート

詳細なセットアップ手順は`docs/SETUP.md`を参照してください。

### 最小限の動作確認手順

```bash
# 1. Composer依存関係のインストール
composer install

# 2. 環境設定
cp .env.example .env
php artisan key:generate

# 3. データベースマイグレーション
php artisan migrate

# 4. サーバー起動
php artisan serve
```

ブラウザで`http://localhost:8000`にアクセスしてください。

## 機能

### ユーザー側機能

- ユーザー登録・ログイン
- プロフィール編集
- 依頼作成（音声入力対応）
- 依頼一覧・詳細確認
- マッチング確認
- チャット機能
- 報告書確認・承認

### ガイド側機能

- ガイド登録・ログイン
- プロフィール編集（対応可能エリア・日時設定）
- 依頼一覧閲覧・承諾
- チャット機能
- 報告書作成・提出

### 管理者側機能

- 管理ログイン
- 依頼一覧・承諾一覧
- マッチング自動/手動切替
- 手動判定（OK/NG）
- 報告書一覧
- CSV出力
- ユーザー・ガイド管理
- お知らせ管理

## アクセシビリティ

- ARIA属性の適用
- スクリーンリーダー対応
- 適切なラベリング
- キーボードナビゲーション対応

## PWA対応

- Service Worker実装
- マニフェストファイル設定
- オフライン対応（最小限）

## セキュリティ

- セッション認証（Laravel標準）
- CSRF保護
- ロールベースアクセス制御
- 住所情報のマスキング処理
- パスワードハッシュ化

## ドキュメント

- `docs/SETUP.md` - セットアップガイド
- `docs/IMPLEMENTATION_STATUS.md` - 実装完了状況
- `docs/API_DESIGN.md` - API設計ドキュメント
- `docs/TROUBLESHOOTING.md` - トラブルシューティング
- `docs/ADMIN_FEATURES.md` - 管理者機能ドキュメント
- `docs/DATABASE.md` - データベースドキュメント
- `docs/EMAIL_TESTING.md` - メール通知の確認方法
- `docs/EMAIL_CONFIRMATION.md` - メール通知の動作確認（テストコマンドと実際の動作の関係）
- `docs/DEVELOPMENT_NOTES.md` - 開発ノート
- `docs/TESTING_CHECKLIST.md` - テストチェックリスト
- `docs/NGROK_SETUP.md` - ngrokセットアップガイド

## ライセンス

ISC
