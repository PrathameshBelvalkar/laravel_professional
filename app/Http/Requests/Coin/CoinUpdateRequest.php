<?php

namespace App\Http\Requests\Coin;

use App\Rules\Uppercase;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class CoinUpdateRequest extends RequestWrapper
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
            'coin_name' => ['nullable', 'string', 'max:255'], 
            'coin_symbol' => ['nullable', 'string', new Uppercase],
            'description' => ['nullable', 'string','max:3000'],
            'coin_logo' => ['nullable', 'file', 'mimes:jpg,png,jpeg,gif', 'max:10240'],
             'price' => ['nullable', 'numeric'],
            'one_h' => ['nullable', 'numeric'],
            'twenty_four_h' => ['nullable', 'numeric'],
            'seven_d' => ['nullable', 'numeric'],
            'market_cap' => ['nullable', 'numeric'],
            'volume' => ['nullable', 'numeric'],
            'circulation_supply' => ['nullable', 'numeric'],
        ];
    }
}
