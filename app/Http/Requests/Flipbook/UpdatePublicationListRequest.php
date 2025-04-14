<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicationListRequest extends RequestWrapper
{

    public function rules(): array
    {
        $user = $this->attributes->get('user');
        return [
            "status" => [Rule::in(['0', '1', '2'])],
            "publication_id" => [
                'required',
                Rule::exists('flipbook_publications', 'id')
                    ->where(function ($query) use ($user) {
                        return $query->where('user_id', $user->id)->where("deleted_at", null);
                    })
            ],
            'title' => ['string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'visibility' => [Rule::in(['1', '2'])],
        ];
    }
}
