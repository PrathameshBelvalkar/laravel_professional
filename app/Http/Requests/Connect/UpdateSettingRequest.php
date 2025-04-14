<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "camera" => [Rule::in(['1', '2'])],
            "action" => ["required", Rule::in(['update', 'fetch'])],
            "mic" => [Rule::in(['1', '2'])],
        ];
    }
}
