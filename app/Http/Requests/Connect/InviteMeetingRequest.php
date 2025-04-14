<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMeetingRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'visibility' => [Rule::in(["0", "1", "2", "3"])],
            'invited_emails' => ["nullable", "array"],
            'invited_usernames' => ["nullable", "array"],
            // 'meeting_time_zone' => ["nullable", "timezone"],
        ];
    }
}
