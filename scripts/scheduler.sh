#!/bin/bash
set -e

echo "⏰ Starting Laravel scheduler..."

# スケジューラを1分ごとに実行するループ
while true; do
    php artisan schedule:run --verbose --no-interaction &
    sleep 60
done


