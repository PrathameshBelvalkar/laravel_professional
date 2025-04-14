<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class UploadVideoRequest extends RequestWrapper
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
            "title" => ['nullable', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'file_path.*' => ['nullable', 'mimes:mp4,mov,m4v,webm,ogv,mkv'],
        ];
    }

    public function messages()
    {
        return [
            'title.string' => 'Name should be a string.',
            'title.min' => 'Name must be at least :min characters.',
            'title.max' => 'Name must not exceed :max characters.',
            'description.string' => 'The description must be a string.',
            'file_path.required' => 'The file path field is required.',
            'file_path.mimes' => 'The file path field is required. and The file extension should be only :values',
            'tags.string' => 'The tags must be a string.',
        ];
    }
}
