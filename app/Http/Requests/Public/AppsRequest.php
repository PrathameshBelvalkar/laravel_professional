<?php

namespace App\Http\Requests\Public;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AppsRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['banner', 'app'])],
        ];
    }
}
