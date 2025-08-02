<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Favorite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'property_type',
        'property_id',
        'property_data'
    ];

    protected $casts = [
        'property_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function localProperty()
    {
        return $this->belongsTo(Property::class, 'property_id')
            ->when($this->property_type === 'local');
    }
}
