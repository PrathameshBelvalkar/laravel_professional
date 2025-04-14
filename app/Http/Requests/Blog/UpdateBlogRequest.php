<?php

namespace App\Http\Requests\Blog;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UpdateBlogRequest extends RequestWrapper
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'blog_id' => 'required|integer|exists:blogs,id',
            'blog_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10240', 
            'blog_detail_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10240',
            'blog_video' => 'sometimes|mimes:3gp,mp4,avi,mov,wmv|max:102400', 
            'title' => 'sometimes|string|max:60',
            'for_whom' => [Rule::in(['0','1', '2'])],
            'tags' => 'sometimes|array', 
            'tags.*' => 'string|max:255', 
            'type' => [Rule::in(['0','1', '2'])],
            'description' => 'sometimes|string',
            'categories' => 'sometimes|string',
            'author' => 'sometimes|string|max:60',
        ];
    }
    public function messages()
    {
        return [
            'title.max' => 'The title must be 60 characters.',
            'author.max' => 'The author name must be 60 characters.',
        ];
    }
}
