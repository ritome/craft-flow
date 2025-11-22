<?php

use App\Models\ExperienceProgram;
use App\Models\Reservation;
use Illuminate\Support\Collection;
use function Livewire\Volt\{state, rules, mount};

// 初期状態とバリデーションルールを設定
state([
    'programs' => Collection::make(),
    'form' => [
        'experience_program_id' => null,
        'reservation_date' => date('Y-m-d'),
        'reservation_time' => '10:00',
        'customer_name' => '',
        'customer_phone' => '',
        'customer_email' => '',
        'participant_count' => 1,
        'reservation_source' => 'self_call', // Default to phone booking
        'notes' => '',
    ],
]);

// バリデーションルール
rules([
    'form.experience_program_id' => 'required|exists:experience_programs,experience_program_id',
    'form.reservation_date' => 'required|date',
    'form.reservation_time' => 'required|date_format:H:i',
    'form.customer_name' => 'required|string|max:255',
    'form.customer_phone' => 'nullable|string|max:20',
    'form.customer_email' => 'nullable|email|max:255',
    'form.participant_count' => 'required|integer|min:1',
    'form.reservation_source' => 'required|string|max:255',
    'form.notes' => 'nullable|string',
]);

// マウント時にプログラムリストをロード
mount(function () {
    $this->programs = ExperienceProgram::select('experience_program_id', 'name')->get();
});

$save = function () {
    $validated = $this->validate();

    // Simple double-booking check
    $isBooked = Reservation::where('experience_program_id', $validated['form']['experience_program_id'])
        ->where('reservation_date', $validated['form']['reservation_date'])
        ->where('reservation_time', $validated['form']['reservation_time'])
        ->where('status', 1) // 1: Booked
        ->exists();

    if ($isBooked) {
        session()->flash('error', 'この日時とプログラムはすでに予約が入っている可能性があります。');
        return;
    }

    // Save reservation (status: 1 = 予約済み)
    Reservation::create($validated['form'] + ['status' => 1]);

    session()->flash('success', '予約が正常に登録されました。');

    // Reset form
    $this->form = [
        'experience_program_id' => null,
        'reservation_date' => date('Y-m-d'),
        'reservation_time' => '10:00',
        'customer_name' => '',
        'customer_phone' => '',
        'customer_email' => '',
        'participant_count' => 1,
        'reservation_source' => 'self_call',
        'notes' => '',
    ];
};

?>
<div class="p-4 sm:p-8">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">新規予約の手動登録</h2>

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

    <form wire:submit="save" class="space-y-6 bg-white p-6 rounded-lg shadow-xl">

        <!-- Program ID & Source -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="program" class="block text-sm font-medium text-gray-700">体験プログラム名 <span
                        class="text-red-500">*</span></label>
                <select id="program" wire:model.live="form.experience_program_id"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">--- 選択してください ---</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->experience_program_id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
                @error('form.experience_program_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="source" class="block text-sm font-medium text-gray-700">予約経路 <span
                        class="text-red-500">*</span></label>
                <select id="source" wire:model.live="form.reservation_source"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="jalan">じゃらん</option>
                    <option value="asoview">アソビュー</option>
                    <option value="hp">自社HP</option>
                    <option value="self_call">自社の電話</option>
                    <option value="center_call">センターの電話</option>
                </select>
                @error('form.reservation_source')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Date, Time, Participants -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">予約日 <span
                        class="text-red-500">*</span></label>
                <input type="date" id="date" wire:model.live="form.reservation_date"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.reservation_date')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label for="time" class="block text-sm font-medium text-gray-700">予約時刻 <span
                        class="text-red-500">*</span></label>
                <input type="time" id="time" wire:model.live="form.reservation_time"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.reservation_time')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label for="participants" class="block text-sm font-medium text-gray-700">参加人数 <span
                        class="text-red-500">*</span></label>
                <input type="number" id="participants" wire:model.live="form.participant_count" min="1"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.participant_count')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Customer Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">予約者名 <span
                        class="text-red-500">*</span></label>
                <input type="text" id="name" wire:model.live="form.customer_name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.customer_name')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">電話番号</label>
                <input type="text" id="phone" wire:model.live="form.customer_phone"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.customer_phone')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                <input type="email" id="email" wire:model.live="form.customer_email"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.customer_email')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">備考</label>
            <textarea id="notes" wire:model.live="form.notes" rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            @error('form.notes')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                予約を登録
            </button>
        </div>
    </form>
</div>
