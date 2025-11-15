<?php

use function Livewire\Volt\{state, mount, rules};
use App\Models\Reservation;
use App\Models\ExperienceProgram;
use Illuminate\Support\Collection;

// --- フォームと表示のための state を定義 ---

// 予約インスタンスと関連データ
state([
    'reservation' => null,
    'programName' => '',
    'originalValues' => [], // 元の値を保持する配列
]);

// フォーム入力とバインドするデータ
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
rules([
    'form.experience_program_id' => 'required|exists:experience_programs,experience_program_id',
    'form.reservation_date' => 'required|date',
    'form.reservation_time' => 'required|date_format:H:i', // H:i 形式を要求
    'form.customer_name' => 'required|string|max:255',
    'form.customer_phone' => 'nullable|string|max:20',
    'form.customer_email' => 'nullable|email|max:255',
    'form.participant_count' => 'required|integer|min:1',
    'form.reservation_source' => 'required|string|max:255',
    'form.status' => 'required|integer|in:1,2,3',
    'form.notes' => 'nullable|string',
]);

// --- コンポーネント初期化処理 ---
mount(function (Reservation $reservation) {
    // ルートモデルバインディングで取得したインスタンスをstateにセット

    
    $this->reservation = $reservation;

    // --- ステップ1: まず「元の値」をモデルから直接、安全に生成する ---
    $this->originalValues = [
        // Null安全演算子(?->)を使い、関連プログラムが無くてもエラーにならないようにする
        'programName' => $this->reservation->experienceProgram?->name ?? '不明なプログラム',
        // 日付も同様にNull安全演算子で処理
        'reservation_date' => $this->reservation->reservation_date?->format('Y-m-d'),
        // アクセサが適用されるので、こちらは直接代入でOK
        'reservation_time' => $this->reservation->reservation_time,
        'customer_name' => $this->reservation->customer_name,
        'customer_phone' => $this->reservation->customer_phone,
        'customer_email' => $this->reservation->customer_email,
        'participant_count' => $this->reservation->participant_count,
        'reservation_source' => $this->reservation->reservation_source,
        'status' => $this->reservation->status,
        'notes' => $this->reservation->notes,
    ];

    // --- ステップ2: 生成した「元の値」を元に、フォームと表示用のプロパティをセットする ---

    // プログラム名をビューで直接使えるようにセット
    $this->programName = $this->originalValues['programName'];

    // フォームの初期値をセット
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
    // バリデーションを実行し、検証済みのデータを取得
    $validated = $this->validate();

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

<div>
    <a href="{{ route('reservations.index') }}">戻る</a>
    <h1>更新</h1>

    @if (session()->has('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif

    <form wire:submit="update">
        <p>
            <label for="program_name">プログラム</label><br>
            <!-- プログラム名は表示のみ -->
            <input type="text" id="program_name" value="{{ $programName }}" disabled>
            @error('form.experience_program_id')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="reservation_date">予約日</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['reservation_date'] }}</span><br>
            <input type="date" wire:model="form.reservation_date" id="reservation_date">
            @error('form.reservation_date')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="reservation_time">予約時刻</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['reservation_time'] }}</span><br>
            <input type="time" wire:model="form.reservation_time" id="reservation_time">
            @error('form.reservation_time')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="customer_name">予約者名</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['customer_name'] }}</span><br>
            <input type="text" wire:model="form.customer_name" id="customer_name">
            @error('form.customer_name')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="customer_phone">電話番号</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['customer_phone'] }}</span><br>
            <input type="text" wire:model="form.customer_phone" id="customer_phone">
            @error('form.customer_phone')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="customer_email">メールアドレス</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['customer_email'] }}</span><br>
            <input type="email" wire:model="form.customer_email" id="customer_email">
            @error('form.customer_email')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="participant_count">参加人数</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['participant_count'] }}</span><br>
            <input type="number" wire:model="form.participant_count" id="participant_count">
            @error('form.participant_count')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="reservation_source">予約経路</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['reservation_source'] }}</span><br>
            <input type="text" wire:model="form.reservation_source" id="reservation_source">
            @error('form.reservation_source')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="status">ステータス</label><br>
            <span style="font-size: 0.8em; color: gray;">元の値: {{ $originalValues['status'] }}</span><br>
            <input type="text" wire:model="form.status" id="status">
            @error('form.status')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <p>
            <label for="notes">備考</label><br>
            <textarea wire:model="form.notes" id="notes" rows="3"></textarea>
            @error('form.notes')
                <span class="error">({{ $message }})</span>
            @enderror
        </p>

        <button type="submit">更新</button>
    </form>
</div>
