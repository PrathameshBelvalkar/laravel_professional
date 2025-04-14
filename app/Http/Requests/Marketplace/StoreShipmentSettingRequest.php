<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;

class StoreShipmentSettingRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
   

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'service_provider' => 'required|in:1,2',
            'type' => 'nullable|in:1,2,3',
            'key' => 'nullable|string',
            'value' => 'nullable|string',
            'status' => 'required|in:0,1',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'service_provider' => 'Service Provider',
            'type' => 'Type',
            'key' => 'Key',
            'value' => 'Value',
            'status' => 'Status',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'service_provider.required' => 'The service provider field is required.',
            'service_provider.in' => 'The selected service provider is invalid.',
            'type.in' => 'The selected type is invalid.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
