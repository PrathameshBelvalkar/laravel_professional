<?php

namespace App\Http\Requests\Game\Team;

use App\Http\Requests\RequestWrapper;

class AddTeamRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sport_id' => 'required|exists:sports,id',
            'team_name' => 'required|string|max:255',
            'team_logo' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }
}
