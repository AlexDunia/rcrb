<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentialProperty extends Model
{
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
