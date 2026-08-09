<?php

namespace App\Domain\Ingredient;

enum IngredientStatus: string
{
    case Provisional = 'provisional';
    case Approved = 'approved';
}
