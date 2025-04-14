<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ScheduleMeetingListRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            // "meeting_time_zone" => ['nullable', "timezone"],
            "duration" => ['required', Rule::in(['current', "length"])],
            "duration_type" => ['required', Rule::in(["days", "months", "hours", "minutes", "weeks", "years"])],
            "duration_length" => ['required_if:duration,length', "numeric", "gte:1"],
        ];
    }
}
