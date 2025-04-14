<?php

namespace App\Http\Requests\Game\Team;

use App\Http\Requests\RequestWrapper;

class UpdateTeamRequest extends RequestWrapper
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
            'id' => 'required|integer|exists:teams,id', // Ensure team_id exists in the teams table
            'team_name' => 'nullable|string|max:255', // Team name is optional and has a maximum length of 255 characters
            'team_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }
}
