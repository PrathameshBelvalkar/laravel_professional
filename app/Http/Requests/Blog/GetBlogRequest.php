<?php

namespace App\Http\Requests\Blog;

use App\Http\Requests\RequestWrapper;

class GetBlogRequest extends RequestWrapper
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
            'category_id' => 'nullable|string',
            'id' => 'nullable|integer',
        ];
    }
}

