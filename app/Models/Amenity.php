<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    public function properties()
    {
        return $this->belongsToMany(Property::class)->withPivot('details');
    }
}
