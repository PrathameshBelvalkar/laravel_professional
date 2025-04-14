<?php

namespace App\Http\Requests\Coin;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddFeedbackRequest extends RequestWrapper
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
            // 'review_id' => ['required', 'exists:reviews,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'max:1000'],
        ];
    }
}
