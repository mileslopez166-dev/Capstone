<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class TeacherProfilePhoto
{
    public static function encode(UploadedFile $file): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['photo' => 'Photo processing is unavailable. Please contact the administrator.'])->errorBag('teacherPhoto');
        }
        $source = @imagecreatefromstring($file->get());
        if (! $source) {
            throw ValidationException::withMessages(['photo' => 'Choose a valid JPG, PNG, or WebP photo.'])->errorBag('teacherPhoto');
        }

        // Apply camera orientation before re-encoding, which discards EXIF/GPS and other metadata.
        $exif = $file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data') ? @exif_read_data($file->getRealPath()) : false;
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if (in_array($orientation, [2, 5, 7], true)) imageflip($source, IMG_FLIP_HORIZONTAL);
        if ($orientation === 4) imageflip($source, IMG_FLIP_VERTICAL);
        $angle = match ($orientation) { 3 => 180, 5, 8 => 90, 6, 7 => -90, default => 0 };
        if ($angle) {
            $rotated = imagerotate($source, $angle, 0);
            imagedestroy($source);
            $source = $rotated;
        }
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 640 / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        ob_start();
        imagejpeg($target, null, 88);
        $encoded = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return $encoded;
    }
}
