#!/bin/bash

# 全銀フォーマットファイルの検証スクリプト
# 使い方: bash check_zengin_file.sh /path/to/zengin_file.txt

if [ -z "$1" ]; then
    echo "使い方: bash check_zengin_file.sh <全銀ファイルのパス>"
    exit 1
fi

FILE="$1"

if [ ! -f "$FILE" ]; then
    echo "❌ ファイルが見つかりません: $FILE"
    exit 1
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📄 全銀フォーマットファイル検証"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "ファイル名: $(basename "$FILE")"
echo "ファイルサイズ: $(stat -f%z "$FILE") バイト"
echo ""

# エンコーディング検出
echo "🔍 エンコーディング検出:"
ENCODING=$(file -b --mime-encoding "$FILE")
echo "  検出結果: $ENCODING"

if [[ "$ENCODING" == *"iso-8859"* ]] || [[ "$ENCODING" == *"unknown"* ]]; then
    echo "  ✅ Shift-JISの可能性が高い（正常）"
else
    echo "  ⚠️  UTF-8ではない（期待通り）"
fi
echo ""

# 改行コード確認
echo "🔍 改行コード確認:"
if file "$FILE" | grep -q "CRLF"; then
    echo "  ✅ CRLF (\\r\\n) - Windows形式（正常）"
elif file "$FILE" | grep -q "CR"; then
    echo "  ❌ CR (\\r) のみ - 古いMac形式"
else
    # hexdumpで確認
    if hexdump -C "$FILE" | grep -q "0d 0a"; then
        echo "  ✅ CRLF (\\r\\n) 確認（正常）"
    elif hexdump -C "$FILE" | grep -q "0a"; then
        echo "  ❌ LF (\\n) のみ - Unix形式"
    else
        echo "  ⚠️  改行なし"
    fi
fi
echo ""

# 各行のバイト長チェック
echo "🔍 各行のバイト長チェック:"
LINE_NUM=1
ERROR_COUNT=0
while IFS= read -r line; do
    # CRLF を削除してバイト長を計算
    LINE_BYTES=$(echo -n "$line" | wc -c | tr -d ' ')
    
    if [ "$LINE_BYTES" -eq 120 ]; then
        if [ $LINE_NUM -le 3 ]; then
            echo "  ✅ 行$LINE_NUM: ${LINE_BYTES}バイト（正常）"
        fi
    else
        echo "  ❌ 行$LINE_NUM: ${LINE_BYTES}バイト（期待値: 120バイト）"
        ERROR_COUNT=$((ERROR_COUNT + 1))
    fi
    
    LINE_NUM=$((LINE_NUM + 1))
done < <(tr -d '\r' < "$FILE")

TOTAL_LINES=$((LINE_NUM - 1))
echo ""
echo "  総行数: $TOTAL_LINES"
echo "  エラー行数: $ERROR_COUNT"
echo ""

# UTF-8に変換して内容プレビュー（最初の3行）
echo "🔍 内容プレビュー（最初の3行・UTF-8変換）:"
iconv -f SJIS-win -t UTF-8 "$FILE" 2>/dev/null | head -n 3 | while IFS= read -r line; do
    echo "  $line"
done
echo ""

# 結果サマリー
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERROR_COUNT -eq 0 ]; then
    echo "✅ 全銀フォーマット検証: 合格"
    echo "   このファイルは銀行システムに提出可能です"
else
    echo "❌ 全銀フォーマット検証: 不合格"
    echo "   ${ERROR_COUNT}行にエラーがあります"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

