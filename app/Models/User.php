<?php

namespace App\Models;

use App\Models\Profile;
use App\Models\Specialitie;
use App\Models\ContractType;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'mobile',
        'birth_date',
        'gender',
        'avatar',

        // Campos personalizados
        'profile',
        'curp',
        'ine',
        'rfc',
        'attendance_number',
        'professional_license',
        'funcion_real', // CORREGIDO aquí
        'specialitie_id',
        'profile_id',
        'contract_type_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relaciones

    public function speciality()
    {
        return $this->belongsTo(Specialitie::class, 'specialitie_id');
    }


    public function profileRelation()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
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
}
