<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyMedia extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'media_key',
        'property_id',
        'resource_name',
        'resource_record_key',
        'media_type',
        'media_category',
        'image_size_description',
        'media_url',
        'media_caption',
        'media_description',
        'is_active',
        'order_index'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
        'modification_timestamp' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
            if (empty($media->media_key)) {
                $media->media_key = (string) Str::uuid();
            }
            if (empty($media->resource_record_key)) {
                $media->resource_record_key = (string) $media->property_id;
            }
        });
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function getMediaUrlAttribute($value)
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        return $value ? Storage::disk('public')->url($value) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('media_type', $type);
    }

    public function scopeOfResource($query, $resourceName)
    {
        return $query->where('resource_name', $resourceName);
    }
}
