<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class UpdateVideoRequest extends RequestWrapper
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
            "stream_id" => ['required', 'numeric',Rule::exists('videos', 'id')],
            "title" => ['nullable', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'title.string' => 'Name should be a string.',
            'title.min' => 'Name must be at least :min characters.',
            'title.max' => 'Name must not exceed :max characters.',
            'description.string' => 'The description must be a string.',
            'tags.string' => 'The tags must be a string.',
        ];
    }
}
