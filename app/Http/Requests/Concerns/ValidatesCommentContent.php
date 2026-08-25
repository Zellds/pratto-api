<?php

namespace App\Http\Requests\Concerns;

trait ValidatesCommentContent
{
    protected function commentContentRules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1000'],
        ];
    }
}
