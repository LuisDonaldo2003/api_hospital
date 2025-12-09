<?php

namespace App\Services;

use App\Models\License;
use App\Models\LicenseActivation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LicenseValidator
{
    private const ENCRYPTION_KEY = 'hospital-license-key-2025';
    private const CACHE_KEY = 'system_license_status';
    private const CACHE_DURATION = 300; // 5 minutos en segundos

    /**
     * Verifica si existe una licencia válida en la base de datos
     */
    public static function isValid(): bool
    {
        try {
            // Usar caché para evitar consultas constantes a la BD
            return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
                $license = License::valid()->first();
                
                if (!$license) {
                    return false;
                }

                // Actualizar última verificación
                $license->markAsChecked();

                return true;
            });
        } catch (\Exception $e) {
            \Log::error('Error validating license: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene la licencia activa actual
     */
    public static function getActiveLicense(): ?License
    {
        try {
            return License::valid()->first();
        } catch (\Exception $e) {
            \Log::error('Error getting active license: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene información de la licencia activa
     */
    public static function getLicenseInfo(): ?array
    {
        try {
            $license = self::getActiveLicense();
            
            if (!$license) {
                return null;
            }

            return [
                'institution' => $license->institution ?? 'N/A',
                'type' => $license->type,
                'activated_at' => $license->activated_at?->format('Y-m-d H:i:s'),
                'expires_at' => $license->expires_at?->format('Y-m-d H:i:s') ?? 'PERMANENT',
                'allowed_domain' => $license->allowed_domain ?? '*',
                'features' => $license->features ?? [],
                'is_valid' => $license->isValid(),
                'days_remaining' => $license->daysRemaining(),
                'last_checked' => $license->last_checked_at?->diffForHumans(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting license info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Procesa y activa un archivo de licencia
     */
    public static function activateLicense(string $licenseContent, ?int $userId = null, ?string $ip = null): array
    {
        try {
            // Desencriptar el archivo de licencia
            $licenseData = self::decryptLicenseFile($licenseContent);
            
            if (!$licenseData) {
                return [
                    'success' => false,
                    'message' => 'No se pudo desencriptar el archivo de licencia'
                ];
            }

            // Validar estructura del archivo
            if (!self::validateLicenseStructure($licenseData)) {
                return [
                    'success' => false,
                    'message' => 'Estructura de licencia inválida'
                ];
            }

            // Verificar firma digital
            if (!self::verifySignature($licenseData)) {
                return [
                    'success' => false,
                    'message' => 'Firma digital inválida. El archivo ha sido alterado.'
                ];
            }

            // Obtener firma de hardware del servidor actual
            $currentHardwareSignature = HardwareSignatureService::generateSignature();
            $hardwareInfo = HardwareSignatureService::getHardwareInfo();

            // Validar que la firma de hardware coincida (si existe en la licencia)
            if (isset($licenseData['hardware_signature'])) {
                if (!hash_equals($licenseData['hardware_signature'], $currentHardwareSignature)) {
                    return [
                        'success' => false,
                        'message' => 'Esta licencia no está autorizada para este servidor. La firma de hardware no coincide.',
                        'error_code' => 'HARDWARE_MISMATCH',
                        'current_hardware' => [
                            'hostname' => $hardwareInfo['hostname'],
                            'mac' => $hardwareInfo['mac_address'],
                        ],
                    ];
                }
            }

            // Determinar tipo de licencia y fecha de expiración
            $type = self::determineLicenseType($licenseData['valid_until'] ?? null);
            $expiresAt = self::calculateExpirationDate($licenseData['valid_until'] ?? null, $type);

            // Crear hash único del archivo
            $licenseKey = hash('sha256', $licenseContent);

            // Verificar si ya existe una activación de esta licencia en otro hardware
            $existingActivation = LicenseActivation::byLicense($licenseKey)
                ->active()
                ->first();

            if ($existingActivation && $existingActivation->hardware_signature !== $currentHardwareSignature) {
                return [
                    'success' => false,
                    'message' => 'Esta licencia ya está activada en otro servidor.',
                    'error_code' => 'ALREADY_ACTIVATED',
                    'activated_on' => [
                        'hostname' => $existingActivation->hardware_info['hostname'] ?? 'Desconocido',
                        'activated_at' => $existingActivation->activated_at->format('Y-m-d H:i:s'),
                    ],
                ];
            }

            // Extraer información del hospital
            $hospitalInfo = self::extractHospitalInfo($licenseData);

            // Desactivar licencias anteriores
            License::where('is_active', true)->update(['is_active' => false]);
            LicenseActivation::where('is_active', true)->update([
                'is_active' => false,
                'deactivated_at' => Carbon::now(),
            ]);

            // Verificar si ya existe esta licencia
            $existingLicense = License::where('license_key', $licenseKey)->first();
            if ($existingLicense) {
                // Reactivar licencia existente
                $existingLicense->update([
                    'is_active' => true,
                    'activated_at' => Carbon::now(),
                    'last_checked_at' => Carbon::now(),
                    'activated_by' => $userId,
                    'activation_ip' => $ip,
                    'hardware_signature' => $currentHardwareSignature,
                    'hospital_info' => $hospitalInfo,
                    'activation_hardware_info' => $hardwareInfo,
                ]);

                $license = $existingLicense;
            } else {
                // Crear nueva licencia en la BD
                $license = License::create([
                    'institution' => $hospitalInfo['name'] ?? $licenseData['institution'] ?? 'N/A',
                    'license_key' => $licenseKey,
                    'license_data' => encrypt(json_encode($licenseData)),
                    'type' => $type,
                    'activated_at' => Carbon::now(),
                    'expires_at' => $expiresAt,
                    'is_active' => true,
                    'features' => $licenseData['features'] ?? [],
                    'allowed_domain' => $licenseData['allowed_domain'] ?? '*',
                    'signature' => $licenseData['signature'],
                    'activated_by' => $userId,
                    'activation_ip' => $ip,
                    'last_checked_at' => Carbon::now(),
                    'hardware_signature' => $currentHardwareSignature,
                    'hospital_info' => $hospitalInfo,
                    'activation_hardware_info' => $hardwareInfo,
                ]);
            }

            // Registrar activación
            if ($existingActivation && $existingActivation->hardware_signature === $currentHardwareSignature) {
                // Reactivar activación existente
                $existingActivation->reactivate();
            } else {
                // Crear nueva activación
                LicenseActivation::create([
                    'license_key' => $licenseKey,
                    'hardware_signature' => $currentHardwareSignature,
                    'hardware_info' => $hardwareInfo,
                    'activated_at' => Carbon::now(),
                    'is_active' => true,
                    'activation_ip' => $ip,
                    'activated_by' => $userId,
                    'server_info' => [
                        'os' => $hardwareInfo['os'],
                        'hostname' => $hardwareInfo['hostname'],
                    ],
                ]);
            }

            // Limpiar caché
            Cache::forget(self::CACHE_KEY);

            return [
                'success' => true,
                'message' => 'Licencia activada correctamente',
                'license' => [
                    'institution' => $license->institution,
                    'hospital_info' => $hospitalInfo,
                    'type' => $license->type,
                    'expires_at' => $license->expires_at?->format('Y-m-d') ?? 'PERMANENT',
                    'days_remaining' => $license->daysRemaining(),
                    'server_identifier' => HardwareSignatureService::getServerIdentifier(),
                ]
            ];

        } catch (\Exception $e) {
            \Log::error('Error activating license: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar la licencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Desencripta el contenido del archivo de licencia
     */
    private static function decryptLicenseFile(string $encryptedContent): ?array
    {
        try {
            $decryptedData = openssl_decrypt(
                $encryptedContent,
                'AES-256-CBC',
                self::ENCRYPTION_KEY,
                0,
                substr(hash('sha256', self::ENCRYPTION_KEY), 0, 16)
            );

            if ($decryptedData === false) {
                \Log::error('Failed to decrypt license file');
                return null;
            }

            $licenseData = json_decode($decryptedData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('Invalid JSON in license file');
                return null;
            }

            return $licenseData;
        } catch (\Exception $e) {
            \Log::error('Error decrypting license: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Valida la estructura del archivo de licencia
     */
    private static function validateLicenseStructure(array $licenseData): bool
    {
        $requiredFields = ['institution', 'valid_until', 'signature'];
        
        foreach ($requiredFields as $field) {
            if (!isset($licenseData[$field])) {
                \Log::error("Missing required field in license: {$field}");
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica la firma digital de la licencia
     */
    private static function verifySignature(array $licenseData): bool
    {
        if (!isset($licenseData['signature'])) {
            return false;
        }

        $dataToSign = [
            'institution' => $licenseData['institution'] ?? '',
            'valid_until' => $licenseData['valid_until'] ?? '',
            'allowed_domain' => $licenseData['allowed_domain'] ?? '',
            'features' => $licenseData['features'] ?? [],
        ];

        $calculatedSignature = hash_hmac('sha256', json_encode($dataToSign), self::ENCRYPTION_KEY);

        return hash_equals($calculatedSignature, $licenseData['signature']);
    }

    /**
     * Determina el tipo de licencia basado en la fecha de expiración
     */
    private static function determineLicenseType(?string $validUntil): string
    {
        if (!$validUntil || strtoupper($validUntil) === 'PERMANENT') {
            return 'permanent';
        }

        try {
            $expirationDate = Carbon::parse($validUntil);
            $now = Carbon::now();
            $monthsDiff = $now->diffInMonths($expirationDate);

            if ($monthsDiff <= 1) {
                return 'monthly';
            } elseif ($monthsDiff <= 12) {
                return 'annual';
            } else {
                return 'permanent';
            }
        } catch (\Exception $e) {
            return 'permanent';
        }
    }

    /**
     * Calcula la fecha de expiración
     */
    private static function calculateExpirationDate(?string $validUntil, string $type): ?Carbon
    {
        if ($type === 'permanent') {
            return null;
        }

        if (!$validUntil) {
            return null;
        }

        try {
            return Carbon::parse($validUntil);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verifica si una característica está habilitada en la licencia
     */
    public static function hasFeature(string $feature): bool
    {
        try {
            $license = self::getActiveLicense();
            
            if (!$license || !$license->isValid()) {
                return false;
            }

            return $license->hasFeature($feature);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Invalida el caché de licencia
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Extrae información del hospital de los datos de la licencia
     */
    private static function extractHospitalInfo(array $licenseData): array
    {
        return [
            'name' => $licenseData['hospital_name'] ?? $licenseData['institution'] ?? 'N/A',
            'address' => $licenseData['hospital_address'] ?? $licenseData['address'] ?? null,
            'contact_name' => $licenseData['contact_name'] ?? null,
            'contact_email' => $licenseData['contact_email'] ?? null,
            'contact_phone' => $licenseData['contact_phone'] ?? null,
            'city' => $licenseData['city'] ?? null,
            'state' => $licenseData['state'] ?? null,
            'country' => $licenseData['country'] ?? 'México',
        ];
    }

    /**
     * Obtiene información del hardware actual del servidor
     */
    public static function getCurrentHardwareInfo(): array
    {
        return HardwareSignatureService::getHardwareInfo();
    }
}
