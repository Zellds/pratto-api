<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FocalPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'focal_x' => ['required', 'numeric', 'between:0,1'],
            'focal_y' => ['required', 'numeric', 'between:0,1'],
        ];
    }
}
