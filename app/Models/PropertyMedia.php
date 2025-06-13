<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PropertyMedia extends Model
{
    protected $fillable = ['path'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function getUrlAttribute()
    {
        return Storage::disk('s3')->url($this->path);
    }
}
