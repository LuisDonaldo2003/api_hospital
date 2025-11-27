<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleFamily extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * Relación muchos a muchos con roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_family_role');
    }

    /**
     * Accessor: Obtiene las keywords únicas de esta familia basándose en los permisos de sus roles
     * Las keywords son los primeros segmentos de los permisos (antes del primer '_')
     * Por ejemplo: 'register_staff' -> 'register', 'list_users' -> 'list'
     */
    public function getKeywordsAttribute()
    {
        // Cargar roles con sus permisos si no están cargados
        if (!$this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        }

        $keywords = collect();

        // Extraer keywords de todos los permisos de los roles de esta familia
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                // Extraer el primer segmento (palabra antes del primer '_')
                $parts = explode('_', $permission->name);
                if (!empty($parts)) {
                    $keywords->push($parts[0]);
                }
                
                // También extraer el sufijo (después del primer '_') para coincidencias más específicas
                if (count($parts) > 1) {
                    $keywords->push(implode('_', array_slice($parts, 1)));
                }
            }
        }

        return $keywords->unique()->values()->toArray();
    }
}
