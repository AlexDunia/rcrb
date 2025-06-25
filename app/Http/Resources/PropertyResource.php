<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zipCode' => $this->zip_code,
            'type' => $this->type,
            'status' => $this->status,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'size' => $this->size,
            'yearBuilt' => $this->year_built,
            'images' => $this->media->pluck('url')->toArray(),
            'features' => $this->features->pluck('name')->toArray(),
            'amenities' => $this->amenities->pluck('name')->toArray(),
            'agent' => $this->when($this->agent, [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
                'photo' => $this->agent->profile_photo_url,
                'phone' => $this->agent->phone,
                'email' => $this->agent->email,
                'rating' => $this->agent->rating
            ]),
            'location' => [
                'neighborhood' => $this->neighborhood,
                'coordinates' => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude
                ]
            ],
            'virtualTour' => $this->virtual_tour_url,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at
        ];
    }
}
