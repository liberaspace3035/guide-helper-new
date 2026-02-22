#!/bin/bash
set -e

# プロジェクトルートに移動（Railway 等で CWD が不定な場合に備える）
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR/.."

echo "⏰ Starting Laravel scheduler (every 60s)..."

# 1分ごとに schedule:run を実行（実行完了を待ってから sleep）
while true; do
    php artisan schedule:run --verbose --no-interaction
    sleep 60
done


