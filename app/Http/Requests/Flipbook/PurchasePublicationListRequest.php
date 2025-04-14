<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class PurchasePublicationListRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'limit' => ['numeric', 'gte:1'],
            'page' => ['numeric', 'gte:1'],
            // 'search_keyword' => ['string'],
            'order_by' => ['string', Rule::in(['currency', "price", "title", "description", "transaction_id", "auger_transaction_id", "seller_id", "buyer_id", "created_at", "updated_at"])],
            'order' => ['string', Rule::in(["asc", "desc"])],
        ];
    }
}
