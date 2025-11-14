<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadZenginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
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
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'excel_file.required' => 'Excelファイルを選択してください。',
            'excel_file.file' => 'ファイルをアップロードしてください。',
            'excel_file.mimes' => 'Excel ファイル（xlsx, xls, csv）をアップロードしてください。',
            'excel_file.max' => 'ファイルサイズは10MB以下にしてください。',
        ];
    }
}
