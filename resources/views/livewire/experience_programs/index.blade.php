<?php

use function Livewire\Volt\{state, on};
use App\Models\ExperienceProgram;
use Illuminate\Database\Eloquent\Collection;

// プログラムのリストをstateとして保持
state(['experience_programs' => fn() => ExperienceProgram::orderBy('experience_program_id')->get()]);

// 新規作成ページへ遷移するアクション
$create = fn() => redirect()->route('experience_programs.create');

// 削除成功のメッセージを受け取るリスナー
on([
    'program-deleted' => function () {
        // 成功メッセージをセッションにフラッシュ
        session()->flash('message', 'プログラムが正常に削除されました。');
        // ページをリフレッシュしてリストを更新
        return $this->redirect(route('experience_programs.index'), navigate: true);
    },
]);

?>

<div class="p-4 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- ヘッダー -->
        <div class="flex items-center justify-between mb-6">
            <!-- 左側：タイトルとダッシュボードへのリンク -->
            <div>
                <h1 class="text-3xl font-bold text-gray-800">プログラム管理</h1>
                <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline">
                    &larr; ダッシュボードに戻る
                </a>
            </div>

            <!-- 右側：新規登録ボタン -->
            <button wire:click="create"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                新規プログラムを登録
            </button>
        </div>

        <!-- 成功メッセージ -->
        @if (session()->has('message'))
            <div class="mb-6 p-4 text-sm text-green-800 bg-green-100 rounded-lg border border-green-300" role="alert">
                {{ session('message') }}
            </div>
        @endif

        <!-- プログラム一覧テーブル -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            プログラム名</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">所要時間
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">料金
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最大人数
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">操作</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($experience_programs as $program)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $program->experience_program_id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-semibold">
                                {{ $program->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $program->duration }} 分
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">&yen;
                                {{ number_format($program->price) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $program->capacity }} 名
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('experience_programs.show', $program) }}"
                                    class="text-indigo-600 hover:text-indigo-900">詳細</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                登録されている体験プログラムはまだありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
