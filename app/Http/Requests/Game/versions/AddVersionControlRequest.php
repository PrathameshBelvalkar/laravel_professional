<?php

namespace App\Http\Requests\Game\versions;

use App\Http\Requests\RequestWrapper;

class AddVersionControlRequest extends RequestWrapper
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
            'title' => 'required|string|max:255',
            'group_key' => 'required|in:0,1,2,3',
            'file' => 'required|file|mimes:zip,exe,',
            'version' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
