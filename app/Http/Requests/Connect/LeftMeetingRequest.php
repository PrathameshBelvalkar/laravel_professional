<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;

class LeftMeetingRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "admin_id" => ['required'],
        ];
    }
}
