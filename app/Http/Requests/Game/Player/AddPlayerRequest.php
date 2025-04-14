<?php

namespace App\Http\Requests\Game\Player;

use App\Http\Requests\RequestWrapper;

class AddPlayerRequest extends RequestWrapper
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
            'team_id' => 'required|integer|exists:teams,id',
            'player_name' => 'required|string|max:255',
            'player_image' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'player_position' => 'required|integer|exists:player_positions,id',
            'display_number' => 'required|integer',
        ];
    }
}
