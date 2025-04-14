<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class CreateWebsite extends RequestWrapper
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
            "website_title" => ['required', 'string', 'min:3', 'max:255'],
            'custom_domian' => ['required', 'string'],
            'channels' => ['required', 'nullable', 'string', 'regex:/^\d+(,\d+)*$/'],
            'site_logo' => ['nullable', 'file'],
            'site_favicon' => ['nullable', 'file'],
            'header' => [Rule::in(['0', '1'])],
            'page_layout' => [Rule::in(['0', '1'])],
            'playback_options' => ['nullable', 'string'],
            'display_options' => ['nullable', 'string'],
            'footer_text' => ['nullable', 'string'],
            'footer_description' => ['nullable', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'website_title.string' => 'Title should be a string.',
            'website_title.min' => 'Title must be at least :min characters.',
            'website_title.max' => 'Title must not exceed :max characters.',
            'custom_domian.required' => 'Domain name is required.',
            'custom_domian.string' => 'The domain name must be a string.',
            'channels.string' => 'The tags must be a string.',
            'channels.regex' => 'The channels field must contain only numbers.',
            'site_logo.string' => 'The site logo must be a file.',
            'site_favicon.string' => 'The site favicon must be a file.',
            'header.in' => 'The header field should be either 0 or 1.',
            'page_layout.in' => 'The page_layout field should be either 0 or 1.',
            'playback_options.string' => 'The playback_options must be a string.',
            'display_options.string' => 'The tags must be a string.',
            'footer_text.string' => 'The tags must be a string.',
            'footer_description.string' => 'The tags must be a string.',
        ];
    }
}
