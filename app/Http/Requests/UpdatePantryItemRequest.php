<?php

// app/Http/Requests/UpdatePantryItemRequest.php

namespace App\Http\Requests;

use App\Domain\Recipe\Enums\MeasurementUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePantryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'needs_to_buy' => ['nullable', 'boolean'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', Rule::in(array_column(MeasurementUnit::cases(), 'value'))],
            'is_fixed' => ['nullable', 'boolean'],
        ];
    }
}
