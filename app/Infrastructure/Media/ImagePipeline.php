<?php

namespace App\Infrastructure\Media;

use App\Domain\Media\Contracts\ImageProcessorInterface;
use App\Domain\Media\Exceptions\InvalidImageException;
use App\Domain\Media\FocalPoint;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

final class ImagePipeline implements ImageProcessorInterface
{
    private const MAX_DIMENSION_PX = 8000;

    private const MAX_TOTAL_PIXELS = 40_000_000;

    private const THUMBNAIL_SIZE = 300;

    private const DISPLAY_MAX_SIDE = 1200;

    private const JPEG_QUALITY = 82;

    public function __construct(private readonly ImageManager $manager) {}

    public function process(string $sourcePath, string $storageKey, FocalPoint $focalPoint): array
    {
        $this->assertDimensionsAreSafe($sourcePath);

        $image = $this->manager->read($sourcePath);

        $this->store($storageKey.'/original.jpg', clone $image);

        $display = $this->buildDisplay(clone $image);
        $this->store($storageKey.'/display.jpg', $display);

        $thumbnail = $this->buildThumbnail(clone $image, $focalPoint);
        $this->store($storageKey.'/thumbnail.jpg', $thumbnail);

        return ['width' => $display->width(), 'height' => $display->height()];
    }

    public function reprocessThumbnail(string $storageKey, FocalPoint $focalPoint): void
    {
        $originalBytes = Storage::disk('media')->get($storageKey.'/original.jpg');
        $original = $this->manager->read($originalBytes);

        $thumbnail = $this->buildThumbnail($original, $focalPoint);
        $this->store($storageKey.'/thumbnail.jpg', $thumbnail);
    }

    private function assertDimensionsAreSafe(string $path): void
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw new InvalidImageException('File is not a readable image.');
        }

        [$width, $height] = $info;

        if ($width > self::MAX_DIMENSION_PX || $height > self::MAX_DIMENSION_PX) {
            throw new InvalidImageException('Image dimensions exceed the allowed limit.');
        }

        if ($width * $height > self::MAX_TOTAL_PIXELS) {
            throw new InvalidImageException('Image pixel count exceeds the allowed limit.');
        }
    }

    private function buildDisplay(ImageInterface $image): ImageInterface
    {
        return $image->scaleDown(width: self::DISPLAY_MAX_SIDE, height: self::DISPLAY_MAX_SIDE);
    }

    private function buildThumbnail(ImageInterface $image, FocalPoint $focalPoint): ImageInterface
    {
        $width = $image->width();
        $height = $image->height();
        $side = min($width, $height);

        $centerX = (int) round($width * $focalPoint->x());
        $centerY = (int) round($height * $focalPoint->y());

        $offsetX = max(0, min($width - $side, $centerX - intdiv($side, 2)));
        $offsetY = max(0, min($height - $side, $centerY - intdiv($side, 2)));

        return $image->crop($side, $side, $offsetX, $offsetY)->resize(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
    }

    private function store(string $path, ImageInterface $image): void
    {
        Storage::disk('media')->put($path, (string) $image->toJpeg(self::JPEG_QUALITY));
    }
}
