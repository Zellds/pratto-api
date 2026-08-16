<?php

use App\Domain\Media\Contracts\ImageProcessorInterface;
use App\Domain\Media\Exceptions\InvalidImageException;
use App\Domain\Media\FocalPoint;
use Illuminate\Support\Facades\Storage;

it('stores original, thumbnail and display under the storage key', function () {
    Storage::fake('media');
    $pipeline = app(ImageProcessorInterface::class);

    $result = $pipeline->process(
        base_path('tests/fixtures/media/valid.jpg'),
        'a-storage-key',
        FocalPoint::center(),
    );

    Storage::disk('media')->assertExists('a-storage-key/original.jpg');
    Storage::disk('media')->assertExists('a-storage-key/thumbnail.jpg');
    Storage::disk('media')->assertExists('a-storage-key/display.jpg');
    expect($result['width'])->toBeGreaterThan(0)
        ->and($result['height'])->toBeGreaterThan(0);
});

it('rejects images that exceed the pixel-count limit before decoding', function () {
    Storage::fake('media');
    $pipeline = app(ImageProcessorInterface::class);

    $pipeline->process(
        base_path('tests/fixtures/media/too-large-dimensions.jpg'),
        'oversized-key',
        FocalPoint::center(),
    );
})->throws(InvalidImageException::class);

it('re-crops only the thumbnail on reprocessThumbnail', function () {
    Storage::fake('media');
    $pipeline = app(ImageProcessorInterface::class);

    $pipeline->process(base_path('tests/fixtures/media/valid.jpg'), 'reprocess-key', FocalPoint::center());
    $before = Storage::disk('media')->get('reprocess-key/display.jpg');

    $pipeline->reprocessThumbnail('reprocess-key', FocalPoint::create(0.1, 0.1));

    $after = Storage::disk('media')->get('reprocess-key/display.jpg');
    expect($after)->toBe($before); // display não muda, só o thumbnail
    Storage::disk('media')->assertExists('reprocess-key/thumbnail.jpg');
});
