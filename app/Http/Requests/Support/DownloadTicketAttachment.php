<?php

namespace App\Http\Requests\Support;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class DownloadTicketAttachment extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "file_path" => ["required", "string"],
            "db_id" => ["required", "numeric"],
            "type" => ["required", Rule::in(["ticket", "reply"])]
        ];
    }
}
