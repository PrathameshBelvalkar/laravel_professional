<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class CreateTvSeriesRequest extends RequestWrapper
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
            'title' => 'required|string|unique:tv_series,title',
            'description' => 'required|string',
            'genre' => 'required|string',
            'release_date' => 'required|date',
            'cover_image' => 'nullable|file|mimes:jpeg,jpg,png,gif,bmp,svg,webp,heic|max:15360',
            'content_rating' => 'required|string',
            'status' => 'required|string',
            'cast' => 'required|string',
            'directors' => 'required|string',
            'channel_id' => 'required|exists:channels,id'
        ];
    }
}
