<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSeasonEpisodeRequest extends RequestWrapper
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
            'episode_id' => 'exists:season_episodes,id',
            'series_id' => 'exists:tv_series,id',
            'season_id' => 'exists:tv_seasons,id',
            'episode_number' => 'integer',
            'title' => 'string',
            'description' => 'string',
            'video_url' => 'nullable|file|mimes:mp4,mov,ogg,qt,webm,avi,flv,mkv,wmv,3gp|max:102400',
            'release_date' => 'date',
            'thumbnail' => 'file|mimes:jpeg,jpg,png,gif,bmp,svg,webp,heic|max:5120',
            'rating' => 'nullable|integer',
            'views' => 'nullable|integer',
            'subtitles' => 'nullable|file|mimes:srt,sub,mpsub,lrc,cap,txt',
            'duration'=>'nullable'

        ];
    }
}
