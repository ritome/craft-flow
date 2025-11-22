<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 全銀フォーマット変換用ファイルアップロードリクエスト
 */
class UploadZenginRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを行う権限があるかを判断
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'excel_file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // 10MB
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'excel_file.required' => 'ファイルを選択してください。',
            'excel_file.file' => '有効なファイルを選択してください。',
            'excel_file.mimes' => 'Excel形式（xlsx, xls）またはCSV形式のファイルを選択してください。',
            'excel_file.max' => 'ファイルサイズは10MB以下にしてください。',
        ];
    }
}
