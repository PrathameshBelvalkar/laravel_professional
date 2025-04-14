<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class TicketStatusRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(["0", "1"])],
            'type' => ['required', Rule::in(["status", "is_stared"])],
            'ticket_id' => ['required', Rule::exists('support_tickets', 'ticket_unique_id')],
        ];
    }
}
