<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feature extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category'
    ];

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_features')
                    ->withTimestamps();
    }
}
