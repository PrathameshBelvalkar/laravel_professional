<?php

namespace App\Http\Requests\Assembler;

use App\Http\Requests\RequestWrapper;

class AssemblerFileUploadRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'file' => 'required|file|max:2097152', // 2GB in KB
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'A file is required.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.max' => 'The file size must not exceed 2GB.',
        ];
    }
}
