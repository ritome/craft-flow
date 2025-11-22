<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PDFインポートリクエスト
 */
class PdfImportRequest extends FormRequest
{
    /**
     * リクエストの認可
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pdf_files' => ['required', 'array', 'min:1', 'max:20'],
            'pdf_files.*' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 最大10MB
        ];
    }

    /**
     * バリデーションエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pdf_files.required' => 'PDFファイルを選択してください。',
            'pdf_files.array' => 'PDFファイルは配列形式である必要があります。',
            'pdf_files.min' => '少なくとも1つのPDFファイルを選択してください。',
            'pdf_files.max' => 'アップロードできるPDFファイルは最大20個までです。',
            'pdf_files.*.required' => 'PDFファイルが必要です。',
            'pdf_files.*.file' => '有効なファイルをアップロードしてください。',
            'pdf_files.*.mimes' => 'PDFファイル形式のみアップロード可能です。',
            'pdf_files.*.max' => 'ファイルサイズは10MB以下にしてください。',
        ];
    }
}
