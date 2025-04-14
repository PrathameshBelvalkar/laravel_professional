<?php

namespace App\Http\Requests\qrcode;

use App\Http\Requests\RequestWrapper;

class AddQrSkuRequest extends RequestWrapper
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
      'product_name' => 'nullable|string|max:255',
      'brand' => 'nullable|string|max:255',
      'stock' => 'nullable|string|max:255',
      'sku_code' => 'nullable|string',
      'category' => 'nullable|string|max:255',
      'sub_category' => 'nullable|string|max:255',
      'material' => 'nullable|string|max:255',
      'color' => 'nullable|string|max:100',
      'size' => 'nullable|string|max:255',
      'weight' => 'nullable|string|max:255',
      'price' => 'nullable|numeric',
      'cost_price' => 'nullable|numeric',
      'currency' => 'nullable|string|max:10',
      'quantity_in_stock' => 'nullable|integer',
      'reorder_level' => 'nullable|string',
      'supplier' => 'nullable|string|max:255',
      'minimum_order_quantity' => 'nullable|integer',
      'short_description' => 'nullable|string|max:500',
      'full_description' => 'nullable|string',
      'file_path.*.*' => 'nullable|file|image|mimes:jpg,jpeg,png|max:20480',
      'sku_pdf' => 'nullable|file|mimes:pdf|max:20480',
    ];
  }
}
