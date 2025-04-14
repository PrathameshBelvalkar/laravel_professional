<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class UpdateLiveStreamRequest extends RequestWrapper
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
            "stream_title" => ['nullable', 'string', 'min:3', 'max:255'],
        ];
    }
    public function messages()
    {
        return [
            'stream_title.string' => 'Title should be a string.',
            'stream_title.min' => 'Title must be at least :min characters.',
            'stream_title.max' => 'Title must not exceed :max characters.',
        ];
    }
}
