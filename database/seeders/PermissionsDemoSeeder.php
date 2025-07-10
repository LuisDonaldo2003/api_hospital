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

        // Users
        Permission::create(['guard_name' => 'api','name' => 'register_staff']);
        Permission::create(['guard_name' => 'api','name' => 'list_staff']);
        Permission::create(['guard_name' => 'api','name' => 'edit_staff']);
        Permission::create(['guard_name' => 'api','name' => 'delete_staff']);

        // Pacientes
        Permission::create(['guard_name' => 'api','name' => 'add_archive']);
        Permission::create(['guard_name' => 'api','name' => 'list_archive']);
        Permission::create(['guard_name' => 'api','name' => 'edit_archive']);
        Permission::create(['guard_name' => 'api','name' => 'delete_archive']);

        //Contracts
        Permission::create(['guard_name' => 'api','name' => 'add_contract']);
        Permission::create(['guard_name' => 'api','name' => 'list_contract']);
        Permission::create(['guard_name' => 'api','name' => 'edit_contract']);
        Permission::create(['guard_name' => 'api','name' => 'delete_contract']);

        //Profile
        Permission::create(['guard_name' => 'api','name' => 'add_profile-m']);
        Permission::create(['guard_name' => 'api','name' => 'list_profile-m']);
        Permission::create(['guard_name' => 'api','name' => 'edit_profile-m']);
        Permission::create(['guard_name' => 'api','name' => 'delete_profile-m']);

        // Departament
        Permission::create(['guard_name' => 'api','name' => 'add_departament']);
        Permission::create(['guard_name' => 'api','name' => 'list_departament']);
        Permission::create(['guard_name' => 'api','name' => 'edit_departament']);
        Permission::create(['guard_name' => 'api','name' => 'delete_departament']);

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


         // Doctor con permisos específicos
        $roleInge = Role::create(['guard_name' => 'api','name' => 'Developer']);

        $ingePermissions = [
            'admin_dashboard',
            'doctor_dashboard',
            'register_rol',
            'list_rol',
            'edit_rol',
            'delete_rol',
            'register_staff',
            'list_staff',
            'edit_staff',
            'delete_staff',
            'add_archive',
            'list_archive',
            'edit_archive',
            'delete_archive',
            'add_contract',
            'list_contract',
            'edit_contract',
            'delete_contract',
            'add_profile-m',
            'list_profile-m',
            'edit_profile-m',
            'delete_profile-m',
            'add_departament',
            'list_departament',
            'edit_departament',
            'delete_departament',
            'register_doctor',
            'list_doctor',
            'edit_doctor',
            'delete_doctor',
            'register_patient',
            'list_patient',
            'edit_patient',
            'delete_patient',
            'register_appointment',
            'list_appointment',
            'edit_appointment',
            'delete_appointment',
            'attention_appointment',
            'show_payment',
            'edit_payment',
            'delete_payment',
            'add_payment',
            'calendar',
        ];

        $roleInge->syncPermissions($ingePermissions);


        //Archivo
        $roleArchive = Role::create(['guard_name' => 'api','name' => 'Archivo ']);
        $ingeArchivePermissions = [
            'add_archive',
            'list_archive',
            'edit_archive',
            'delete_archive',
        ];
        $roleArchive->syncPermissions($ingeArchivePermissions);

         $userArchive = User::updateOrCreate(
            ['email' => 'archivo@gmail.com'],
            [
                'name' => 'Archivo User',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userArchive->assignRole($roleArchive);


    }
}
