<?php

namespace App\Http\Requests\Coin;

use App\Http\Requests\RequestWrapper;

class AddReportsRequest extends RequestWrapper
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
            'coin_id' => ['required', 'numeric'],
            'category_id' => ['required'],
            'sub_category_id' => ['required'],
            'report_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'coin_id.required' => 'The coin ID is required.',
            'category_id.required' => 'The category ID is required.',
            'sub_category_id.required' => 'The sub-category ID is required.',
            'report_file.required' => 'The report file is required.',
            'report_file.file' => 'The report file must be a file.',
            'report_file.mimes' => 'The report file must be a file of type: pdf.',
            'report_file.max' => 'The report file may not be greater than 10 MB.',
        ];
    }
}
