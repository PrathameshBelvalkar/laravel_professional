<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class FrontendSettingRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['update', 'fetch', "rearrange"])],
            'column' => ['required_if:action,update', Rule::in(['apps', 'theme'])],
            'columnKey' => ['required_if:column,apps', Rule::in(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '10', '15', '16', '17', "18", "19"])],
            'columnValue' => ['required_if:action,update', Rule::in(['1', '2'])],
            'apps' => ['required_if:action,rearrange', "array"],
        ];
    }
}
