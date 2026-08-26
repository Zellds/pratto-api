<?php

// app/Http/Requests/AddPantryItemRequest.php

namespace App\Http\Requests;

use App\Domain\Recipe\Enums\MeasurementUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPantryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ingredient_id' => ['nullable', 'string', 'exists:ingredients,id'],
            'ingredient_name' => ['required_without:ingredient_id', 'nullable', 'string', 'max:80'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', Rule::in(array_column(MeasurementUnit::cases(), 'value'))],
            'is_fixed' => ['nullable', 'boolean'],
        ];
    }
}
