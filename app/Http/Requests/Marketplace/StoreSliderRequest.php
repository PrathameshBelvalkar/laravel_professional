<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;
use Illuminate\Support\Facades\Log;

class StoreSliderRequest extends RequestWrapper
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'slider1' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'slider2' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'slider3' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_text1' => 'required|json',
            'image_text2' => 'required|json',
            'image_text3' => 'required|json',
        ];
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        Log::info('Request data: ', $this->all());
        // You can add additional preparation logic here if needed
    }
}
