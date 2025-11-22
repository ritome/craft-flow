<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SettlementRequest;
use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 委託精算書コントローラ
 * 
 * Issue #12〜#17: 委託精算書一括発行システム
 * 
 * コントローラは薄く保ち、ビジネスロジックは SettlementService に委譲
 */
class SettlementController extends Controller
{
    /**
     * 精算サービス
     */
    public function __construct(
        private readonly SettlementService $settlementService
    ) {}

    /**
     * 精算トップ画面（アップロード画面）
     * 
     * Issue #12: 精算用Excelデータアップロード機能
     * 
     * @return View
     */
    public function index(): View
    {
        return view('settlements.index');
    }

    /**
     * 精算書生成処理
     * 
     * Issue #12〜#16 統合処理：
     * - #12: Excel アップロード
     * - #13: 委託先別精算データ自動変換
     * - #14: 精算書一括生成（Excel/PDF）
     * - #15: ファイルダウンロード
     * - #16: 履歴保存
     * 
     * @param  SettlementRequest  $request
     * @return RedirectResponse
     */
    public function generate(SettlementRequest $request): RedirectResponse
    {
        try {
            \Log::info('Settlement generation request received', [
                'billing_dates' => [
                    'start' => $request->input('billing_start_date'),
                    'end' => $request->input('billing_end_date'),
                ],
                'customer_file' => $request->file('customer_file')->getClientOriginalName(),
                'sales_file' => $request->file('sales_file')->getClientOriginalName(),
            ]);

            // SettlementService に処理を委譲
            $settlement = $this->settlementService->generateSettlements(
                billingStartDate: $request->input('billing_start_date'),
                billingEndDate: $request->input('billing_end_date'),
                customerFile: $request->file('customer_file'),
                salesFile: $request->file('sales_file')
            );

            return redirect()
                ->route('settlements.history')
                ->with('success', "精算書を生成しました（委託先数: {$settlement->client_count}件）");
        } catch (\Exception $e) {
            \Log::error('精算書生成エラー: '.$e->getMessage());

            return back()
                ->withErrors(['error' => '精算書の生成中にエラーが発生しました: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 精算履歴一覧画面
     * 
     * Issue #17: 過去精算書履歴ダウンロード機能
     * 
     * @return View
     */
    public function history(): View
    {
        $settlements = Settlement::with('details')
            ->orderByDesc('created_at')
            ->paginate(20);

        // 統計情報
        $totalSettlements = Settlement::count();
        $totalClients = Settlement::sum('client_count');
        $totalSalesAmount = Settlement::sum('total_sales_amount');

        return view('settlements.history', compact(
            'settlements',
            'totalSettlements',
            'totalClients',
            'totalSalesAmount'
        ));
    }

    /**
     * Excel ファイルダウンロード
     * 
     * Issue #17: 過去精算書履歴ダウンロード機能
     * 
     * ZIPファイルまたは単一Excelファイルに対応
     * 
     * @param  Settlement  $settlement
     * @return StreamedResponse|RedirectResponse
     */
    public function downloadExcel(Settlement $settlement): StreamedResponse|RedirectResponse
    {
        // リレーションをロード
        $settlement->load('details');
        
        if (! $settlement->hasExcelFile()) {
            return back()->withErrors(['download_error' => 'Excel ファイルが見つかりません。']);
        }

        $content = $settlement->getExcelContent();
        
        // ファイルの拡張子を取得（.zip or .xlsx）
        $extension = pathinfo($settlement->excel_path, PATHINFO_EXTENSION);
        $dateStr = $settlement->billing_start_date->format('Ymd').'-'.$settlement->billing_end_date->format('Ymd');
        
        // ZIPファイルか単一Excelファイルかで拡張子とMIMEタイプを変更
        if ($extension === 'zip') {
            $filename = "settlement_{$dateStr}.zip";
            $mimeType = 'application/zip';
        } else {
            $filename = "settlement_{$dateStr}.xlsx";
            $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    /**
     * PDF ファイルダウンロード
     * 
     * Issue #17: 過去精算書履歴ダウンロード機能
     * 
     * @param  Settlement  $settlement
     * @return StreamedResponse|RedirectResponse
     */
    public function downloadPdf(Settlement $settlement): StreamedResponse|RedirectResponse
    {
        // リレーションをロード
        $settlement->load('details');
        
        if (! $settlement->hasPdfFile()) {
            return back()->withErrors(['download_error' => 'PDF ファイルが見つかりません。']);
        }

        $content = $settlement->getPdfContent();
        $filename = "settlement_{$settlement->billing_start_date->format('Ymd')}-{$settlement->billing_end_date->format('Ymd')}.pdf";

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * 精算履歴削除
     * 
     * @param  Settlement  $settlement
     * @return RedirectResponse
     */
    public function destroy(Settlement $settlement): RedirectResponse
    {
        try {
            $this->settlementService->deleteSettlement($settlement);

            return back()->with('success', '精算履歴を削除しました。');
        } catch (\Exception $e) {
            \Log::error('精算履歴削除エラー: '.$e->getMessage());

            return back()->withErrors(['error' => '削除中にエラーが発生しました。']);
        }
    }
}



