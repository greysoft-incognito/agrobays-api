<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $load = collect($this->arrayMaker($request->get('with', [])));

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'items' => $this->when($load->contains(fn ($i) => $i == 'items'), $this->content ?? $this->items ?? []),
            'user' => $this->when($load->contains(fn ($i) => $i == 'user'), new UserBasicDataResource($this->user)),
            'reference' => $this->reference,
            'method' => $this->method,
            'due' => $this->due,
            'tax' => $this->tax,
            'fees' => $this->fees,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function arrayMaker($load)
    {
        if (is_string($load)) {
            return str($load)->replace(' ', '')->explode(',');
        }

        return $load;
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
