<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    public function residential()
    {
        return $this->hasOne(ResidentialProperty::class);
    }

    public function commercial()
    {
        return $this->hasOne(CommercialProperty::class);
    }

    public function land()
    {
        return $this->hasOne(LandProperty::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class)->withPivot('details');
    }

    public function media()
    {
        return $this->hasMany(PropertyMedia::class);
    }
}
