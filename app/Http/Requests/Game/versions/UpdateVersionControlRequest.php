<?php

namespace App\Http\Requests\Game\versions;

use App\Http\Requests\RequestWrapper;

class UpdateVersionControlRequest extends RequestWrapper
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
            'id' => 'required|exists:version_controls,id',
            'title' => 'sometimes|string|max:255',
            'group_key' => 'sometimes|in:0,1,2,3',
            'version' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'file' => 'sometimes|file|mimes:zip,exe,',
        ];
    }
}
