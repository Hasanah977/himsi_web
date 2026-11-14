<?php

use App\Models\PublicContent;
use App\Models\User;
use App\Support\Appearance;
use App\Support\PublicImage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

it('lets super admin replace all three backgrounds and preserves them on text edits', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    $this->withoutVite();
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $settings = PublicContent::create(['section' => 'settings', 'slug' => 'site', 'payload' => config('superadmin.defaults'), 'position' => 0, 'is_published' => true]);
    $data = [];
    foreach (config('superadmin.sections.settings.fields') as $key => $field) {
        if ($field[1] !== 'image') {
            $value = $settings->payload[$key] ?? ($field[1] === 'number' ? 0 : 'HIMSI');
            $data[$key] = is_array($value) ? implode("\n", $value) : $value;
        }
    }
    $uploads = [];
    foreach (['sidebar_background', 'maintenance_background', 'auth_background'] as $key) {
        $uploads[$key] = UploadedFile::fake()->image($key.'.png', 120, 160);
    }
    $url = route('admin.content.update', ['settings', $settings]);
    $this->actingAs($admin)->put($url, ['data' => $data, 'position' => 0, 'uploads' => $uploads])->assertSessionHasNoErrors();
    $payload = $settings->fresh()->payload;
    foreach (array_keys($uploads) as $key) {
        expect(Appearance::background($key))->toBe(PublicContent::imageUrl($payload[$key]));
    }
    $this->get(route('administration.notifications'))->assertOk()->assertSee(PublicContent::imageUrl($payload['sidebar_background']), false);
    auth()->logout();
    $this->get(route('login'))->assertOk()->assertSee(PublicContent::imageUrl($payload['auth_background']), false);
    expect(view('maintenance-public')->render())->toContain(PublicContent::imageUrl($payload['maintenance_background']));
    $this->actingAs($admin)->put($url, ['data' => $data, 'position' => 0])->assertSessionHasNoErrors();
    expect($settings->fresh()->payload['sidebar_background'])->toBe($payload['sidebar_background']);
});

it('uses the default background when the appearance manifest is missing or damaged', function (): void {
    Storage::fake('local');
    expect(Appearance::background('maintenance_background'))->toBe(asset('images/himsi/sidebar-background.webp'));
    Storage::disk('local')->put('appearance.json', 'invalid json');
    expect(Appearance::background('sidebar_background'))->toBe(asset('images/himsi/sidebar-background.webp'));
});

it('stores uploaded public images as real WebP and renders their URL', function (string $extension): void {
    Storage::fake('public');
    $this->withoutVite();
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

    $this->actingAs($admin)->post(route('admin.content.store', 'gallery'), [
        'data' => ['title' => 'Kegiatan uji', 'description' => 'Dokumentasi kegiatan'],
        'position' => 0, 'is_published' => 1,
        'uploads' => ['image' => UploadedFile::fake()->image('photo.'.$extension, 240, 160)],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $content = PublicContent::where('section', 'gallery')->where('payload->title', 'Kegiatan uji')->firstOrFail();
    $path = Str::after($content->payload['image'], 'storage/');
    Storage::disk('public')->assertExists($path);
    expect($path)->toEndWith('.webp');
    $size = getimagesize(Storage::disk('public')->path($path));
    expect([$size[0], $size[1], $size['mime']])->toBe([240, 160, 'image/webp']);
    $this->get('/')->assertOk()->assertSee(PublicContent::imageUrl($content->payload['image']), false);
    $this->get('/storage/'.$path)->assertOk()->assertHeader('Content-Type', 'image/webp');
})->with(['jpeg', 'png', 'webp']);

it('replaces a public image on update and preserves it on a text-only edit', function (): void {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $content = PublicContent::create(['section' => 'gallery', 'payload' => ['title' => 'Awal', 'description' => 'Awal', 'image' => 'storage/himsi/baseline.jpg'], 'position' => 0, 'is_published' => true]);
    Storage::disk('public')->put('himsi/baseline.jpg', 'baseline');
    $data = ['data' => ['title' => 'Baru', 'description' => 'Baru'], 'position' => 0, 'is_published' => 1];

    $this->actingAs($admin)->put(route('admin.content.update', ['gallery', $content]), $data + ['uploads' => ['image' => UploadedFile::fake()->image('photo.jpg')]])->assertSessionHasNoErrors();

    $image = $content->fresh()->payload['image'];
    expect($image)->toEndWith('.webp');
    Storage::disk('public')->assertExists('himsi/baseline.jpg');
    $this->put(route('admin.content.update', ['gallery', $content]), $data)->assertSessionHasNoErrors();
    expect($content->fresh()->payload['image'])->toBe($image);
});

it('preserves PNG transparency when converting to WebP', function (): void {
    Storage::fake('public');
    $image = imagecreatetruecolor(20, 10);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 10, 20, 30, 127));
    ob_start();
    imagepng($image);
    $file = UploadedFile::fake()->createWithContent('transparent.png', ob_get_clean());

    $path = PublicImage::store($file, 'image');

    $converted = imagecreatefromwebp(Storage::disk('public')->path($path));
    expect(imagecolorsforindex($converted, imagecolorat($converted, 0, 0))['alpha'])->toBe(127);
});

it('does not recompress an existing WebP upload', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('photo.webp');
    $original = file_get_contents($file->getPathname());

    $path = PublicImage::store($file, 'image');

    expect(Storage::disk('public')->get($path))->toBe($original);
});

it('rejects invalid image content without storing a file', function (): void {
    Storage::fake('public');

    expect(fn () => PublicImage::store(UploadedFile::fake()->createWithContent('broken.jpg', 'not an image'), 'image'))
        ->toThrow(ValidationException::class);

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('keeps the displayed orientation of a camera JPEG', function (): void {
    Storage::fake('public');
    $source = UploadedFile::fake()->image('camera.jpg', 40, 20);
    $jpeg = file_get_contents($source->getPathname());
    $exif = "Exif\0\0".'II'.pack('vV', 42, 8).pack('v', 1)
        .pack('vvVV', 0x0112, 3, 1, 6).pack('V', 0);
    $file = UploadedFile::fake()->createWithContent('camera.jpg', substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2));

    $path = PublicImage::store($file, 'image');

    $size = getimagesize(Storage::disk('public')->path($path));
    expect([$size[0], $size[1]])->toBe([20, 40]);
});

it('rejects excessive pixel dimensions before decoding', function (): void {
    Storage::fake('public');
    $source = UploadedFile::fake()->image('large.png', 10, 10);
    $png = file_get_contents($source->getPathname());
    $file = UploadedFile::fake()->createWithContent('large.png', substr_replace($png, pack('NN', 5000, 5000), 16, 8));

    expect(fn () => PublicImage::store($file, 'image'))
        ->toThrow(ValidationException::class, 'Resolusi gambar maksimal 16 megapiksel.');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});
