<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ScheduleMeetingRequest extends RequestWrapper
{
    public function rules(): array
    {
        $user = $this->attributes->get('user');
        return [
            "meeting_start_time" => ["required", 'date_format:Y-m-d H:i:s'],
            "meeting_end_time" => ["required", 'date_format:Y-m-d H:i:s'],
            // "meeting_time_zone" => ["nullable", 'timezone'],
            "description" => ["nullable", 'max:255'],
            "id" => ["nullable", Rule::exists("connects", "id")->whereNull("deleted_at")->where("user_id", $user->id)],
            "title" => ["nullable", 'max:500'],
        ];
    }
}
