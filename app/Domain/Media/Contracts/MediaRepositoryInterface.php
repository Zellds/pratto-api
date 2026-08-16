<?php

namespace App\Domain\Media\Contracts;

use App\Domain\Media\Media;
use App\Domain\Shared\Ulid;

interface MediaRepositoryInterface
{
    public function findById(Ulid $id): ?Media;

    public function save(Media $media): void;
}
