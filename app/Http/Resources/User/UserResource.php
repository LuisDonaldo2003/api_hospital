<?php

namespace App\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id"                    => $this->id,
            "name"                  => $this->name,
            "surname"               => $this->surname,
            "email"                 => $this->email,
            "birth_date"            => $this->birth_date ? Carbon::parse($this->birth_date)->format("Y-m-d") : null,
            "gender_id"             => $this->gender_id,
            "gender"                => optional($this->gender)->name ?? $this->gender,
            "mobile"                => $this->mobile,
            "avatar"                => $this->avatar ? asset('storage/' . $this->avatar) : null,
            "created_at"            => $this->created_at->format("Y/m/d"),
            "role"                  => $this->roles->first(), // Objeto rol principal
            "roles"                 => $this->roles->map(function($role) {
                                    return [
                                        'id' => $role->id,
                                        'name' => $role->name
                                    ];
                                })->values(), // Array de roles

            // Campos personalizados
            "curp"                  => $this->curp,
            "ine"                   => $this->ine,
            "rfc"                   => $this->rfc,
            "attendance_number"     => $this->attendance_number,
            "professional_license"  => $this->professional_license,
            "funcion_real"          => $this->funcion_real,

            // Relaciones con nombre
            "departament"           => optional($this->departament)->name,
            "departament_id"        => $this->departament_id,

            "profile_relation"      => optional($this->profileRelation)->name,
            "profile_id"            => $this->profile_id,

            "contract_type"         => optional($this->contractType)->name,
            "contract_type_id"      => $this->contract_type_id,

            'online'                => $this->isOnline(),
            "settings"              => $this->settings,
            "doctor_id"             => $this->doctor_id,
        ];
    }
}
