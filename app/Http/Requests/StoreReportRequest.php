<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', 'string', 'in:recipe,user,comment'],
            'target_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
