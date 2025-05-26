<?php

namespace App\Models;

use App\Models\Archive;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    protected $table = 'genders';
    public $timestamps = false;

    public function archives()
    {
        return $this->hasMany(Archive::class);
    }
}
