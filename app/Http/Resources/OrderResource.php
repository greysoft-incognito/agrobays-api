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
        $with = is_array($request->with) ? $request->with : explode(',', $request->with);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'due' => $this->due,
            'tax' => $this->tax,
            'fees' => $this->fees,
            'amount' => $this->amount,
            'status' => $this->status,
            'payment' => $this->payment,
            'reference' => $this->reference,
            'delivery_method' => $this->delivery_method,
            'transaction' => new TransactionResource($this->transaction),
            'items' => $this->items,
            'user' => $this->when(in_array('user', $with), new UserBasicDataResource($this->user)),
            'model' => 'order',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        \App\Services\AppInfo::api();
    }
}