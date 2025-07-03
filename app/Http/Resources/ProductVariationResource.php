<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /* format attributs to look like this
          {
             "size": "largee",
             "color": "red",
           },
        */
        $attributes = [];
        if ($this->relationLoaded('attributes')) {
            foreach ($this->attributes as $attribute) {
                $attributes[$attribute->name] = $attribute->pivot->value;
            }
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => $this->price,
            'attributes' => $attributes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
