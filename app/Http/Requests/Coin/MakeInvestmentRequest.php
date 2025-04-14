<?php

namespace App\Http\Requests\Coin;

use App\Http\Requests\RequestWrapper;

class MakeInvestmentRequest extends RequestWrapper
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
            'year_id' => ['required', 'exists:coin_calendar_year,id', 'max:255'], 
            'coin_id' => ['required', 'exists:coin,id'],
            // 'investment_amount' => ['required', 'numeric', 'min:10'],
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'investment_amount.min' => 'The investment amount must be at least 10 USD.',
    //     ];
    // }

}
