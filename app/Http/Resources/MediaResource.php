<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'mediaKey' => $this->media_key,
            'resourceName' => $this->resource_name,
            'resourceRecordKey' => $this->resource_record_key,
            'mediaType' => $this->media_type,
            'mediaCategory' => $this->media_category,
            'imageSizeDescription' => $this->image_size_description,
            'mediaUrl' => $this->media_url,
            'mediaCaption' => $this->media_caption,
            'mediaDescription' => $this->media_description,
            'isActive' => $this->is_active,
            'orderIndex' => $this->order_index,
            'modificationTimestamp' => $this->modification_timestamp?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String()
        ];
    }
}
