<?php

namespace App\Http\Requests\Game\Match;

use App\Http\Requests\RequestWrapper;

class UpdateMatchRequest extends RequestWrapper
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
            'match_id' => 'required|integer',
            'team_one_id' => 'sometimes|required|integer',
            'team_two_id' => 'sometimes|required|integer|different:team_one_id',
            'location' => 'sometimes|nullable|string',
            'date' => 'sometimes|nullable|date',
            'time' => 'sometimes|nullable|date_format:H:i',
        ];
    }
}
