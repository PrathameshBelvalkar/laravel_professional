<?php

namespace App\Http\Requests\Podcast;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePodcastRequest extends RequestWrapper
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'podcast_id' => 'required|exists:podcasts,id',
            'title' => 'nullable|string|max:255|unique:podcasts,title,' . $this->podcast_id,
            'description' => 'nullable|string',
            'audio_url' => 'nullable|file|mimes:mp3|max:10240',
            'published_at' => 'nullable|date',
            'explicit' => 'nullable|in:0,1',
            'image_url' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240',
            'transcriptions' => 'nullable|string',
            'guest_speakers' => 'nullable|string|max:255',
            'season_number' => 'nullable|integer',
            'episode_number' => 'nullable|integer',
            'publisher' => 'nullable|string|max:255',
            'language' => 'nullable|integer|max:255',
            'category_id' => 'nullable|integer|exists:podcast_categories,id',
            'tags_id.*' => 'integer|exists:podcast_tags,id',
            'release_date' => 'nullable|date',
            'favourite' => 'nullable|boolean',
            'website' => 'nullable|url',
            'number_of_episodes' => 'nullable|integer',
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'podcast_id.required' => 'The podcast ID is required.',
            'podcast_id.exists' => 'The selected podcast ID does not exist.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'title.unique' => 'A podcast with this title already exists.',
            'description.string' => 'The description must be a string.',
            'audio_url.file' => 'The audio file must be a valid file.',
            'audio_url.mimes' => 'The audio file must be in mp3 format.',
            'audio_url.max' => 'The audio file may not be larger than 10MB.',
            'published_at.date' => 'The published date must be a valid date.',
            'explicit.in' => 'The explicit field must be either 0 or 1.',
            'image_url.file' => 'The image must be a file.',
            'image_url.mimes' => 'The image must be a type of: jpeg, png, jpg, gif.',
            'image_url.max' => 'The image may not be larger than 10MB.',
            'transcriptions.string' => 'The transcription must be a string.',
            'guest_speakers.max' => 'The guest speakers field may not exceed 255 characters.',
            'season_number.integer' => 'The season number must be an integer.',
            'episode_number.integer' => 'The episode number must be an integer.',
            'publisher.string' => 'The publisher must be a string.',
            'language.string' => 'The language must be a string.',
            'category_id.exists' => 'The selected category does not exist.',
            'tags_id.array' => 'The tags must be an array.',
            'tags_id.*.exists' => 'One or more selected tags do not exist.',
            'release_date.date' => 'The release date must be a valid date.',
            'favourite.boolean' => 'The favourite field must be true or false.',
            'website.url' => 'The website must be a valid URL.',
            'number_of_episodes.integer' => 'The number of episodes must be an integer.',
        ];
    }
}
