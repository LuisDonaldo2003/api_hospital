<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingAssistant extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'correo',
        'ei',
        'ef',
        'profesion',
        'area',
        'adscripcion',
    ];

    public function events()
    {
        return $this->hasMany(TeachingEvent::class);
    }
}
