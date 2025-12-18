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
        'user_id', // Relación con el usuario (login)
        'appointment_service_id', // Nueva columna
        'especialidad_id', // Deprecated - mantener temporalmente
        'general_medical_id', // Deprecated - mantener temporalmente
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

    protected $appends = ['service_name']; // Cambiado de especialidad_nombre

    /**
     * Relación con servicio de citas
     */
    public function appointmentService()
    {
        return $this->belongsTo(AppointmentService::class, 'appointment_service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * DEPRECATED: Relación con especialidad (mantener para compatibilidad temporal)
     */
    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    /**
     * DEPRECATED: Relación con médico general (mantener para compatibilidad temporal)
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
     * Accessor para nombre del servicio
     */
    public function getServiceNameAttribute()
    {
        if ($this->appointmentService) {
            return $this->appointmentService->nombre;
        }

        // Fallback a sistema antiguo (temporal)
        if ($this->especialidad) {
            return $this->especialidad->nombre;
        }
        
        if ($this->generalMedical) {
            return $this->generalMedical->nombre;
        }

        return 'Sin servicio';
    }

    /**
     * DEPRECATED: Accessor antiguo (mantener para compatibilidad)
     */
    public function getEspecialidadNombreAttribute()
    {
        return $this->service_name;
    }

    /**
     * Scope para doctores activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por servicio de citas
     */
    public function scopeByService($query, $serviceId)
    {
        return $query->where('appointment_service_id', $serviceId);
    }

    /**
     * DEPRECATED: Scope para buscar por especialidad (mantener temporalmente)
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
