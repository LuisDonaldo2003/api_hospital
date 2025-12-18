<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentService extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'orden',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer'
    ];

    /**
     * Relación: Un servicio tiene muchos doctores
     */
    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'appointment_service_id');
    }

    /**
     * Relación: Un servicio tiene muchas citas
     */
    public function citas()
    {
        return $this->hasMany(Cita::class, 'appointment_service_id');
    }

    /**
     * Relación: Muchos a muchos con usuarios
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_appointment_services');
    }

    /**
     * Scope: Solo servicios activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Ordenados
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('orden');
    }
}
