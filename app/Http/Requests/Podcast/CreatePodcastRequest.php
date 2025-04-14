<?php

namespace App\Http\Requests\Podcast;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class CreatePodcastRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:podcasts,title',
            'description' => 'required|string',
            'image_url' => 'file',
            'publisher' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:255',
            'explicit' => 'required|in:0,1',
            'category_id' => 'nullable|string',
            'tags_id' => 'nullable|string',
            'release_date' => 'nullable|string',
            'favourite' => 'nullable|string',
            'website' => 'nullable|string|url|max:255',
            'number_of_episodes' => 'nullable|integer',
        ];
    }
    public function messages()
    {
        return [
            'title.required' => 'The podcast title is required.',
            'title.string' => 'The podcast title must be a string.',
            'title.max' => 'The podcast title cannot be longer than 255 characters.',
            'title.unique' => 'The podcast title already exists.',
            'description.required' => 'The podcast description is required.',
            'description.string' => 'The podcast description must be a string.',
            'publisher.max' => 'The publisher name cannot be longer than 255 characters.',
            'language.max' => 'The language name cannot be longer than 255 characters.',
            'website.max' => 'The website URL cannot be longer than 255 characters.',
            'explicit.required' => 'Please specify if the podcast is explicit.',
            'explicit.in' => 'The explicit value must be either 0 or 1.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
