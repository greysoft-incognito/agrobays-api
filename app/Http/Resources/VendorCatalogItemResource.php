<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorCatalogItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $with = str($request->with)->remove(' ')->explode(',');

        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'vendor_id' => $this->vendor_id,
            'quantity' => $this->quantity,
            'type' => str(get_class($this->catalogable))->afterLast('\\'),
            $this->mergeWhen(true, fn () => [
                'name' => $this->catalogable->name,
                'price' => $this->catalogable->price ?? 0,
                'weight' => ($this->catalogable->weight ?? 0) . ($this->catalogable->unit ?? 'kg'),
                'image_url' => $this->catalogable->image_url,
                'available' => $this->catalogable->available,
                'price_total' => round((float) ($this->catalogable->price ?? 0) * ($this->quantity ? $this->quantity : 1), 2),
                'description' => $this->catalogable->description,
            ]),
            'vendor' => $this->when($with->contains('vendor'), $this->vendor),
        ];
    }
}
