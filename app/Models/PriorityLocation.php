<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriorityLocation extends Model
{
    protected $table = 'priority_locations';

    protected $fillable = [
        'location_id',
        'municipality_id', 
        'state_id',
        'location_name',
        'municipality_name',
        'state_name',
        'display_text',
        'normalized_name',
        'priority_level'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
