<?php

namespace App\Http\Requests\Support;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddQuestionAnswerRequest extends RequestWrapper
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
            'category_id' => ['required'],
            'question' => ['required','string'],
            'answer' => ['required ','string'],
        ];
    }

    public function messages()
    {
        return [
            'category_id.required' => 'The category id field is required.',
            'question.required' => 'The question field is required.',
            'answer.required' => 'The answer field is required.',
        ];
    }
}
