<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'teaching_assistant_id',
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

    public function assistant()
    {
        return $this->belongsTo(TeachingAssistant::class, 'teaching_assistant_id');
    }
}
