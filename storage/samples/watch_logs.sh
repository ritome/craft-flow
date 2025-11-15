#!/bin/bash

# ログをリアルタイムで監視するスクリプト

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📝 Laravelログをリアルタイム監視中..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Ctrl+C で終了"
echo ""

cd /Users/akazawayoshimi/camp/craft-frow
tail -f storage/logs/laravel.log

