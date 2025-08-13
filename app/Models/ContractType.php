<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContractType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'state',
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["created_at"] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set('America/Mexico_City');
        $this->attributes["updated_at"] = Carbon::now();
    }

}
