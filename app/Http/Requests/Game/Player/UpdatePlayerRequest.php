<?php

namespace App\Http\Requests\Game\Player;

use App\Http\Requests\RequestWrapper;

class UpdatePlayerRequest extends RequestWrapper
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
            'player_id' => 'sometimes|integer|exists:players,id',
            'team_id' => 'sometimes|required|integer|exists:teams,id',
            'player_name' => 'sometimes|string|max:255',
            'player_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10240',
            'player_position' => 'sometimes|required|integer|exists:player_positions,id',
            'display_number' => 'sometimes|required|integer',
        ];
    }
}
