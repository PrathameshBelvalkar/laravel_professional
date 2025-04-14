<?php

namespace App\Http\Requests\Coin;

use App\Rules\Uppercase;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class CoinRegisterRequest extends RequestWrapper
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
            'coin_name' => ['required', 'string', 'max:255'], 
            'coin_symbol' => ['required', 'string', new Uppercase],
            'description' => ['nullable', 'string','max:3000'],
            'coin_logo' => ['nullable', 'file', 'mimes:jpg,png,jpeg,gif', 'max:10240'],
            'price' => ['required', 'numeric'],
            'one_h' => ['required', 'numeric'],
            'twenty_four_h' => ['required', 'numeric'],
            'seven_d' => ['required', 'numeric'],
            'market_cap' => ['required', 'numeric'],
            'volume' => ['required', 'numeric'],
            'circulation_supply' => ['required', 'numeric'],
        ];
    }
}
