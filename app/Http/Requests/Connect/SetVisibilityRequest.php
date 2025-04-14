<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class SetVisibilityRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "visibility" => ['required', Rule::in(["0", "1", "2", "3"])]
        ];
    }
}
