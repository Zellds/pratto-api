<?php

namespace App\Domain\Ingredient\Enums;

enum IngredientStatus: string
{
    case Provisional = 'provisional';
    case Approved = 'approved';
}
