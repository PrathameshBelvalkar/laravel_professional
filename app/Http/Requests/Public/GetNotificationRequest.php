<?php

namespace App\Http\Requests\Public;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetNotificationRequest extends RequestWrapper
{

  public function rules(): array
  {
    return [
      'module' => [Rule::in(['1', '2', '3', '4', '5', '6', '7', '8', '13', '14', '11', "9", "10", "12", "13", "14", "15", "16", "17", "18", "19"])],
      'offset' => ["numeric"],
      'recent' => ["numeric"],
      'limit' => ["numeric"],
    ];
  }
}
