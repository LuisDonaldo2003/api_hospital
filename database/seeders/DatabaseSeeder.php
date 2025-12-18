<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $this->call([
        // Catálogos base del sistema
        DepartamentSeeder::class,
        ProfileSeeder::class,
        ContractTypeSeeder::class,
        PermissionsDemoSeeder::class,
        GenderSeeder::class,
        
        // Localización
        CountrySeeder::class,
        StateSeeder::class,
        MunicipalitySeeder::class,
        LocationSeeder::class,
        PriorityLocationSeeder::class,
        
        // Archivo
        ArchiveSeeder::class,
        
        // Catálogos Teaching (en orden correcto)
        TeachingModalidadesSeeder::class,      // 21 modalidades
        TeachingParticipacionesSeeder::class,  // 8 participaciones
        TeachingAreasSeeder::class,            // 6 áreas (usa tabla 'areas')
        TeachingCatalogsSeeder::class,         // 15 profesiones
        
        // Catálogos Appointments
        EspecialidadesSeeder::class,           // Especialidades médicas
        GeneralMedicalSeeder::class,           // Médicos Generales (Categorías)
        DoctoresSeeder::class,                 // Doctores del hospital
        AppointmentServicesSeeder::class,
        TeachingSeeder::class,
    ]);
}

}
