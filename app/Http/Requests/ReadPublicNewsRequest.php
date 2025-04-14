<?php

namespace App\Http\Requests;
use Illuminate\Validation\Rule;

class ReadPublicNewsRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'ip'],
            'id' => ['required', Rule::exists('public_news', "id")->whereNull("deleted_at")],
        ];
    }
}
