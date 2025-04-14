<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class StoreImageSelectionRequest extends RequestWrapper
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
      'default_image_id' => 'nullable|exists:dashboard_images,id',
      'custom_image_path' => 'nullable|string',
      'color' => 'nullable|string',
      'is_logo' => 'nullable|in:0,1',
      'alignment' => 'nullable|string',
    ];
  }
}
