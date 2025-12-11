<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cita extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'citas';

    protected $fillable = [
        'paciente_id',
        'folio_expediente',
        'paciente_nombre',
        'fecha_nacimiento',
        'numero_cel',
        'procedencia',
        'tipo_cita',
        'turno',
        'paciente_telefono',
        'paciente_email',
        'doctor_id',
        'fecha',
        'hora',
        'motivo',
        'observaciones',
        'estado',
        'fecha_cancelacion',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'fecha_cancelacion' => 'datetime',
    ];

    protected $appends = ['paciente', 'doctor'];

    /**
     * Relación con doctor
     */
    public function doctorRelation()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Relación con paciente (si existe)
     */
    public function pacienteRelation()
    {
        return $this->belongsTo(Patient::class, 'paciente_id');
    }

    /**
     * Accessor para datos del paciente
     */
    public function getPacienteAttribute()
    {
        if ($this->paciente_id && $this->pacienteRelation) {
            $paciente = $this->pacienteRelation;
            $person = $paciente->person;
            return [
                'id' => $paciente->id,
                'nombre' => $person ? "{$person->name} {$person->surname}" : $this->paciente_nombre,
                'telefono' => $person ? $person->mobile : $this->paciente_telefono,
                'email' => $person ? $person->email : $this->paciente_email,
            ];
        }

        return [
            'id' => null,
            'nombre' => $this->paciente_nombre ?? 'Sin nombre',
            'telefono' => $this->paciente_telefono,
            'email' => $this->paciente_email,
        ];
    }

    /**
     * Accessor para datos del doctor
     */
    public function getDoctorAttribute()
    {
        if ($this->doctorRelation) {
            $doctor = $this->doctorRelation;
            return [
                'id' => $doctor->id,
                'nombre' => $doctor->nombre_completo,
                'especialidad' => $doctor->especialidad_nombre,
            ];
        }

        return null;
    }

    /**
     * Scope para citas de un doctor
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope para citas por estado
     */
    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para citas por rango de fechas
     */
    public function scopeByDateRange($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para citas del día
     */
    public function scopeToday($query)
    {
        return $query->whereDate('fecha', today());
    }
}
