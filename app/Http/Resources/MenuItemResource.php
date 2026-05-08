<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'target' => $this->target,
            'type' => $this->type,
            'order' => $this->order,
            'attributes' => $this->attributes,
            'children' => MenuItemResource::collection($this->whenLoaded('allChildren')),
        ];
    }
}

