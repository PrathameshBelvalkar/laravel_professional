<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class DeleteScheduleMeetingRequest extends RequestWrapper
{
    public function rules(): array
    {
        $user = $this->attributes->get('user');

        return [
            "id" => ["nullable", Rule::exists("connects", "id")->whereNull("deleted_at")->where("user_id", $user->id)],
        ];
    }
}
