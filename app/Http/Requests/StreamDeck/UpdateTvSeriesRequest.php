<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTvSeriesRequest extends RequestWrapper
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
            'tvSeriesId' => 'exists:tv_series,id',
            'title' => 'string',
            'description' => 'string',
            'genre' => 'string',
            'release_date' => 'date',
            'cover_image' => 'file|mimes:jpeg,jpg,png,gif,bmp,svg,webp,heic|max:15360',
            'content_rating' => 'string',
            'status' => 'string',
            'cast' => 'string',
            'directors' => 'string',
            'channel_id' => 'exists:channels,id'
        ];
    }
}
