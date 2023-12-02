<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $with = collect(is_array($request->with) ? $request->with : explode(',', $request->with));
        $without = collect(is_array($request->without) ? $request->without : explode(',', $request->without));

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'due' => $this->due,
            'tax' => $this->tax,
            'fees' => $this->fees,
            'model' => 'order',
            'amount' => $this->amount,
            'status' => $this->status,
            'payment' => $this->payment,
            'reference' => $this->reference,
            'delivery_method' => $this->delivery_method,
            'express_delivery' => $this->express_delivery,
            'user' => $this->when($with->contains('user'), new UserBasicDataResource($this->user)),
            'items' => $this->items,
            'transaction' => $this->when(!$without->contains('transaction'), new TransactionResource($this->transaction)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
