<?php

use function Livewire\Volt\{state, mount}; // ★ rules を use から削除
use App\Models\Reservation;
use App\Models\ExperienceProgram;
use Illuminate\Support\Collection;

// --- フォームと表示のための state を定義 ---
state([
    'reservation' => null,
    'programName' => '',
    'originalValues' => [],
    'programs' => Collection::make(),
]);
state('form', [
    'experience_program_id' => null,
    'reservation_date' => '',
    'reservation_time' => '',
    'customer_name' => '',
    'customer_phone' => '',
    'customer_email' => '',
    'participant_count' => 1,
    'reservation_source' => '',
    'status' => 1,
    'notes' => '',
]);

// --- バリデーションルール ---
// ★★★ この rules(...) ブロック全体を削除します ★★★

// --- コンポーネント初期化処理 ---
mount(function (Reservation $reservation) {
    // ... (この部分は変更ありません) ...
    $this->programs = ExperienceProgram::all(['experience_program_id', 'name']);
    $this->reservation = $reservation;
    $this->originalValues = [
        'programName' => $this->reservation->experienceProgram?->name ?? '不明なプログラム',
        'reservation_date' => $this->reservation->reservation_date?->format('Y-m-d'),
        'reservation_time' => $this->reservation->reservation_time,
        'customer_name' => $this->reservation->customer_name,
        'customer_phone' => $this->reservation->customer_phone,
        'customer_email' => $this->reservation->customer_email,
        'participant_count' => $this->reservation->participant_count,
        'reservation_source' => $this->reservation->reservation_source,
        'status' => $this->reservation->status,
        'notes' => $this->reservation->notes,
    ];
    $this->programName = $this->originalValues['programName'];
    $this->form = [
        'experience_program_id' => $this->reservation->experience_program_id,
        'reservation_date' => $this->originalValues['reservation_date'],
        'reservation_time' => $this->originalValues['reservation_time'],
        'customer_name' => $this->originalValues['customer_name'],
        'customer_phone' => $this->originalValues['customer_phone'],
        'customer_email' => $this->originalValues['customer_email'],
        'participant_count' => $this->originalValues['participant_count'],
        'reservation_source' => $this->originalValues['reservation_source'],
        'status' => $this->originalValues['status'],
        'notes' => $this->originalValues['notes'],
    ];
});

// --- 更新処理 ---
$update = function () {
    // ★★★ ここから修正 ★★★

    // バリデーションルールをこの場で定義する
    $rules = [
        'form.experience_program_id' => 'required|exists:experience_programs,experience_program_id',
        'form.reservation_date' => 'required|date',
        'form.reservation_time' => 'required|date_format:H:i',
        'form.customer_name' => 'required|string|max:255',
        'form.customer_phone' => 'nullable|string|max:20',
        'form.customer_email' => 'nullable|email|max:255',
        'form.participant_count' => 'required|integer|min:1',
        'form.reservation_source' => 'required|string|max:255',
        'form.status' => 'required|integer|in:1,2,3',
        'form.notes' => 'nullable|string',
    ];

    // validate() メソッドに直接ルールを渡す
    $validated = $this->validate($rules);

    // ★★★ ここまで修正 ★★★

    // H:i 形式の時刻に秒を追加して H:i:s 形式に戻す
    $dataToUpdate = $validated['form'];
    $dataToUpdate['reservation_time'] = $dataToUpdate['reservation_time'] . ':00';

    // データベースを更新
    $this->reservation->update($dataToUpdate);

    // フラッシュメッセージをセッションに保存
    session()->flash('success', '予約情報が正常に更新されました。');

    // 詳細ページにリダイレクト
    return redirect()->route('reservations.show', $this->reservation);
};

?>

<div class="bg-gray-100 p-4 sm:p-6 md:p-8 min-h-screen">
    <div class="max-w-3xl mx-auto">

        <!-- 戻るボタン -->
        <a href="{{ route('reservations.index') }}"
            class="mb-6 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
            &larr; 予約カレンダーに戻る
        </a>

        <!-- フォームのコンテナ -->
        <form wire:submit="update">
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="p-6 sm:p-8">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">予約情報の更新</h1>
                    <p class="text-sm text-gray-500 mb-6">内容を編集して、更新ボタンを押してください。</p>

                    <!-- 成功メッセージ -->
                    @if (session()->has('success'))
                        <div class="mb-6 p-4 text-sm text-green-800 bg-green-100 rounded-lg border border-green-300"
                            role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="space-y-6">
                        <!-- プログラム選択 -->
                        <div>
                            <label for="program_id" class="block text-sm font-medium text-gray-700">プログラム</label>
                            <span class="text-xs text-gray-500">元の値: {{ $originalValues['programName'] }}</span>
                            <select id="program_id" wire:model="form.experience_program_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach ($programs as $program)
                                    <option value="{{ $program->experience_program_id }}">
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form.experience_program_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約日 -->
                        <div>
                            <label for="reservation_date" class="block text-sm font-medium text-gray-700">予約日</label>
                            <span class="text-xs text-gray-500">元の値: {{ $originalValues['reservation_date'] }}</span>
                            <input type="date" wire:model="form.reservation_date" id="reservation_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('form.reservation_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約時刻 -->
                        <div>
                            <label for="reservation_time" class="block text-sm font-medium text-gray-700">予約時刻</label>
                            <span class="text-xs text-gray-500">元の値: {{ $originalValues['reservation_time'] }}</span>
                            <input type="time" wire:model="form.reservation_time" id="reservation_time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('form.reservation_time')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約者名 -->
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700">予約者名</label>
                            <span class="text-xs text-gray-500">元の値: {{ $originalValues['customer_name'] }}</span>
                            <input type="text" wire:model="form.customer_name" id="customer_name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('form.customer_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 電話番号 -->
                        <div>
                            <label for="customer_phone" class="block text-sm font-medium text-gray-700">電話番号</label>
                            <span class="text-xs text-gray-500">元の値: {{ $originalValues['customer_phone'] }}</span>
                            <input type="text" wire:model="form.customer_phone" id="customer_phone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('form.customer_phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- メールアドレス -->
                        <div>
                            <label for="customer_email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                            <span class="text-xs text-gray-500">元の値: {{ $originalValues['customer_email'] }}</span>
                            <input type="email" wire:model="form.customer_email" id="customer_email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('form.customer_email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 参加人数 -->
                        <div>
                            <label for="participant_count" class="block text-sm font-medium text-gray-700">参加人数</label>
                            <span class="text-xs text-gray-500">元の値: {{ $originalValues['participant_count'] }}</span>
                            <input type="number" wire:model="form.participant_count" id="participant_count"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                min="1">
                            @error('form.participant_count')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 予約経路 -->
                        <!-- 予約経路 -->
                        <div>
                            <label for="reservation_source" class="block text-sm font-medium text-gray-700">予約経路</label>

                            {{-- 元の値を表示 --}}
                            <span class="text-xs text-gray-500">
                                元の値: {{ $originalValues['reservation_source'] }}
                            </span>

                            <select id="reservation_source" wire:model="form.reservation_source"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="hp">自社HP</option>
                                <option value="jalan">じゃらん</option>
                                <option value="asoview">アソビュー</option>
                                <option value="self_call">自社の電話</option>
                                <option value="center_call">センターの電話</option>
                                <option value="other">その他</option>
                            </select>

                            @error('form.reservation_source')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ステータス -->
                        <!-- ステータス -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">予約状態</label>

                            {{-- 元の値を分かりやすく表示 --}}
                            <span class="text-xs text-gray-500">
                                元の値:
                                @switch($originalValues['status'])
                                    @case(1)
                                        予約済
                                    @break

                                    @case(2)
                                        キャンセル済
                                    @break

                                    @case(3)
                                        完了
                                    @break

                                    @default
                                        不明 ({{ $originalValues['status'] }})
                                @endswitch
                            </span>

                            <select id="status" wire:model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="1">1: 予約済</option>
                                <option value="2">2: キャンセル済</option>
                                <option value="3">3: 完了</option>
                            </select>

                            @error('form.status')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 備考 -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">備考</label>
                            <textarea wire:model="form.notes" id="notes" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('form.notes')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- フッター部分 -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="submit"
                        class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        更新する
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>
