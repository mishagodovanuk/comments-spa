<?php

namespace App\Services\Comment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

final class AttachmentService
{
    public function handle(?UploadedFile $file): array
    {
        if (!$file) {
            return ['type' => null, 'path' => null, 'original' => null];
        }

        try {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $original = (string) $file->getClientOriginalName();

            if ($ext === 'txt') {
                return $this->storeTxt($file, $original);
            }

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                return $this->storeImage($file, $ext, $original);
            }

            throw new \RuntimeException('Unsupported file type. Allowed: jpg, jpeg, png, gif, txt.');
        } catch (Throwable $e) {
            Log::error('Upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            throw $e;
        }
    }

    private function storeTxt(UploadedFile $file, string $original): array
    {
        if (($file->getSize() ?? 0) > 100 * 1024) {
            throw new \RuntimeException('TXT file too large (max 100KB).');
        }

        $name = Str::uuid()->toString() . '.txt';
        $path = "comments/txt/{$name}";

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return ['type' => 'text', 'path' => $path, 'original' => $original];
    }

    private function storeImage(UploadedFile $file, string $ext, string $original): array
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getPathname());

        $image->scaleDown(width: 320, height: 240);

        $encoded = match ($ext) {
            'jpg', 'jpeg' => (string) $image->toJpeg(),
            'png' => (string) $image->toPng(),
            'gif' => (string) $image->toGif(),
            default => throw new \RuntimeException('Unsupported image extension.'),
        };

        $name = Str::uuid()->toString() . '.' . $ext;
        $path = "comments/images/{$name}";

        Storage::disk('public')->put($path, $encoded);

        return ['type' => 'image', 'path' => $path, 'original' => $original];
    }
}
