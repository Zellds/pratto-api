<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesRecipeContent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    use ValidatesRecipeContent;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->recipeContentRules();
    }
}
