<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Store an uploaded image as a compressed WebP file.
     *
     * @param  UploadedFile  $file
     * @param  string  $directory  Storage path under the public disk (e.g. 'alumni/profile_photos')
     * @param  int  $quality  WebP quality 1–100 (default 82 — good balance of quality vs size)
     * @param  int|null  $maxWidth  Resize to this width if the image is wider (maintains aspect ratio)
     * @return string  The stored path relative to the public disk
     */
    public static function storeAsWebP(
        UploadedFile $file,
        string $directory,
        int $quality = 82,
        ?int $maxWidth = 1200
    ): string {
        $source = self::createGdImage($file);

        if ($maxWidth !== null) {
            $source = self::resizeIfWider($source, $maxWidth);
        }

        $filename = Str::uuid() . '.webp';
        $relativePath = $directory . '/' . $filename;
        $fullPath = Storage::disk('public')->path($relativePath);

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);

        imagewebp($source, $fullPath, $quality);
        imagedestroy($source);

        return $relativePath;
    }

    private static function createGdImage(UploadedFile $file): \GdImage
    {
        $mime = $file->getMimeType();
        $path = $file->getRealPath();

        $image = match (true) {
            str_contains($mime, 'jpeg') => imagecreatefromjpeg($path),
            str_contains($mime, 'png')  => imagecreatefrompng($path),
            str_contains($mime, 'webp') => imagecreatefromwebp($path),
            str_contains($mime, 'gif')  => imagecreatefromgif($path),
            default                     => imagecreatefromstring(file_get_contents($path)),
        };

        if ($image === false) {
            throw new \RuntimeException("Could not read image file: {$file->getClientOriginalName()}");
        }

        // Preserve transparency for PNG/GIF
        if (str_contains($mime, 'png') || str_contains($mime, 'gif')) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        return $image;
    }

    private static function resizeIfWider(\GdImage $image, int $maxWidth): \GdImage
    {
        $origWidth  = imagesx($image);
        $origHeight = imagesy($image);

        if ($origWidth <= $maxWidth) {
            return $image;
        }

        $newHeight = (int) round($origHeight * ($maxWidth / $origWidth));
        $resized = imagecreatetruecolor($maxWidth, $newHeight);

        // Keep transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $origWidth, $origHeight);
        imagedestroy($image);

        return $resized;
    }
}
