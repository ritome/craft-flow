<?php

declare(strict_types=1);

namespace App\Http\Controllers\Zengin;

use App\Http\Controllers\Controller;
use App\Models\ZenginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 全銀フォーマット変換履歴コントローラ
 *
 * 変換履歴の一覧表示と過去ファイルの再ダウンロード機能を提供
 */
class HistoryController extends Controller
{
    /**
     * 変換履歴一覧を表示
     *
     * @return View
     */
    public function index(): View
    {
        // 変換日時の降順で履歴を取得
        $logs = ZenginLog::orderBy('created_at', 'desc')
            ->paginate(20);

        return view('zengin.history', [
            'logs' => $logs,
        ]);
    }

    /**
     * 指定IDの全銀ファイルをダウンロード
     *
     * @param  int  $id  ZenginLog ID
     * @return BinaryFileResponse
     */
    public function download(int $id): BinaryFileResponse
    {
        $log = ZenginLog::findOrFail($id);

        // ファイルの存在確認
        if (! $log->fileExists()) {
            abort(404, 'ファイルが見つかりません。削除された可能性があります。');
        }

        // ファイルの完全パスを取得
        $filePath = $log->full_path;

        // ダウンロード
        return response()->download($filePath, $log->filename, [
            'Content-Type' => 'text/plain; charset=shift_jis',
        ]);
    }

    /**
     * 指定IDの履歴を削除
     *
     * @param  int  $id  ZenginLog ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id)
    {
        $log = ZenginLog::findOrFail($id);

        // ファイルが存在する場合は削除
        if ($log->fileExists()) {
            \Storage::disk('local')->delete($log->file_path);
        }

        // レコード削除
        $log->delete();

        return redirect()
            ->route('zengin.history')
            ->with('success', '履歴を削除しました。');
    }
}

