<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddProductRequest extends RequestWrapper
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
      "product_name" => [
        'required',
        'nullable',
        'string',
        'min:3',
        'max:255',
        Rule::unique('marketplace_products')
          ->where(function ($query) {
            return $query->where('store_id', $this->store_id);
          })
          ->whereNull('deleted_at')

      ],
      "price" => ['required', 'numeric'],
      "discount_percentage" => ['nullable', 'numeric'],
      "features" => ['nullable', 'string'],
      "description" => ['required', 'string', 'min:3', 'max:255'],
      "product_images.*" => ['required', 'nullable', 'file', 'mimes:jpg,png,webp,ico,avif,jpeg', 'max:10240'],
      "thumbnail" => ['required', 'nullable', 'file', 'mimes:jpg,png,webp,ico,avif,jpeg', 'max:10240'],
      "threed_image" => [
        'nullable',
        'file',
        function ($attribute, $value, $fail) {
          $allowedExtensions = ['3dm', '3ds', '3mf', 'amf', 'bim', 'brep', 'dae', 'fbx', 'fcstd', 'gltf', 'ifc', 'iges', 'step', 'stl', 'obj', 'off', 'ply', 'wrl', 'glb'];
          $extension = strtolower($value->getClientOriginalExtension());

          if (!in_array($extension, $allowedExtensions)) {
            $fail("The {$attribute} must be a file of type: " . implode(', ', $allowedExtensions) . '.');
          }
        },
        'max:102400'
      ],
      "is_accessory" => ['nullable', Rule::in(['0', '1'])],
      "stock" => ['required', 'numeric', 'gt:0'],
      "category_id" => ['required', 'numeric', Rule::exists('marketplace_category', 'id')],
      "sub_category_id" => ['required', 'numeric', Rule::exists('marketplace_sub_category', 'id')],
      "store_id" => ['required', 'nullable', 'numeric', Rule::exists('marketplace_stores', 'id')],
      "product_color" => ['required', 'min:3', 'max:25'],
      "brand_name" => ['required', 'string', 'min:3', 'max:255'],
    ];
  }
  public function messages()
  {
    return [
      "product_name.string" => "Product name should be a string.",
      "product_name.min" => "Product name must be at least :min characters.",
      "product_name.max" => "Product name must not exceed :max characters.",
      "price.numeric" => "Price should be a numeric value.",
      "discount_percentage.numeric" => "Discount percentage should be a numeric value.",
      "features.numeric" => "Features should be a numeric value.",
      "description.string" => "Description should be a string.",
      "description.min" => "Description must be at least :min characters.",
      "description.max" => "Description must not exceed :max characters.",
      "product_images.file" => "Product images should be files.",
      "product_images.mimes" => "Product images must have one of the following extensions: :values.",
      "product_images.max" => "Product images must not exceed :max kilobytes in size.",
      "thumbnail.file" => "Thumbnail should be files.",
      "thumbnail.mimes" => "Thumbnail must have one of the following extensions: :values.",
      "thumbnail.max" => "Thumbnail must not exceed :max kilobytes in size.",
      "is_accessory.in" => "The value for is_accessory should be either '0' or '1'.",
      "category_id.numeric" => "Category Id should be a numeric value.",
      "sub_category_id.numeric" => "Sub-category Id should be a numeric value.",
      "store_id.numeric" => "Store Id should be a numeric value.",
      "product_color.min" => "Product color must be at least :min characters.",
      "product_color.max" => "Product color must not exceed :max characters.",
      "brand_name.string" => "Brand name should be a string.",
      "brand_name.min" => "Brand name must be at least :min characters.",
      "brand_name.max" => "Brand name must not exceed :max characters.",
    ];
  }
}
