<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'municipality_id',
        'status',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function archives()
    {
        return $this->hasMany(Archive::class);
    }
}
