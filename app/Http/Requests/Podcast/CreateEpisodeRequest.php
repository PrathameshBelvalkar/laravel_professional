<?php

namespace App\Http\Requests\Podcast;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class CreateEpisodeRequest extends RequestWrapper
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
            'podcast_id' => 'required|exists:podcasts,id',
            'title' => 'required|string|max:255|unique:podcast_episodes,title',
            'description' => 'required|string',
            'audio_url' => 'required|file|mimes:mp3|max:10240',
            'published_at' => 'nullable|date',
            'explicit' => 'required|in:0,1',
            'image_url' => 'file|max:10240',
            'transcriptions' => 'nullable|string',
            'guest_speakers' => 'nullable|string|max:255',
            'season_number' => 'nullable|integer',
            'episode_number' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'podcast_id.required' => 'The podcast ID is required.',
            'podcast_id.exists' => 'The selected podcast ID does not exist.',
            'title.required' => 'The title is required.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'description.required' => 'The description is required.',
            'description.string' => 'The description must be a string.',
            'audio_url.required' => 'The audio file is required.',
            'audio_url.file' => 'The audio file must be a file.',
            'audio_url.mimes' => 'The audio file must be an mp3 file.',
            'audio_url.max' => 'The audio file may not be greater than 10MB.',
            'published_at.date' => 'The published date must be a valid date.',
            'explicit.required' => 'The explicit field is required.',
            'explicit.in' => 'The explicit field must be either 0 or 1.',
            'image_url.string' => 'The image file is required.',
            'image_url.max' => 'The image must be JPG,PNG.',
            'transcriptions.string' => 'The transcriptions must be a string.',
            'guest_speakers.max' => 'The guest speakers may not be greater than 255 characters.',
            'season_number.integer' => 'The season number must be an integer.',
            'episode_number.integer' => 'The episode number must be an integer.',
        ];
    }
}
