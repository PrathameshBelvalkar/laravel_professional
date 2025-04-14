<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class GetQuestionRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'numeric'],
            'limit' => ['numeric'],
            'page' => ['numeric', "gt:0"],
            'search' => ['string'],
        ];
    }
}
