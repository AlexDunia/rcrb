<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'address',
        'city',
        'state',
        'zip_code',
        'type',
        'status',
        'bedrooms',
        'bathrooms',
        'size',
        'year_built',
        'virtual_tour_url',
        'neighborhood',
        'latitude',
        'longitude',
        'agent_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'bathrooms' => 'decimal:1',
        'size' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'year_built' => 'integer',
        'bedrooms' => 'integer',
    ];

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
        return $this->belongsToMany(Amenity::class, 'property_amenities')
                    ->withPivot('details')
                    ->withTimestamps();
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'property_features')
                    ->withTimestamps();
    }

    public function media()
    {
        return $this->hasMany(PropertyMedia::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
