<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class RemoveFollowersRequest  extends RequestWrapper
{
  public function rules(): array
  {
    return [
      'follower_id' => 'required|integer|exists:users,id',
      'module' => 'required|string|in:community',
    ];
  }
}
