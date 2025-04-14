<?php

namespace App\Http\Requests\Blog;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddBlogRequest extends RequestWrapper
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
            'blog_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', 
            'blog_detail_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'blog_video' => 'nullable|mimes:3gp,mp4,avi,mov,wmv|max:102400', 
            'title' => 'required|string|max:60', 
            'type' => [Rule::in(['0','1', '2'])],
            'description' => 'required|string',
            'categories' => 'nullable|string',
            'author' => 'nullable|string|max:60',
            'blog_audio' => 'nullable|mimes:mp3',


        ];
    }
    public function messages()
    {
        return [
            'title.max' => 'The title must be 60 characters.',
            'author.max' => 'The author name must be 60 characters.',
            'blog_image.image' => 'Blog image must be an image file',
            'blog_image.mimes' => 'Blog image must be: jpeg, png, jpg, gif',
            'blog_image.max' => 'Blog image size: max 10 MB',
            'blog_detail_image.image' => 'Blog Detail image must be an image file',
            'blog_detail_image.mimes' => 'Blog Detail image must be: jpeg, png, jpg, gif',
            'blog_detail_image.max' => 'Blog Detail image size: max 10 MB',
        ];
    }
}
