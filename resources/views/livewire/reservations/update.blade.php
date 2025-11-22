<?php

use App\Models\ExperienceProgram;
use App\Models\Reservation;
use function Livewire\Volt\{state, rules, mount};

// 予約IDをURLから受け取る
state(['reservationId', 'reservation' => null, 'programs' => Collection::make()]);

// フォームの状態
state([
    'form' => [
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
    ],
]);

// バリデーションルール (登録とほぼ同じ)
rules([
    'form.experience_program_id' => 'required|exists:experience_programs,experience_program_id',
    'form.reservation_date' => 'required|date',
    'form.reservation_time' => 'required|date_format:H:i',
    'form.customer_name' => 'required|string|max:255',
    'form.customer_phone' => 'nullable|string|max:20',
    'form.customer_email' => 'nullable|email|max:255',
    'form.participant_count' => 'required|integer|min:1',
    'form.reservation_source' => 'required|string|max:255',
    'form.status' => 'required|integer|in:1,2,3', // 1:予約済み, 2:キャンセル, 3:完了
    'form.notes' => 'nullable|string',
]);

mount(function ($reservationId) {
    // 予約IDを使って既存データをロード
    $this->reservation = Reservation::findOrFail($reservationId);
    $this->programs = ExperienceProgram::select('experience_program_id', 'name')->get();

    // フォームにデータを設定
    $this->form = [
        'experience_program_id' => $this->reservation->experience_program_id,
        'reservation_date' => $this->reservation->reservation_date,
        'reservation_time' => $this->reservation->reservation_time,
        'customer_name' => $this->reservation->customer_name,
        'customer_phone' => $this->reservation->customer_phone,
        'customer_email' => $this->reservation->customer_email,
        'participant_count' => $this->reservation->participant_count,
        'reservation_source' => $this->reservation->reservation_source,
        'status' => $this->reservation->status,
        'notes' => $this->reservation->notes,
    ];
});

$update = function () {
    $validated = $this->validate();

    // ダブルブッキングチェック (今回は更新のため、自分自身は除外してチェック)
    $isBooked = Reservation::where('experience_program_id', $validated['form']['experience_program_id'])
        ->where('reservation_date', $validated['form']['reservation_date'])
        ->where('reservation_time', $validated['form']['reservation_time'])
        ->where('status', 1)
        ->where('id', '!=', $this->reservation->id) // 自分自身を除く
        ->exists();

    if ($isBooked) {
        session()->flash('error', 'この日時とプログラムは他の予約と競合しています。');
        return;
    }

    // 予約を更新
    $this->reservation->update($validated['form']);

    session()->flash('success', '予約情報が正常に更新されました。');
    // リストページへリダイレクトするなど
    // return redirect()->route('reservations.index');
};

?>
<div>
    <h2 class="text-2xl font-bold mb-6">予約情報 編集 (#{{ $reservation->id }})</h2>

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if ($reservation)
        <form wire:submit="update" class="space-y-6 bg-white p-6 rounded-lg shadow-md">

            <!-- プログラムID, 予約経路, 予約状態 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="program" class="block text-sm font-medium text-gray-700">体験プログラム名 <span
                            class="text-red-500">*</span></label>
                    <select id="program" wire:model="form.experience_program_id"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach ($programs as $program)
                            <option value="{{ $program->experience_program_id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="source" class="block text-sm font-medium text-gray-700">予約経路 <span
                            class="text-red-500">*</span></label>
                    <select id="source" wire:model="form.reservation_source"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="jalan">じゃらん</option>
                        <option value="asoview">アソビュー</option>
                        <option value="hp">自社HP</option>
                        <option value="self_call">自社の電話</option>
                        <option value="center_call">センターの電話</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">予約状態 <span
                            class="text-red-500">*</span></label>
                    <select id="status" wire:model="form.status"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="1">1: 予約済み</option>
                        <option value="2">2: キャンセル</option>
                        <option value="3">3: 完了</option>
                    </select>
                </div>
            </div>

            <!-- 日時と人数 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 日時、人数などのフィールドが続く -->
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">予約日 <span
                            class="text-red-500">*</span></label>
                    <input type="date" id="date" wire:model="form.reservation_date"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="time" class="block text-sm font-medium text-gray-700">予約時刻 <span
                            class="text-red-500">*</span></label>
                    <input type="time" id="time" wire:model="form.reservation_time"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="participants" class="block text-sm font-medium text-gray-700">参加人数 <span
                            class="text-red-500">*</span></label>
                    <input type="number" id="participants" wire:model="form.participant_count" min="1"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- 予約者情報 (省略) -->

            <!-- 備考 -->
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">備考</label>
                <textarea id="notes" wire:model="form.notes" rows="3"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    予約を更新する
                </button>
            </div>
        </form>
    @else
        <p class="text-center text-red-500">予約が見つかりません。</p>
    @endif
</div>
