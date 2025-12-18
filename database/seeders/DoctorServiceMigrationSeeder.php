<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Especialidad;
use App\Models\GeneralMedical;
use App\Models\AppointmentService;

class DoctorServiceMigrationSeeder extends Seeder
{
    public function run()
    {
        $cnt = 0;
        $doctors = Doctor::all();
        foreach($doctors as $d) {
            // Update if missing OR if we want to enforce consistency (optional, but let's just do missing for now)
            if(!$d->appointment_service_id) {
                if($d->especialidad_id) {
                    $s = Especialidad::find($d->especialidad_id);
                    if ($s) {
                        $srv = AppointmentService::where('nombre', $s->nombre)->first();
                        if($srv) {
                            $d->appointment_service_id = $srv->id;
                            $d->save();
                            $cnt++;
                        }
                    }
                } elseif($d->general_medical_id) {
                    $g = GeneralMedical::find($d->general_medical_id);
                    if ($g) {
                        $srv = AppointmentService::where('nombre', $g->nombre)->first();
                        if($srv) {
                            $d->appointment_service_id = $srv->id;
                            $d->save();
                            $cnt++;
                        }
                    }
                }
            }
        }
        $this->command->info("Updated $cnt doctors.");
    }
}
