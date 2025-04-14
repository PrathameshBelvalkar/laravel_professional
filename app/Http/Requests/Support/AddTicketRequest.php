<?php

namespace App\Http\Requests\Support;


use App\Http\Requests\RequestWrapper;

class AddTicketRequest extends RequestWrapper
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
            'category_id' => ['required', 'numeric'],
            'title' => ['required', 'string', 'max:1000'],
            'description' => ['string'],
            'tags' => ['nullable', "string"],
        ];
    }
    public function messages(): array
    {
        return [
            'category_id.required' => 'The category id field is required.',
            'category_id.numeric' => 'The category id must be a number.',
            'description.string' => 'The description must be a string.',
            'tags.string' => 'The tags must be a string.',
            'file_upload.file' => 'The file upload must be a file.',
        ];
    }
}
