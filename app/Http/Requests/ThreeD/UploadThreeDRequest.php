<?php

namespace App\Http\Requests\ThreeD;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class UploadThreeDRequest extends RequestWrapper
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
            // "file_path" => ['required', 'file', 'mimes:3dm,3ds,3mf,amf,bim,brep,dae,fbx,fcstd,gltf,ifc,iges,step,stl,obj,off,ply,wrl,glb'],
            // "file_name" => ['required', 'string', 'max:255'],
            // "thumbnail_path" => ['required', 'file'],
            "file_format" => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_path.required' => 'File path is required.',
            // 'file_path.file' => 'File path must be a file.',
            // 'file_path.mimes' => 'The file_path extension should be only :values.',
            // 'file_name.required' => 'File name is required.',
            // 'file_name.string' => 'File name must be a string.',
            // 'file_name.max' => 'File name must not exceed :max characters.',
            // 'thumbnail_path.file' => 'Thumbnail path must be a file.',
            'file_format.string' => 'File format must be a string.',
        ];
    }
}
