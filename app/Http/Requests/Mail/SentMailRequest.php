<?php

namespace App\Http\Requests\Mail;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class SentMailRequest extends RequestWrapper
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
            'recipients' => ['nullable', 'regex:/^([\w\.-]+@[\w\.-]+\s*,\s*)*[\w\.-]+@[\w\.-]+$/'],
            'sender' => 'required|email',
            'cc' => ['nullable', 'regex:/^([\w\.-]+@[\w\.-]+\s*,\s*)*[\w\.-]+@[\w\.-]+$/'],
            'bcc' => ['nullable', 'regex:/^([\w\.-]+@[\w\.-]+\s*,\s*)*[\w\.-]+@[\w\.-]+$/'],
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'attachment' => 'nullable|array',
        ];
            
    }
}
