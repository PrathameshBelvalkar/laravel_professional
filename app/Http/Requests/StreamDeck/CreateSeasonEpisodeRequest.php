<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class CreateSeasonEpisodeRequest extends RequestWrapper
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
            'series_id' => 'required|exists:tv_series,id',
            'season_id' => 'required|exists:tv_seasons,id',
            'episode_number' => 'nullable|integer',
            'title' => 'required|string',
            'description' => 'required|string',
            'video_url' => 'nullable|file|mimes:mp4,mov,ogg,qt,webm,avi,flv,mkv,wmv,3gp|max:1024000',
            'release_date' => 'required|date',
            'thumbnail' => 'required|file|mimes:jpeg,jpg,png,gif,bmp,svg,webp,heic|max:15360',
            'rating' => 'nullable|integer',
            'views' => 'nullable|integer',
            'subtitles' => 'nullable|file|mimes:srt,sub,mpsub,lrc,cap,txt',

        ];
    }
}
