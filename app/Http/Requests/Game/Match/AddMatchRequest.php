<?php

namespace App\Http\Requests\Game\Match;

use App\Http\Requests\RequestWrapper;

class AddMatchRequest extends RequestWrapper
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
            'tournament_id' => 'required|integer',
            'sport_id' => 'required|integer',
            'team_one_id' => 'required|integer',
            'team_two_id' => 'required|integer|different:team_one_id',
            'location' => 'nullable|string',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ];
    }
}
