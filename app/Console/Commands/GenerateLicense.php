<?php

namespace App\Console\Commands;

use App\Services\LicenseGeneratorService;
use App\Services\HardwareSignatureService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:generate
                            {--hospital= : Nombre del hospital}
                            {--address= : Dirección del hospital}
                            {--city= : Ciudad}
                            {--state= : Estado}
                            {--contact-name= : Nombre del contacto}
                            {--contact-email= : Email del contacto}
                            {--contact-phone= : Teléfono del contacto}
                            {--hardware= : Firma de hardware (opcional, se genera automáticamente si no se especifica)}
                            {--type=permanent : Tipo de licencia (permanent, annual, monthly)}
                            {--expires= : Fecha de expiración (YYYY-MM-DD)}
                            {--features=* : Características a habilitar}
                            {--output= : Directorio de salida (por defecto: storage/app/licenses)}
                            {--interactive : Modo interactivo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un archivo de licencia para un hospital';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('  Generador de Licencias - Sistema Hospital  ');
        $this->info('==============================================');
        $this->newLine();

        // Modo interactivo o por parámetros
        if ($this->option('interactive')) {
            $hospitalData = $this->collectHospitalDataInteractive();
            $hardwareSignature = $this->option('hardware') ?? $this->collectHardwareSignature();
            $licenseType = $this->collectLicenseType();
            $validUntil = $this->collectExpirationDate($licenseType);
            $features = $this->collectFeatures();
        } else {
            $hospitalData = $this->collectHospitalDataFromOptions();
            
            if (empty($hospitalData['name'])) {
                $this->error('Error: El nombre del hospital es requerido. Use --hospital="Nombre del Hospital"');
                $this->info('O ejecute en modo interactivo: php artisan license:generate --interactive');
                return 1;
            }

            $hardwareSignature = $this->option('hardware') ?? $this->generateDefaultHardwareSignature();
            $licenseType = $this->option('type');
            $validUntil = $this->option('expires');
            $features = $this->option('features') ?? [];
        }

        // Validar datos
        $errors = LicenseGeneratorService::validateHospitalData($hospitalData);
        if (!empty($errors)) {
            $this->error('Errores de validación:');
            foreach ($errors as $error) {
                $this->error('  - ' . $error);
            }
            return 1;
        }

        // Mostrar resumen
        $this->showSummary($hospitalData, $hardwareSignature, $licenseType, $validUntil, $features);

        if

 (!$this->confirm('¿Desea generar esta licencia?', true)) {
            $this->warn('Operación cancelada');
            return 0;
        }

        // Generar licencia
        $this->info('Generando licencia...');
        
        $result = LicenseGeneratorService::generateLicense(
            $hospitalData,
            $hardwareSignature,
            $licenseType,
            $features,
            $validUntil
        );

        if (!$result['success']) {
            $this->error('Error al generar la licencia: ' . $result['message']);
            return 1;
        }

        // Guardar archivo
        $outputDir = $this->option('output') ?? 'licenses';
        $path = $outputDir . '/' . $result['filename'];
        
        Storage::put($path, $result['content']);
        $fullPath = storage_path('app/' . $path);

        $this->newLine();
        $this->info('✓ Licencia generada exitosamente!');
        $this->info('  Archivo: ' . $fullPath);
        $this->newLine();
        $this->info('Información de la licencia:');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Hospital', $result['hospital_info']['name']],
                ['Tipo', $result['hospital_info']['type']],
                ['Expira', $result['hospital_info']['expires_at']],
                ['Características', implode(', ', $result['hospital_info']['features']) ?: 'Ninguna'],
            ]
        );

        return 0;
    }

    private function collectHospitalDataFromOptions(): array
    {
        return [
            'name' => $this->option('hospital'),
            'address' => $this->option('address'),
            'city' => $this->option('city'),
            'state' => $this->option('state'),
            'country' => 'México',
            'contact_name' => $this->option('contact-name'),
            'contact_email' => $this->option('contact-email'),
            'contact_phone' => $this->option('contact-phone'),
        ];
    }

    private function collectHospitalDataInteractive(): array
    {
        $this->info('Ingrese los datos del hospital:');
        
        return [
            'name' => $this->ask('Nombre del hospital', 'Hospital General'),
            'address' => $this->ask('Dirección', 'Calle Principal 123'),
            'city' => $this->ask('Ciudad', 'Ciudad de México'),
            'state' => $this->ask('Estado', 'CDMX'),
            'country' => 'México',
            'contact_name' => $this->ask('Nombre del contacto', 'Dr. Juan Pérez'),
            'contact_email' => $this->ask('Email del contacto', 'contacto@hospital.com'),
            'contact_phone' => $this->ask('Teléfono del contacto', '+52 55 1234 5678'),
        ];
    }

    private function collectHardwareSignature(): string
    {
        $this->newLine();
        $this->info('Firma de hardware:');
        $this->info('Presione Enter para usar la firma del servidor actual,');
        $this->info('o ingrese la firma de hardware del servidor de destino.');
        
        $signature = $this->ask('Firma de hardware');
        
        if (empty($signature)) {
            $signature = $this->generateDefaultHardwareSignature();
            $this->info('Usando firma del servidor actual: ' . substr($signature, 0, 16) . '...');
        }
        
        return $signature;
    }

    private function generateDefaultHardwareSignature(): string
    {
        $this->warn('ADVERTENCIA: No se especificó firma de hardware.');
        $this->warn('Se generará una firma basada en el servidor ACTUAL.');
        $this->warn('Esta licencia SOLO funcionará en este servidor.');
        
        $signature = HardwareSignatureService::generateSignature();
        $hwInfo = HardwareSignatureService::getHardwareInfo();
        
        $this->newLine();
        $this->info('Información del hardware actual:');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Hostname', $hwInfo['hostname']],
                ['MAC Address', $hwInfo['mac_address']],
                ['System UUID', substr($hwInfo['system_uuid'], 0, 16) . '...'],
                ['Firma', substr($signature, 0, 32) . '...'],
            ]
        );
        
        return $signature;
    }

    private function collectLicenseType(): string
    {
        return $this->choice(
            '¿Tipo de licencia?',
            ['permanent' => 'Permanente', 'annual' => 'Anual', 'monthly' => 'Mensual'],
            'permanent'
        );
    }

    private function collectExpirationDate(string $type): ?string
    {
        if ($type === 'permanent') {
            return null;
        }

        $default = $type === 'annual' 
            ? now()->addYear()->format('Y-m-d')
            : now()->addMonth()->format('Y-m-d');

        return $this->ask('Fecha de expiración (YYYY-MM-DD)', $default);
    }

    private function collectFeatures(): array
    {
        $availableFeatures = LicenseGeneratorService::getAvailableFeatures();
        
        $this->newLine();
        $this->info('Características disponibles:');
        foreach ($availableFeatures as $key => $description) {
            $this->info("  - {$key}: {$description}");
        }
        
        $this->newLine();
        $featuresInput = $this->ask(
            'Características a habilitar (separadas por coma, o "all" para todas)',
            'all'
        );

        if (strtolower($featuresInput) === 'all') {
            return array_keys($availableFeatures);
        }

        return array_map('trim', explode(',', $featuresInput));
    }

    private function showSummary(
        array $hospitalData,
        string $hardwareSignature,
        string $licenseType,
        ?string $validUntil,
        array $features
    ): void {
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('  Resumen de la Licencia');
        $this->info('═══════════════════════════════════════');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Hospital', $hospitalData['name']],
                ['Dirección', $hospitalData['address'] ?? 'N/A'],
                ['Ciudad', $hospitalData['city'] ?? 'N/A'],
                ['Estado', $hospitalData['state'] ?? 'N/A'],
                ['Contacto', $hospitalData['contact_name'] ?? 'N/A'],
                ['Email', $hospitalData['contact_email'] ?? 'N/A'],
                ['Teléfono', $hospitalData['contact_phone'] ?? 'N/A'],
                ['Tipo de Licencia', ucfirst($licenseType)],
                ['Expira', $validUntil ?? 'NUNCA (Permanente)'],
                ['Características', implode(', ', $features) ?: 'Ninguna'],
                ['Firma HW', substr($hardwareSignature, 0, 32) . '...'],
            ]
        );
    }
}
