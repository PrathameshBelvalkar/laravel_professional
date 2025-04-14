<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchasePublicationRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'publication_id' => ['required', Rule::exists('flipbook_publications', 'id')->whereNull("deleted_at")->where("status", "2")],
        ];
    }
}
