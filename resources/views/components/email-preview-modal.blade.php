<div x-data="{
    show: false,
    htmlContent: '',
    updateIframe() {
        if (this.$refs.iframe) {
            // iframeのsrcdocにHTMLを安全にセットする
            this.$refs.iframe.srcdoc = this.htmlContent;
        }
    }
}"
    x-on:show-email-preview.window="
        show = true;
        htmlContent = $event.detail.html;
        // DOMが更新された次のフレームでiframeの内容を更新する
        $nextTick(() => updateIframe());
    "
    x-show="show" x-cloak class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4"
    x-transition>
    <!-- モーダル本体 -->
    <div @click.away="show = false"
        class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-full max-h-[90vh] flex flex-col">
        <!-- ヘッダー -->
        <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
            <h3 class="text-lg font-semibold text-gray-800">メールプレビュー</h3>
            <button @click="show = false" class="text-gray-500 hover:text-gray-800 text-2xl leading-none">&times;</button>
        </div>

        <!-- メール本文 -->
        <div class="p-1 flex-grow overflow-y-auto">
            <iframe x-ref="iframe" class="w-full h-full border-0"></iframe>
        </div>

        <!-- フッター -->
        <div class="p-4 border-t bg-gray-50 text-right rounded-b-lg">
            <a href="{{ route('reservations.index') }}"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                予約一覧に戻る
            </a>
        </div>
    </div>
</div>
