<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bio' => ['required', 'string', 'max:280'],
            'avatar_media_id' => ['nullable', 'string', 'exists:media,id'],
        ];
    }
}
