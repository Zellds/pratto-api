<?php

namespace App\Domain\Recipe\Exceptions;

use App\Domain\Recipe\Enums\RecipeStatus;
use RuntimeException;

final class InvalidRecipeStatusTransitionException extends RuntimeException
{
    public function __construct(RecipeStatus $from, RecipeStatus $to)
    {
        parent::__construct(sprintf('Cannot transition recipe from "%s" to "%s".', $from->value, $to->value));
    }
}
