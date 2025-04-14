<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetTechUsersRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "limit" => ["numeric"],
            "offset" => ["numeric"],
            "search" => ["string"],
            "ticket_id" => ['required', Rule::exists('support_tickets', 'ticket_unique_id')],
        ];
    }
}
