<?php

namespace App\Http\Requests\Public;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;


class GetSiteSettingRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "module_id" => ['required', "numeric", Rule::in(["0", '1', '2', '3', '4', '5', '6', '7', '8'])],
            "field_key" => ["string"],
        ];
    }
}
