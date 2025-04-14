<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetTicketSummaryRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "duration" => ['required', 'numeric'],
            "duration_type" => ['required', Rule::in(['d', 'm', 'y'])]
        ];
    }
}
