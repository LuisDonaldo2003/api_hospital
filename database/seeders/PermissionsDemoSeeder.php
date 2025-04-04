<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionsDemoSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- PERMISOS ---
        Permission::create(['guard_name' => 'api','name' => 'admin_dashboard']);
        Permission::create(['guard_name' => 'api','name' => 'doctor_dashboard']);

        // Roles y Permisos
        Permission::create(['guard_name' => 'api','name' => 'register_rol']);
        Permission::create(['guard_name' => 'api','name' => 'list_rol']);
        Permission::create(['guard_name' => 'api','name' => 'edit_rol']);
        Permission::create(['guard_name' => 'api','name' => 'delete_rol']);

        // Staff
        Permission::create(['guard_name' => 'api','name' => 'register_staff']);
        Permission::create(['guard_name' => 'api','name' => 'list_staff']);
        Permission::create(['guard_name' => 'api','name' => 'edit_staff']);
        Permission::create(['guard_name' => 'api','name' => 'delete_staff']);

        // Especialidades
        Permission::create(['guard_name' => 'api','name' => 'register_specialty']);
        Permission::create(['guard_name' => 'api','name' => 'list_specialty']);
        Permission::create(['guard_name' => 'api','name' => 'edit_specialty']);
        Permission::create(['guard_name' => 'api','name' => 'delete_specialty']);

        // Doctores
        Permission::create(['guard_name' => 'api','name' => 'register_doctor']);
        Permission::create(['guard_name' => 'api','name' => 'list_doctor']);
        Permission::create(['guard_name' => 'api','name' => 'edit_doctor']);
        Permission::create(['guard_name' => 'api','name' => 'delete_doctor']);
        Permission::create(['guard_name' => 'api','name' => 'profile_doctor']);

        // Pacientes
        Permission::create(['guard_name' => 'api','name' => 'register_patient']);
        Permission::create(['guard_name' => 'api','name' => 'list_patient']);
        Permission::create(['guard_name' => 'api','name' => 'edit_patient']);
        Permission::create(['guard_name' => 'api','name' => 'delete_patient']);
        Permission::create(['guard_name' => 'api','name' => 'profile_patient']);

        // Citas
        Permission::create(['guard_name' => 'api','name' => 'register_appointment']);
        Permission::create(['guard_name' => 'api','name' => 'list_appointment']);
        Permission::create(['guard_name' => 'api','name' => 'edit_appointment']);
        Permission::create(['guard_name' => 'api','name' => 'delete_appointment']);
        Permission::create(['guard_name' => 'api','name' => 'attention_appointment']);

        // Pagos
        Permission::create(['guard_name' => 'api','name' => 'show_payment']);
        Permission::create(['guard_name' => 'api','name' => 'edit_payment']);
        Permission::create(['guard_name' => 'api','name' => 'delete_payment']);
        Permission::create(['guard_name' => 'api','name' => 'add_payment']);

        // Calendario
        Permission::create(['guard_name' => 'api','name' => 'calendar']);


        // --- ROLES Y USUARIOS ---

        // Super-Admin (con acceso total)
        $roleSuperAdmin = Role::create(['guard_name' => 'api','name' => 'Super-Admin']);

        $userSuper = User::factory()->create([
            'name' => 'Super-Admin User',
            'email' => 'prueba@gmail.com',
            'password' => bcrypt('12345678')
        ]);
        $userSuper->assignRole($roleSuperAdmin);

        // Doctor con permisos específicos
        $roleDoctor = Role::create(['guard_name' => 'api','name' => 'Doctor']);

        $doctorPermissions = [
            'list_doctor',
            'edit_doctor',
            'profile_doctor',
            'register_patient',
            'list_patient',
            'edit_patient',
            'delete_patient',
            'profile_patient',
            'register_appointment',
            'list_appointment',
            'edit_appointment',
            'delete_appointment',
            'attention_appointment',
            'calendar',
        ];

        $roleDoctor->syncPermissions($doctorPermissions);

        $userDoctor = User::updateOrCreate(
            ['email' => 'doctor@gmail.com'],
            [
                'name' => 'Doctor User',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userDoctor->assignRole($roleDoctor);
    }
}
