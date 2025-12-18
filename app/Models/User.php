<?php

namespace App\Models;

use App\Models\Profile;
use App\Models\Departaments;
use App\Models\ContractType;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'mobile',
    'birth_date',
    'gender_id',
        'avatar',

        // Campos personalizados
        'profile',
        'curp',
        'ine',
        'rfc',
        'attendance_number',
        'professional_license',
        'funcion_real',
        'departament_id',
        'profile_id',
        'contract_type_id',
        'email_verified_at',
        'email_verification_code',
        'recovery_code',
        'recovery_code_expires_at',
        'session_id',
        'session_created_at',
        'email_change_code',
        'email_change_expires_at',
        'new_email_request',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'array',
    ];

    protected $appends = ['doctor_id'];

    // Relaciones
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function getDoctorIdAttribute()
    {
        return $this->doctor ? $this->doctor->id : null;
    }

    public function departament()
    {
        return $this->belongsTo(Departaments::class, 'departament_id');
    }

    // Relación con tabla genders
    public function gender()
    {
        return $this->belongsTo(\App\Models\Gender::class, 'gender_id');
    }

    public function profileRelation()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    /**
     * Relación con los logs de actividad
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Relación: Muchos a muchos con servicios de citas
     */
    public function appointmentServices()
    {
        return $this->belongsToMany(AppointmentService::class, 'user_appointment_services');
    }

    /**
     * Helper: Verifica si el usuario tiene acceso a un servicio específico
     */
    public function hasAccessToService($serviceId): bool
    {
        return $this->appointmentServices()->where('appointment_service_id', $serviceId)->exists();
    }

    /**
     * Helper: Obtiene los IDs de servicios accesibles por el usuario
     */
    public function getAccessibleServiceIds(): array
    {
        return $this->appointmentServices()->pluck('appointment_service_id')->toArray();
    }

    // JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Comprobar si el perfil está completo
    public function isProfileComplete(): bool
    {
        return $this->mobile &&
               $this->birth_date &&
               $this->gender &&
               $this->curp &&
               $this->rfc &&
               $this->ine &&
               $this->attendance_number &&
               $this->professional_license &&
               $this->funcion_real &&
               $this->departament_id &&
               $this->profile_id &&
               $this->contract_type_id;
    }

    public function isOnline()
    {
        $timestamp = \Cache::get('user-is-online-' . $this->id);
        if (!$timestamp) {
            return false;
        }
        
        // Verificar si el timestamp es reciente (últimos 90 segundos)
        return (now()->timestamp - $timestamp) < 90;
    }
}
