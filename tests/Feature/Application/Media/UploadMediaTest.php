<?php

// tests/Feature/Application/Media/UploadMediaTest.php

use App\Application\Media\DTOs\UploadMediaInput;
use App\Application\Media\UseCases\UploadMedia;
use Illuminate\Support\Facades\Storage;

it('uploads an avatar already approved, with signed urls', function () {
    Storage::fake('media');
    $owner = anOwner();

    $output = app(UploadMedia::class)(new UploadMediaInput(
        $owner->value(), 'avatar', base_path('tests/fixtures/media/valid.jpg'), null, null,
    ));

    expect($output->status)->toBe('approved')
        ->and($output->kind)->toBe('avatar')
        ->and($output->thumbnailUrl)->not->toBeEmpty()
        ->and($output->displayUrl)->not->toBeEmpty();
});

it('uploads a recipe photo pending review', function () {
    Storage::fake('media');
    $owner = anOwner();

    $output = app(UploadMedia::class)(new UploadMediaInput(
        $owner->value(), 'recipe_photo', base_path('tests/fixtures/media/valid.jpg'), 0.2, 0.4,
    ));

    expect($output->status)->toBe('pending_review')
        ->and($output->focalX)->toBe(0.2)
        ->and($output->focalY)->toBe(0.4);
});

it('defaults the focal point to the center when not given', function () {
    Storage::fake('media');
    $owner = anOwner();

    $output = app(UploadMedia::class)(new UploadMediaInput(
        $owner->value(), 'avatar', base_path('tests/fixtures/media/valid.jpg'), null, null,
    ));

    expect($output->focalX)->toBe(0.5)
        ->and($output->focalY)->toBe(0.5);
});
