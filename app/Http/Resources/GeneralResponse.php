<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ([
            'type' => isset($this['type']) ? $this['type'] : 400,
            'status' => isset($this['status']) ? $this['status'] : 400,
            'code' => isset($this['code']) ? $this['code'] : 400,
            'message' => $this['message'],
            'data' => (isset($this['data']) && !empty($this['data'])) ? $this['data'] : (object)[],
            'toast' => isset($this['toast']) ? $this['toast'] : false,
        ]);
    }
}
