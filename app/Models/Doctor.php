<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'doctors';

    protected $fillable = [
        'nombre_completo',
        'especialidad_id',
        'general_medical_id',
        'turno',
        'hora_inicio_matutino',
        'hora_fin_matutino',
        'hora_inicio_vespertino',
        'hora_fin_vespertino',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected $appends = ['especialidad_nombre'];

    /**
     * Relación con especialidad
     */
    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    /**
     * Relación con médico general (categoría)
     */
    public function generalMedical()
    {
        return $this->belongsTo(GeneralMedical::class, 'general_medical_id');
    }

    /**
     * Relación con citas
     */
    public function citas()
    {
        return $this->hasMany(Cita::class, 'doctor_id');
    }

    /**
     * Accessor para nombre de especialidad o médico general
     */
    public function getEspecialidadNombreAttribute()
    {
        if ($this->especialidad) {
            return $this->especialidad->nombre;
        }
        
        if ($this->generalMedical) {
            return $this->generalMedical->nombre;
        }

        return 'Sin especialidad';
    }

    /**
     * Scope para doctores activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por especialidad
     */
    public function scopeByEspecialidad($query, $especialidadId)
    {
        return $query->where('especialidad_id', $especialidadId);
    }

    /**
     * Scope para buscar por turno
     */
    public function scopeByTurno($query, $turno)
    {
        return $query->where('turno', $turno);
    }
}
