<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCommentContent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    use ValidatesCommentContent;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->commentContentRules();
    }
}
