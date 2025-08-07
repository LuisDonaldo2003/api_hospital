<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archive extends Model
{
    use SoftDeletes;

    protected $table = 'archive';
    protected $primaryKey = 'archive_number';
    public $incrementing = false;

    protected $fillable = [
        'archive_number',
        'last_name_father',
        'last_name_mother',
        'name',
        'age',
        'gender_id',
        'contact_last_name_father',
        'contact_last_name_mother',
        'contact_name',
        'admission_date',
        'address',
        'location_id',
        'location_text',
    ];

    protected $dates = ['admission_date', 'created_at', 'updated_at', 'deleted_at'];

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}

