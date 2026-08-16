<?php

namespace App\Http\Requests\Concerns;

use App\Domain\Recipe\MeasurementUnit;
use Illuminate\Validation\Rule;

trait ValidatesRecipeContent
{
    protected function recipeContentRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'portions' => ['required', 'integer', 'min:1'],
            'prep_time_minutes' => ['required', 'integer', 'min:1'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.ingredient_id' => ['nullable', 'string', 'exists:ingredients,id'],
            'ingredients.*.ingredient_name' => ['required_without:ingredients.*.ingredient_id', 'nullable', 'string', 'max:80'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'ingredients.*.unit' => ['required', 'string', Rule::in(array_column(MeasurementUnit::cases(), 'value'))],
            'ingredients.*.position' => ['required', 'integer', 'min:0'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.position' => ['required', 'integer', 'min:0'],
            'steps.*.instruction' => ['required', 'string', 'max:1000'],
            'cover_media_id' => ['nullable', 'string', 'exists:media,id'],
        ];
    }
}
