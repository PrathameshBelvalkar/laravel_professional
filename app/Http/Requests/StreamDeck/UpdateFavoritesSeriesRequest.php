<?php

namespace App\Http\Requests\StreamDeck;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFavoritesSeriesRequest extends RequestWrapper
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
        'favoriteSeries_id'=>'required|exists:tv_favorites,id',
        'series_id'=>'required|exists:tv_series,id',
        'added_at'=>'required|date'
        ];
    }
}
