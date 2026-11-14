<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicImage
{
    public static function store(UploadedFile $file, string $field): string
    {
        if ($file->getMimeType() === 'image/webp') {
            $path = $file->store('himsi', 'public');
            abort_unless($path, 500, 'Gagal menyimpan gambar.');

            return $path;
        }

        if (! function_exists('imagewebp')) {
            throw ValidationException::withMessages([$field => 'Server belum mendukung konversi WebP.']);
        }

        $size = @getimagesize($file->getPathname());
        if (! $size || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw ValidationException::withMessages([$field => 'Gambar JPEG/PNG tidak dapat dibaca.']);
        }
        if ($size[0] * $size[1] > 16000000) {
            throw ValidationException::withMessages([$field => 'Resolusi gambar maksimal 16 megapiksel.']);
        }

        $image = $size[2] === IMAGETYPE_JPEG
            ? @imagecreatefromjpeg($file->getPathname())
            : @imagecreatefrompng($file->getPathname());

        if ($image === false) {
            throw ValidationException::withMessages([$field => 'Gambar rusak atau tidak dapat dikonversi.']);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        if ($size[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $metadata = @exif_read_data($file->getPathname());
            $orientation = (int) ($metadata['Orientation'] ?? 1);
            if (in_array($orientation, [2, 5, 7], true)) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            } elseif ($orientation === 4) {
                imageflip($image, IMG_FLIP_VERTICAL);
            }
            $angle = match ($orientation) {
                3 => 180,
                5, 8 => 90,
                6, 7 => -90,
                default => 0,
            };
            if ($angle !== 0) {
                $image = imagerotate($image, $angle, 0);
            }
        }

        $stream = tmpfile();
        abort_unless($stream, 500, 'Gagal menyiapkan konversi gambar.');

        try {
            $encoded = imagewebp($image, $stream, 80);
            if (! $encoded || fstat($stream)['size'] === 0) {
                throw ValidationException::withMessages([$field => 'Gagal mengonversi gambar ke WebP.']);
            }

            rewind($stream);
            $path = 'himsi/'.Str::uuid().'.webp';
            abort_unless(Storage::disk('public')->put($path, $stream), 500, 'Gagal menyimpan gambar.');

            return $path;
        } finally {
            fclose($stream);
        }
    }
}
