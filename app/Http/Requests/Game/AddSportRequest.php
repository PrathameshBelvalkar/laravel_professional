<?php

namespace App\Http\Requests\Game;

use App\Http\Requests\RequestWrapper;


class AddSportRequest extends RequestWrapper
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
            'sport_image' => 'required|image|mimes:jpeg,png,jpg,gif', 
            'sport_name' => 'required|string|max:255',
            'sport_description' => 'required|string',
        ];
    }
}
