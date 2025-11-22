<?php

declare(strict_types=1);

use App\Models\ZenginLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // テスト用のストレージディスク設定
    Storage::fake('local');
});

test('履歴一覧画面が表示される', function () {
    // テストデータ作成
    ZenginLog::factory()->count(3)->create();

    $response = $this->get(route('zengin.history'));

    $response->assertStatus(200);
    $response->assertViewIs('zengin.history');
    $response->assertViewHas('logs');
});

test('履歴がない場合は空のメッセージが表示される', function () {
    $response = $this->get(route('zengin.history'));

    $response->assertStatus(200);
    $response->assertSee('履歴がありません');
});

test('履歴一覧が変換日時の降順で表示される', function () {
    $log1 = ZenginLog::create([
        'filename' => 'zengin_20251114_100000.txt',
        'file_path' => 'zengin/zengin_20251114_100000.txt',
        'total_count' => 10,
        'total_amount' => 1000000,
        'created_at' => now()->subDays(2),
    ]);

    $log2 = ZenginLog::create([
        'filename' => 'zengin_20251115_100000.txt',
        'file_path' => 'zengin/zengin_20251115_100000.txt',
        'total_count' => 20,
        'total_amount' => 2000000,
        'created_at' => now()->subDay(),
    ]);

    $response = $this->get(route('zengin.history'));

    $response->assertStatus(200);

    // 新しい順に表示されていることを確認
    $logs = $response->viewData('logs');
    expect($logs->first()->id)->toBe($log2->id);
    expect($logs->last()->id)->toBe($log1->id);
});

test('ファイルが存在する場合はダウンロードできる', function () {
    // ダミーファイルを作成
    $content = "20001ﾐｽﾞﾎｷﾞﾝｺｳ      001ﾄｳｷｮｳｴｲｷﾞｮｳﾌﾞ      11234567ﾃｽﾄﾀﾛｳ                        0000100000                              \r\n";
    $sjisContent = mb_convert_encoding($content, 'SJIS-win', 'UTF-8');

    Storage::disk('local')->put('zengin/test.txt', $sjisContent);

    $log = ZenginLog::create([
        'filename' => 'zengin_test.txt',
        'file_path' => 'zengin/test.txt',
        'total_count' => 1,
        'total_amount' => 100000,
    ]);

    $response = $this->get(route('zengin.download', $log->id));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; charset=shift_jis');
});

test('ファイルが存在しない場合は404エラー', function () {
    $log = ZenginLog::create([
        'filename' => 'zengin_notfound.txt',
        'file_path' => 'zengin/notfound.txt',
        'total_count' => 1,
        'total_amount' => 100000,
    ]);

    $response = $this->get(route('zengin.download', $log->id));

    $response->assertStatus(404);
});

test('存在しないIDでダウンロードしようとすると404エラー', function () {
    $response = $this->get(route('zengin.download', 999));

    $response->assertStatus(404);
});

test('履歴を削除できる', function () {
    // ダミーファイルを作成
    Storage::disk('local')->put('zengin/delete_test.txt', 'test content');

    $log = ZenginLog::create([
        'filename' => 'zengin_delete_test.txt',
        'file_path' => 'zengin/delete_test.txt',
        'total_count' => 1,
        'total_amount' => 100000,
    ]);

    expect(Storage::disk('local')->exists('zengin/delete_test.txt'))->toBeTrue();
    expect(ZenginLog::count())->toBe(1);

    $response = $this->delete(route('zengin.history.destroy', $log->id));

    $response->assertRedirect(route('zengin.history'));
    $response->assertSessionHas('success', '履歴を削除しました。');

    // DBレコードが削除されたことを確認
    expect(ZenginLog::count())->toBe(0);

    // ファイルも削除されたことを確認
    expect(Storage::disk('local')->exists('zengin/delete_test.txt'))->toBeFalse();
});

test('ZenginLogモデルのfileExists()が正しく動作する', function () {
    Storage::disk('local')->put('zengin/exists_test.txt', 'test');

    $log = ZenginLog::create([
        'filename' => 'exists_test.txt',
        'file_path' => 'zengin/exists_test.txt',
        'total_count' => 1,
        'total_amount' => 100000,
    ]);

    expect($log->fileExists())->toBeTrue();

    Storage::disk('local')->delete('zengin/exists_test.txt');

    expect($log->fileExists())->toBeFalse();
});

test('ZenginLogモデルのgetFileContent()が正しく動作する', function () {
    $content = 'test content';
    Storage::disk('local')->put('zengin/content_test.txt', $content);

    $log = ZenginLog::create([
        'filename' => 'content_test.txt',
        'file_path' => 'zengin/content_test.txt',
        'total_count' => 1,
        'total_amount' => 100000,
    ]);

    expect($log->getFileContent())->toBe($content);

    Storage::disk('local')->delete('zengin/content_test.txt');

    expect($log->getFileContent())->toBeNull();
});
