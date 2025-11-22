<?php

declare(strict_types=1);

namespace App\Http\Controllers\Zengin;

use App\Http\Controllers\Controller;
use App\Models\ZenginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 全銀フォーマット変換履歴コントローラ
 */
class HistoryController extends Controller
{
    /**
     * 変換履歴一覧を表示
     */
    public function index(): View
    {
        $logs = ZenginLog::orderByDesc('created_at')->paginate(20);

        // 統計情報
        $totalConversions = ZenginLog::count();
        $totalRecords = ZenginLog::sum('total_count');
        $totalAmount = ZenginLog::sum('total_amount');

        return view('zengin.history', compact('logs', 'totalConversions', 'totalRecords', 'totalAmount'));
    }

    /**
     * 履歴ファイルをダウンロード
     */
    public function download(ZenginLog $log): StreamedResponse|RedirectResponse
    {
        if (! $log->fileExists()) {
            return back()->withErrors(['download_error' => 'ファイルが見つかりません。']);
        }

        $content = $log->getFileContent();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $log->filename, [
            'Content-Type' => 'text/plain; charset=shift_jis',
            'Content-Disposition' => 'attachment; filename="'.$log->filename.'"',
        ]);
    }

    /**
     * 履歴を削除
     */
    public function destroy(ZenginLog $log): RedirectResponse
    {
        if ($log->fileExists()) {
            Storage::disk('local')->delete($log->file_path);
        }

        $log->delete();

        return back()->with('success', '履歴とファイルを削除しました。');
    }
}



