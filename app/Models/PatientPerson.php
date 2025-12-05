<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientPerson extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patient_persons';

    protected $fillable = [
        'name',
        'surname',
        'birth_date',
        'gender_id',
        'email',
        'mobile',
        'address',
    ];
}
