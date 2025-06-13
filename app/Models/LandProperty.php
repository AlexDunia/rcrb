<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandProperty extends Model
{
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
