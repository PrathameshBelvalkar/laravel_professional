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
        return [];
    }

    public function messages()
    {
        return [];
    }
}
