<?php

namespace App\Services;

use Carbon\Carbon;

class LicenseGeneratorService
{
    private const ENCRYPTION_KEY = 'hospital-license-key-2025';

    /**
     * Genera un archivo de licencia encriptado
     * 
     * @param array $hospitalData Datos del hospital
     * @param string $hardwareSignature Firma de hardware autorizada
     * @param string $licenseType Tipo de licencia: 'permanent', 'annual', 'monthly'
     * @param array $features Características habilitadas
     * @param string|null $validUntil Fecha de expiración (null para permanente)
     * @return array
     */
    public static function generateLicense(
        array $hospitalData,
        string $hardwareSignature,
        string $licenseType = 'permanent',
        array $features = [],
        ?string $validUntil = null
    ): array {
        try {
            // Validar datos requeridos
            if (empty($hospitalData['name'])) {
                throw new \Exception('El nombre del hospital es requerido');
            }

            // Calcular fecha de expiración automática si no se especifica
            if (!$validUntil && $licenseType !== 'permanent') {
                $validUntil = self::calculateDefaultExpiration($licenseType);
            }

            // Construir datos de la licencia
            $licenseData = [
                'institution' => $hospitalData['name'],
                'hospital_name' => $hospitalData['name'],
                'hospital_address' => $hospitalData['address'] ?? null,
                'city' => $hospitalData['city'] ?? null,
                'state' => $hospitalData['state'] ?? null,
                'country' => $hospitalData['country'] ?? 'México',
                'contact_name' => $hospitalData['contact_name'] ?? null,
                'contact_email' => $hospitalData['contact_email'] ?? null,
                'contact_phone' => $hospitalData['contact_phone'] ?? null,
                'hardware_signature' => $hardwareSignature,
                'valid_until' => $validUntil ?? 'PERMANENT',
                'features' => $features,
                'allowed_domain' => '*',
                'generated_at' => Carbon::now()->toIso8601String(),
                'license_version' => '2.0',
            ];

            // Generar firma digital
            $licenseData['signature'] = self::generateSignature($licenseData);

            // Encriptar datos
            $encryptedContent = self::encryptLicenseData($licenseData);

            if (!$encryptedContent) {
                throw new \Exception('Error al encriptar los datos de la licencia');
            }

            // Generar nombre de archivo
            $filename = self::generateFilename($hospitalData['name']);

            return [
                'success' => true,
                'content' => $encryptedContent,
                'filename' => $filename,
                'hospital_info' => [
                    'name' => $hospitalData['name'],
                    'type' => $licenseType,
                    'expires_at' => $validUntil ?? 'PERMANENT',
                    'features' => $features,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al generar licencia: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Genera el nombre del archivo de licencia
     */
    private static function generateFilename(string $hospitalName): string
    {
        // Limpiar nombre del hospital para usarlo como nombre de archivo
        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $hospitalName);
        $cleanName = strtoupper(substr($cleanName, 0, 30));
        $date = Carbon::now()->format('Ymd');
        
        return "{$cleanName}_{$date}.license";
    }

    /**
     * Calcula la fecha de expiración por defecto según el tipo
     */
    private static function calculateDefaultExpiration(string $type): string
    {
        return match($type) {
            'monthly' => Carbon::now()->addMonth()->toDateString(),
            'annual' => Carbon::now()->addYear()->toDateString(),
            default => 'PERMANENT',
        };
    }

    /**
     * Genera la firma digital de la licencia
     */
    private static function generateSignature(array $licenseData): string
    {
        $dataToSign = [
            'institution' => $licenseData['institution'] ?? '',
            'valid_until' => $licenseData['valid_until'] ?? '',
            'allowed_domain' => $licenseData['allowed_domain'] ?? '',
            'features' => $licenseData['features'] ?? [],
        ];

        return hash_hmac('sha256', json_encode($dataToSign), self::ENCRYPTION_KEY);
    }

    /**
     * Encripta los datos de la licencia
     */
    private static function encryptLicenseData(array $licenseData): ?string
    {
        try {
            $jsonData = json_encode($licenseData, JSON_UNESCAPED_UNICODE);
            
            if ($jsonData === false) {
                \Log::error('Failed to encode license data to JSON');
                return null;
            }

            $encryptedData = openssl_encrypt(
                $jsonData,
                'AES-256-CBC',
                self::ENCRYPTION_KEY,
                0,
                substr(hash('sha256', self::ENCRYPTION_KEY), 0, 16)
            );

            if ($encryptedData === false) {
                \Log::error('Failed to encrypt license data');
                return null;
            }

            return $encryptedData;
        } catch (\Exception $e) {
            \Log::error('Error encrypting license data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Valida que los datos de hospital sean correctos
     */
    public static function validateHospitalData(array $hospitalData): array
    {
        $errors = [];

        if (empty($hospitalData['name'])) {
            $errors[] = 'El nombre del hospital es requerido';
        }

        if (!empty($hospitalData['contact_email']) && !filter_var($hospitalData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email de contacto no es válido';
        }

        return $errors;
    }

    /**
     * Genera información de muestra para probar
     */
    public static function getSampleHospitalData(): array
    {
        return [
            'name' => 'Hospital General de Ejemplo',
            'address' => 'Calle Principal 123',
            'city' => 'Ciudad de México',
            'state' => 'CDMX',
            'country' => 'México',
            'contact_name' => 'Dr. Juan Pérez',
            'contact_email' => 'contacto@hospital.com',
            'contact_phone' => '+52 55 1234 5678',
        ];
    }

    /**
     * Lista de características disponibles
     */
    public static function getAvailableFeatures(): array
    {
        return [
            'module_teaching' => 'Módulo de Enseñanza',
            'module_contracts' => 'Módulo de Contratos',
            'module_personal' => 'Módulo de Personal',
            'module_dashboard' => 'Dashboard Completo',
            'module_evaluations' => 'Módulo de Evaluaciones',
            'backup_gdrive' => 'Respaldo a Google Drive',
            'multi_user' => 'Múltiples Usuarios',
        ];
    }
}
