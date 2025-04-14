<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class UpadateSeasonRequest extends RequestWrapper
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
            'season_id' => 'exists:tv_seasons,id',
            'series_id' => 'exists:tv_series,id',
            'season_number' => 'integer',
            'title' => 'string',
            'description' => 'string',
            'release_date' => 'date',
            'episode_count' => 'nullable|integer',
            'cover_image' => 'file|mimes:jpeg,jpg,png,gif,bmp,svg,webp,heic|max:15360',
            'video_url' => 'nullable|file|mimes:mp4,mov,ogg,qt,webm,avi,flv,mkv,wmv,3gp|max:102400'
        ];
    }
}
