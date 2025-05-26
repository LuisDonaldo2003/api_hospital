<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    protected $table = 'municipalities';

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
