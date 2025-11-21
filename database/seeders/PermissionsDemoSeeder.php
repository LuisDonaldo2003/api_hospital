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
        Permission::create(['guard_name' => 'api','name' => 'patient_dashboard']);
        Permission::create(['guard_name' => 'api','name' => 'archive_dashboard']);

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

        // Pacientes(archivo)
        Permission::create(['guard_name' => 'api','name' => 'add_archive']);
        Permission::create(['guard_name' => 'api','name' => 'list_archive']);
        Permission::create(['guard_name' => 'api','name' => 'edit_archive']);
        Permission::create(['guard_name' => 'api','name' => 'delete_archive']);
        Permission::create(['guard_name' => 'api','name' => 'export_archive']);
        Permission::create(['guard_name' => 'api','name' => 'backup_archive']);
        

        //Contracts
        Permission::create(['guard_name' => 'api','name' => 'add_contract']);
        Permission::create(['guard_name' => 'api','name' => 'list_contract']);
        Permission::create(['guard_name' => 'api','name' => 'edit_contract']);
        Permission::create(['guard_name' => 'api','name' => 'delete_contract']);

        //Lista de organigrama
        Permission::create(['guard_name' => 'api','name' => 'list_organization']);

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

        // Laravel Pulse Access
        Permission::create(['guard_name' => 'api','name' => 'access_pulse']);
        Permission::create(['guard_name' => 'api','name' => 'manage_pulse_access']);

        //Recursos Humanos (Personal)
        Permission::create(['guard_name' => 'api','name' => 'list_personal']);
        Permission::create(['guard_name' => 'api','name' => 'add_personal']);
        Permission::create(['guard_name' => 'api','name' => 'edit_personal']);
        Permission::create(['guard_name' => 'api','name' => 'delete_personal']);
        Permission::create(['guard_name' => 'api','name' => 'view_documents_personal']);
        Permission::create(['guard_name' => 'api','name' => 'toggle_status_personal']);

        //Creditos
        Permission::create(['guard_name' => 'api','name' => 'view_credits']);

        //PDF Compresor
        Permission::create(['guard_name' => 'api','name' => 'access_pdf_compressor']);

        //Enseñanza(Asistencias-Evaluaciones-Modalidades-Participaciones-Áreas)
        Permission::create(['guard_name' => 'api','name' => 'add_teaching']);
        Permission::create(['guard_name' => 'api','name' => 'list_teaching']);
        Permission::create(['guard_name' => 'api','name' => 'edit_teaching']);
        Permission::create(['guard_name' => 'api','name' => 'delete_teaching']);
        Permission::create(['guard_name' => 'api','name' => 'add_evaluation']);
        Permission::create(['guard_name' => 'api','name' => 'list_evaluation']);
        Permission::create(['guard_name' => 'api','name' => 'edit_evaluation']);
        Permission::create(['guard_name' => 'api','name' => 'delete_evaluation']);
        Permission::create(['guard_name' => 'api','name' => 'add_modality']);
        Permission::create(['guard_name' => 'api','name' => 'list_modality']);
        Permission::create(['guard_name' => 'api','name' => 'edit_modality']);
        Permission::create(['guard_name' => 'api','name' => 'delete_modality']);
        Permission::create(['guard_name' => 'api','name' => 'add_stakeholding']);
        Permission::create(['guard_name' => 'api','name' => 'list_stakeholding']);
        Permission::create(['guard_name' => 'api','name' => 'edit_stakeholding']);
        Permission::create(['guard_name' => 'api','name' => 'delete_stakeholding']);
        Permission::create(['guard_name' => 'api','name' => 'add_area']);
        Permission::create(['guard_name' => 'api','name' => 'list_area']);
        Permission::create(['guard_name' => 'api','name' => 'edit_area']);
        Permission::create(['guard_name' => 'api','name' => 'delete_area']);

        // --- ROLES Y USUARIOS ---

        // Director General (con acceso total)
        $roleSuperAdmin = Role::create(['guard_name' => 'api','name' => 'Director General']);
        
        // Asignar todos los permisos al Director General, incluyendo los de Pulse
        $roleSuperAdmin->givePermissionTo(Permission::all());

        $userSuper = User::factory()->create([
            'name' => 'Dr. Eric Aburto Álvarez',
            'email' => 'director@gmail.com',
            'password' => bcrypt('12345678')
        ]);
        $userSuper->assignRole($roleSuperAdmin);

        // Subdirector General (con acceso total)
        $roleSubAdmin = Role::create(['guard_name' => 'api','name' => 'Subdirector General']);
        
        // Asignar todos los permisos al Subdirector General, igual que el Director General
        $roleSubAdmin->givePermissionTo(Permission::all());

        $userSubdirector = User::updateOrCreate(
            ['email' => 'subdirector@gmail.com'],
            [
                'name' => 'Dr. Oswaldo Manuel Vergara Campos',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userSubdirector->assignRole($roleSubAdmin);

         // Developer con todos los permisos
        $roleInge = Role::create(['guard_name' => 'api','name' => 'Desarrollador']);

        $roleInge ->givePermissionTo(Permission::all());

        $userDeveloper = User::updateOrCreate(
            ['email' => 'monsterpark1000@gmail.com'],
            [
                'name' => 'Luis Donaldo López Martínez',
                'password' => Hash::make('Marimar97'),
                'email_verified_at' => now(),
            ]
        );
        $userDeveloper->assignRole($roleInge);

        $userDeveloper2 = User::updateOrCreate(
            ['email' => 'reynosozavaleta@gmail.com'],
            [
                'name' => 'Julián Reynoso Zavaleta',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userDeveloper2->assignRole($roleInge);

        $userDeveloper3 = User::updateOrCreate(
            ['email' => 'alevidalperez88@gmail.com'],
            [
                'name' => 'Alejandro Vidal Pérez',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userDeveloper3->assignRole($roleInge);

        $userDeveloper4 = User::updateOrCreate(
            ['email' => 'chulotono@gmail.com'],
            [
                'name' => 'José Antonio Herrera Chamú',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userDeveloper4->assignRole($roleInge);

        $userDeveloper5 = User::updateOrCreate(
            ['email' => 'peraltakike51@gmail.com'],
            [
                'name' => 'Enrique Ruiz Peralta',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userDeveloper5->assignRole($roleInge);


        //Archivo
        $roleArchive = Role::create(['guard_name' => 'api','name' => 'Archivo']);
        $ingeArchivePermissions = [
            'add_archive',
            'list_archive',
            'edit_archive',
            'delete_archive',
            'archive_dashboard',
            'backup_archive',
            'export_archive'
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


        //Estadistica
        $roleStatistics = Role::create(['guard_name' => 'api','name' => 'Estadistica']);
        $ingeStatisticsPermissions = [
            'add_archive',
            'list_archive',
            'edit_archive',
            'delete_archive',
            'archive_dashboard',
            'backup_archive',
            'export_archive'
        ];
        $roleStatistics->syncPermissions($ingeStatisticsPermissions);


         //Recursos Humanos
        $roleHumanResources = Role::create(['guard_name' => 'api','name' => 'Recursos Humanos']);
        $ingeHumanResourcesPermissions = [
            'admin_dashboard',
            'add_personal',
            'list_personal',
            'edit_personal',
            'delete_personal',
            'view_documents_personal',
            'toggle_status_personal',
            'access_pdf_compressor',
            'add_contract',
            'edit_contract',
            'list_contract',
            'delete_contract',
            'add_profile-m',
            'edit_profile-m',
            'list_profile-m',
            'delete_profile-m',
            'add_departament',
            'edit_departament',
            'list_departament',
            'delete_departament',
            
        ];
        $roleHumanResources->syncPermissions($ingeHumanResourcesPermissions);

         $userHumanResources = User::updateOrCreate(
            ['email' => 'recursoshumanos@gmail.com'],
            [
                'name' => 'Recursos Humanos User',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userHumanResources->assignRole($roleHumanResources);

        $userHumanResources2 = User::updateOrCreate(
            ['email' => 'venturamitzuko2@gmail.com'],
            [
                'name' => 'Mitzuko Ventura Hernández',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userHumanResources2->assignRole($roleHumanResources);

        $userHumanResources3 = User::updateOrCreate(
            ['email' => 'estrella.feliciano.granda@outlook.es'],
            [
                'name' => 'Dayanni Estrella Feliciano Granda',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userHumanResources3->assignRole($roleHumanResources);


        //Enseñanza
        $roleEnseñanza = Role::create(['guard_name' => 'api','name' => 'Enseñanza']);
        $EnseñanzaPermissions = [
            'admin_dashboard',
            'add_teaching',
            'list_teaching',
            'edit_teaching',
            'delete_teaching',
            'add_evaluation',
            'list_evaluation',
            'edit_evaluation',
            'delete_evaluation',
            'add_modality',
            'edit_modality',
            'list_modality',
            'delete_modality',
            'add_stakeholding',
            'edit_stakeholding',
            'list_stakeholding',
            'delete_stakeholding',
            'add_area',
            'edit_area',
            'list_area',
            'delete_area',        
        ];
        $roleEnseñanza->syncPermissions($EnseñanzaPermissions);

        $userEnseñanza = User::updateOrCreate(
            ['email' => 'ensenanza@gmail.com'],
            [
                'name' => 'Enseñanza User',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );
        $userEnseñanza->assignRole($roleEnseñanza);
    }
}
