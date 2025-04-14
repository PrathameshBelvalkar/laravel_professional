<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ReplyTicketRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'ticket_id' => ['required', Rule::exists('support_tickets', 'ticket_unique_id')],
            'reply' => ['required', 'string'],
            'chat_type' => ['required', Rule::in(["1", "2"])],
        ];
    }
}
