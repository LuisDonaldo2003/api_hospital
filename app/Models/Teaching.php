<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teaching extends Model
{
    use HasFactory;

    protected $table = 'teachings';

    protected $fillable = [
        'correo',
        'ei',
        'ef',
        'profesion',
        'nombre',
        'area',
        'adscripcion',
        'nombre_evento',
        'tema',
        'fecha',
        'horas',
        'foja',
        'modalidad_id',
        'participacion_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class);
    }
}
