<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class TransferRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'numeric', Rule::exists("users", 'id')],
            'txn_tokens' => ['required', 'numeric'],
            'request_id' => ['numeric', Rule::exists("token_requests", "id")->where('type', "1")->where("status", "0")],
        ];
    }
    public function messages()
    {
        return [
            'receiver_id.required' => 'The receiver user id field is required.',
            'receiver_id.numeric' => 'The receiver user id must be numeric.',
            'txn_tokens.required' => 'The token amount field is required.',
            'txn_tokens.numeric' => 'The token amount Id must be numeric.'
        ];
    }
}
