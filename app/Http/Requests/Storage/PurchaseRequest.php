<?php

namespace App\Http\Requests\Storage;

use App\Http\Requests\RequestWrapper;

class PurchaseRequest extends RequestWrapper
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
            'storage_plan_id' => ['required', 'numeric'],
        ];
    }
    public function messages()
    {
        return [
            'storage_plan_id.required' => 'The storage_plan_id field is required.',
            'storage_plan_id.numeric' => 'The numeric must be number.',
        ];
    }
}
