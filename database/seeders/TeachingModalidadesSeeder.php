<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachingModalidadesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['codigo' => 'CURSO.LOC.VIR.', 'nombre' => 'CURSO LOCAL VIRTUAL'],
            ['codigo' => 'CURSO.LOC.PRES.', 'nombre' => 'CURSO LOCAL PRESENCIAL'],
            ['codigo' => 'CURSO.LOC.MIX.', 'nombre' => 'CURSO LOCAL MIXTO'],
            ['codigo' => 'CURSO.EST.VIRT.', 'nombre' => 'CURSO ESTATAL VIRTUAL'],
            ['codigo' => 'CURSO.EST.PRES.', 'nombre' => 'CURSO ESTATAL PRESENCIAL'],
            ['codigo' => 'CLASES.MIP', 'nombre' => 'CLASES MIPS PRESENCIAL'],
            ['codigo' => 'SESION.EPSS.VIRT.', 'nombre' => 'SESION EPSS VIRTUAL'],
            ['codigo' => 'SESION.EPSS.PRES.', 'nombre' => 'SESION EPSS PRESENCIAL'],
            ['codigo' => 'SESION.EPSS.MIX.', 'nombre' => 'SESION EPSS MIXTO'],
            ['codigo' => 'SESION.ENF.VIRT.', 'nombre' => 'SESION DE ENFERMERIA VIRTUAL'],
            ['codigo' => 'SESION.ENF.PRES.', 'nombre' => 'SESION DE ENFERMERIA PRESENCIAL'],
            ['codigo' => 'SESION.ENF.MIXTO.', 'nombre' => 'SESION DE ENFERMERIA MIXTO'],
            ['codigo' => 'CURSO.FED.VIRT.', 'nombre' => 'CURSO FEDERAL VIRTUAL'],
            ['codigo' => 'SESION.C.CATE.P', 'nombre' => 'SESION CLINICA DE CATETERES PRESENCIAL'],
            ['codigo' => 'SESION.C.CATE.M', 'nombre' => 'SESION CLINICA DE CATETERES MIXTO'],
            ['codigo' => 'VC.TELEM.ESTA', 'nombre' => 'VIDEOCONFERENCIA TELEMEDICINA ESTATAL'],
            ['codigo' => 'VC.TELEM.LOCAL', 'nombre' => 'VIDEOCONFERENCIA TELEMEDICINA LOCAL'],
            ['codigo' => 'CAP.LOC.V', 'nombre' => 'CAPACITACION LOCAL VIRTUAL'],
            ['codigo' => 'CAP.LOC.P', 'nombre' => 'CAPACITACION LOCAL PRESENCIAL'],
            ['codigo' => 'CAP.LOC.M', 'nombre' => 'CAPACITACION LOCAL MIXTO'],
            ['codigo' => 'CAP.PRES.SERV.', 'nombre' => 'CAPACITACION PRESENCIAL EN SERVICIO'],
        ];

        foreach ($items as $it) {
            DB::table('modalidades')->updateOrInsert(['codigo' => $it['codigo']], $it + ['activo' => 1]);
        }
    }
}
