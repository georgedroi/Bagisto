<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

it('uploads a picture once, reuses it for the same file, lists and deletes it', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $file = UploadedFile::fake()->image('banner.png', 640, 480);

    $first = $this->post(route('admin.media.assets.store'), ['file' => $file], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('reused', false)
        ->json('data');

    $this->post(route('admin.media.assets.store'), ['file' => $file], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('reused', true)
        ->assertJsonPath('data.id', $first['id']);

    Storage::disk('public')->assertExists($first['path']);

    get(route('admin.media.index', ['search' => 'banner']))->assertOk()->assertSee('banner.png');

    $this->delete(route('admin.media.assets.delete', $first['id']), [], ['Accept' => 'application/json'])->assertOk();

    Storage::disk('public')->assertMissing($first['path']);

    expect(DB::table('media_assets')->where('id', $first['id'])->exists())->toBeFalse();
});

it('refuses a Media Bank upload that is not an allowed picture or video', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $this->post(route('admin.media.assets.store'), ['file' => UploadedFile::fake()->createWithContent('run.php', '<?php echo 1;')], ['Accept' => 'application/json'])->assertUnprocessable();

    expect(Storage::disk('public')->allFiles())->toBe([]);
});
