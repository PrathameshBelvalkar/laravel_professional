<?php

namespace App\Http\Requests\AppDetails;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddAppDetailsRequest extends RequestWrapper
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
            'about_app'=>'required|string|max:1000',
            'app_features'=>'required',
            //'app_features.*' => 'max:5000',
            'app_logo'=>'required|max:1',
            'app_logo'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'app_screenshots'=>'required|array|max:5',
            'app_screenshots.*'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
