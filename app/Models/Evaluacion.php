<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $fillable = [
        'teaching_id',
        'fecha_inicio',
        'fecha_limite',
        'especialidad',
        'nombre',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_limite' => 'date',
    ];

    public function teaching()
    {
        return $this->belongsTo(Teaching::class);
    }
}
