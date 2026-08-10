<?php

namespace App\Domain\Recipe;

enum MeasurementUnit: string
{
    case Gram = 'g';
    case Kilogram = 'kg';
    case Milliliter = 'ml';
    case Liter = 'l';
    case Whole = 'unidade';
    case Cup = 'xicara';
    case Tablespoon = 'colher_sopa';
    case Teaspoon = 'colher_cha';
    case Pinch = 'pitada';
    case ToTaste = 'a_gosto';
}
