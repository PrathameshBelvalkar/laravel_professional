<?php

namespace App\Http\Requests\Game\Team;

use App\Http\Requests\RequestWrapper;

class GetTeamRequest extends RequestWrapper
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
            'id' => 'integer|exists:teams,id',
            'sport_id' => 'required|integer|exists:teams,sport_id'
        ];
    }
}
