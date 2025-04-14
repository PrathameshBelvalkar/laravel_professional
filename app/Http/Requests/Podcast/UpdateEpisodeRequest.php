<?php

namespace App\Http\Requests\Podcast;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEpisodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'episode_id' => 'required|exists:podcast_episodes,id', // Required to identify the episode
            'title' => 'nullable|string|max:255|unique:podcast_episodes,title,' . $this->episode_id,
            'description' => 'nullable|string',
            'audio_url' => 'sometimes|file|mimes:mp3|max:10240',
            'published_at' => 'nullable|date',
            'explicit' => 'nullable|in:0,1',
            'image_url' => 'sometimes|file|mimes:jpeg,png,jpg,gif|max:10240',
            'transcriptions' => 'nullable|string',
            'guest_speakers' => 'nullable|string|max:255',
            'season_number' => 'nullable|integer',
            'episode_number' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'episode_id.required' => 'The episode ID is required.',
            'episode_id.exists' => 'The selected episode ID does not exist.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'title.unique' => 'The title has already been taken.',
            'description.string' => 'The description must be a string.',
            'audio_url.file' => 'The audio file must be a valid file.',
            'audio_url.mimes' => 'The audio file must be in mp3 format.',
            'audio_url.max' => 'The audio file may not be larger than 10MB.',
            'published_at.date' => 'The published date must be a valid date.',
            'explicit.in' => 'The explicit field must be either 0 or 1.',
            'image_url.file' => 'The image must be a file.',
            'image_url.mimes' => 'The image must be a type of: jpeg, png, jpg, gif.',
            'image_url.max' => 'The image may not be larger than 10MB.',
            'transcriptions.string' => 'The transcriptions must be a string.',
            'guest_speakers.max' => 'The guest speakers field may not exceed 255 characters.',
            'season_number.integer' => 'The season number must be an integer.',
            'episode_number.integer' => 'The episode number must be an integer.',
        ];
    }
}
