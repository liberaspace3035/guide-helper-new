# ngrok セットアップガイド

このアプリケーションはngrokを使用して外部からアクセスできるように設定されています。

## セットアップ手順

### 1. フロントエンドのみngrokを使用する場合（推奨）

フロントエンドのみをngrokで公開し、バックエンドAPIはViteのproxy経由でアクセスします。

#### 手順:

1. **フロントエンドサーバーを起動**
   ```bash
   cd frontend
   npm run dev
   ```

2. **ngrokでポート5173を公開**
   ```bash
   ngrok http 5173
   ```

3. **バックエンドのCORS設定（オプション）**
   `backend/.env`ファイルにngrok URLを追加（自動検出も可能）:
   ```env
   NGROK_FRONTEND_URL=https://your-ngrok-url.ngrok-free.app
   ```

4. **アクセス**
   - ngrokが提供するURL（例: `https://xxxx-xx-xx-xx-xx.ngrok-free.app`）にアクセス
   - APIリクエストは自動的にVite proxy経由で`http://localhost:3001`に転送されます

### 2. フロントエンドとバックエンドの両方をngrokで公開する場合

#### 手順:

1. **バックエンドサーバーを起動**
   ```bash
   cd backend
   npm run dev
   ```

2. **バックエンドをngrokで公開**
   ```bash
   ngrok http 3001
   ```
   バックエンドのngrok URLをメモ（例: `https://backend-xxxx.ngrok-free.app`）

3. **フロントエンドの環境変数を設定**
   `frontend/.env`ファイルを作成:
   ```env
   VITE_API_URL=https://backend-xxxx.ngrok-free.app/api
   ```

4. **フロントエンドサーバーを起動**
   ```bash
   cd frontend
   npm run dev
   ```

5. **フロントエンドをngrokで公開**
   ```bash
   ngrok http 5173
   ```

6. **バックエンドのCORS設定**
   `backend/.env`ファイルにフロントエンドのngrok URLを追加:
   ```env
   NGROK_FRONTEND_URL=https://frontend-xxxx.ngrok-free.app
   ```

## 自動機能

- **CORS自動許可**: バックエンドは開発環境でngrokドメイン（`ngrok-free.app`、`ngrok.io`）を自動的に許可します
- **API URL自動検出**: フロントエンドがngrok URLでアクセスされている場合、自動的にVite proxy経由でAPIを呼び出します

## 注意事項

- ngrokの無料プランでは、URLが再起動のたびに変更されます
- 本番環境では適切なドメインとSSL証明書を使用してください
- セキュリティのため、本番環境ではngrok URLを明示的に許可リストに追加することを推奨します

