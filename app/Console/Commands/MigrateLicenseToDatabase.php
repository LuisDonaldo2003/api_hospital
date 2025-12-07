<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LicenseValidator;

class MigrateLicenseToDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:migrate-to-db
                            {--force : Migrar incluso si ya existe una licencia en la BD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra la licencia existente desde archivo a la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Migrando licencia desde archivo a base de datos...');
        $this->newLine();

        // Verificar si existe el archivo de licencia
        $licensePath = storage_path('app/license.key');
        
        if (!file_exists($licensePath)) {
            $this->error('❌ No se encontró archivo de licencia en: ' . $licensePath);
            $this->warn('💡 Use el comando para generar una nueva licencia o suba una desde el frontend.');
            return Command::FAILURE;
        }

        // Leer el archivo
        $licenseContent = file_get_contents($licensePath);
        
        if (!$licenseContent) {
            $this->error('❌ No se pudo leer el archivo de licencia.');
            return Command::FAILURE;
        }

        $this->info('📄 Archivo de licencia encontrado.');
        
        // Verificar si ya hay una licencia activa en la BD
        $existingLicense = \App\Models\License::where('is_active', true)->first();
        
        if ($existingLicense && !$this->option('force')) {
            $this->warn('⚠️  Ya existe una licencia activa en la base de datos:');
            $this->line("   Institución: {$existingLicense->institution}");
            $this->line("   Tipo: {$existingLicense->type}");
            $this->line("   Activada: {$existingLicense->activated_at->format('d/m/Y H:i')}");
            $this->newLine();
            
            if (!$this->confirm('¿Desea reemplazarla con la licencia del archivo?', false)) {
                $this->info('❌ Migración cancelada.');
                return Command::CANCELLED;
            }
        }

        $this->info('🔐 Procesando licencia...');
        
        // Activar la licencia usando el servicio
        $result = LicenseValidator::activateLicense(
            $licenseContent,
            null, // Sin usuario (migración automática)
            'CLI-Migration'
        );

        if (!$result['success']) {
            $this->error('❌ Error al migrar la licencia:');
            $this->line('   ' . $result['message']);
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✅ ¡Licencia migrada exitosamente!');
        $this->newLine();
        
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📋 INFORMACIÓN DE LA LICENCIA:");
        $this->line("   Institución: {$result['license']['institution']}");
        $this->line("   Tipo: " . strtoupper($result['license']['type']));
        $this->line("   Expira: {$result['license']['expires_at']}");
        
        if ($result['license']['days_remaining'] !== null) {
            $this->line("   Días restantes: {$result['license']['days_remaining']}");
        } else {
            $this->line("   Días restantes: <fg=green>ILIMITADO (Permanente)</>");
        }
        
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return Command::SUCCESS;
    }
}
