<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'especialidades';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con doctores
     */
    public function doctores()
    {
        return $this->hasMany(Doctor::class, 'especialidad_id');
    }
    
    // Alias para compatibilidad
    public function doctors()
    {
        return $this->doctores();
    }
}
