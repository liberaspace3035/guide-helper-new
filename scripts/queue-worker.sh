#!/bin/bash
set -e

echo "🔄 Starting queue worker..."

# キューワーカーの起動
# --tries: ジョブの最大試行回数
# --timeout: ジョブのタイムアウト（秒）
# --max-time: ワーカーの最大実行時間（秒、Railwayの制限に合わせて調整）
exec php artisan queue:work \
    --queue=default \
    --tries=3 \
    --timeout=90 \
    --max-time=3600 \
    --sleep=3 \
    --max-jobs=1000

