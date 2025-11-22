<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 精算書生成リクエストバリデーション
 *
 * Issue #12: 精算用Excelデータアップロード機能
 */
class SettlementRequest extends FormRequest
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
            'billing_start_date' => [
                'required',
                'date',
                'before_or_equal:billing_end_date',
            ],
            'billing_end_date' => [
                'required',
                'date',
                'after_or_equal:billing_start_date',
            ],
            'customer_file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // 10MB
            ],
            'sales_file' => [
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
            'billing_start_date.required' => '請求開始日を入力してください。',
            'billing_start_date.date' => '請求開始日は有効な日付形式で入力してください。',
            'billing_start_date.before_or_equal' => '請求開始日は請求終了日以前の日付を入力してください。',

            'billing_end_date.required' => '請求終了日を入力してください。',
            'billing_end_date.date' => '請求終了日は有効な日付形式で入力してください。',
            'billing_end_date.after_or_equal' => '請求終了日は請求開始日以降の日付を入力してください。',

            'customer_file.required' => '顧客マスタファイルを選択してください。',
            'customer_file.file' => '有効なファイルを選択してください。',
            'customer_file.mimes' => '顧客マスタはExcel形式（xlsx, xls）またはCSV形式のファイルを選択してください。',
            'customer_file.max' => '顧客マスタのファイルサイズは10MB以下にしてください。',

            'sales_file.required' => '売上データファイルを選択してください。',
            'sales_file.file' => '有効なファイルを選択してください。',
            'sales_file.mimes' => '売上データはExcel形式（xlsx, xls）またはCSV形式のファイルを選択してください。',
            'sales_file.max' => '売上データのファイルサイズは10MB以下にしてください。',
        ];
    }

    /**
     * バリデーション属性名
     */
    public function attributes(): array
    {
        return [
            'billing_start_date' => '請求開始日',
            'billing_end_date' => '請求終了日',
            'customer_file' => '顧客マスタ',
            'sales_file' => '売上データ',
        ];
    }

    /**
     * より詳細なバリデーション
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // 請求期間が未来日でないことを確認
            $billingEndDate = $this->input('billing_end_date');
            if ($billingEndDate && \Carbon\Carbon::parse($billingEndDate)->isFuture()) {
                $validator->errors()->add(
                    'billing_end_date',
                    '請求終了日に未来の日付は指定できません。'
                );
            }

            // 請求期間が3ヶ月以上でないことを確認（データ量制限）
            $start = $this->input('billing_start_date');
            $end = $this->input('billing_end_date');

            if ($start && $end) {
                $startDate = \Carbon\Carbon::parse($start);
                $endDate = \Carbon\Carbon::parse($end);

                if ($startDate->diffInMonths($endDate) > 3) {
                    $validator->errors()->add(
                        'billing_end_date',
                        '請求期間は3ヶ月以内で指定してください。'
                    );
                }
            }

            // ファイルが実際にアップロードされているか確認
            if ($this->hasFile('customer_file') && ! $this->file('customer_file')->isValid()) {
                $validator->errors()->add(
                    'customer_file',
                    '顧客マスタファイルのアップロードに失敗しました。'
                );
            }

            if ($this->hasFile('sales_file') && ! $this->file('sales_file')->isValid()) {
                $validator->errors()->add(
                    'sales_file',
                    '売上データファイルのアップロードに失敗しました。'
                );
            }
        });
    }
}
