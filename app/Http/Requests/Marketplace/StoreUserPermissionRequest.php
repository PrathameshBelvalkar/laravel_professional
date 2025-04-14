<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;

class StoreUserPermissionRequest extends RequestWrapper
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
            'store_id' => 'nullable|integer',
            'allowed_permissions' => 'nullable|string',
        ];
    }
}
