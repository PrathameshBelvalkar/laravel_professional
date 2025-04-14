<?php

namespace App\Http\Requests\Coin;

use App\Rules\Uppercase;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class NewsAddRequest extends RequestWrapper
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
            'coin_id' => ['required', 'numeric'],
            'title' => ['required', 'string', 'max:255'], 
            'description' => ['required', 'string','max:3000'],
            // 'news_img' => ['required', 'file', 'mimes:jpg,png,jpeg,gif', 'max:10240'],
            'author' => ['required', 'string'],
        ];
    }
}
