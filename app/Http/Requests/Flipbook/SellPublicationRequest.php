<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class SellPublicationRequest extends RequestWrapper
{
    public function rules(): array
    {
        $user = $this->attributes->get('user');
        return [
            "flipbook_id" => ["required", Rule::exists("flipbook_publications", "flipbook_id")->whereIn("status", ['1', '2'])->where("user_id", $user->id)->whereNull("deleted_at")],
            'price' => ["required", "numeric", "gte:1"],
            'pages' => ["required", "array"],
            'currency' => ["nullable", "string", "length:3"],
            'preview_link' => 'required|file|mimes:pdf',
        ];
    }
}
