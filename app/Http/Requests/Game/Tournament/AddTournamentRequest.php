<?php

namespace App\Http\Requests\Game\Tournament;

use App\Http\Requests\RequestWrapper;

class AddTournamentRequest extends RequestWrapper
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
            'tournament_name' => 'required|string|max:255',
            'tournament_logo' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'location' => 'required|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }
}
