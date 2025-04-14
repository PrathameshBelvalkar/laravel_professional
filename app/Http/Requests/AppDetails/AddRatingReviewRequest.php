<?php

namespace App\Http\Requests\AppDetails;

use App\Rules\Uppercase;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddRatingReviewRequest extends RequestWrapper
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
            'app_id'=>'required|integer',
            'reviews'=>'nullable|string|max:255',
            'ratings'=>'nullable|integer|between:1,5',
        ];
    }
}
