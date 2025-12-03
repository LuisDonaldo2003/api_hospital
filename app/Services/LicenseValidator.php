<?php

namespace App\Services;

use Carbon\Carbon;

class LicenseValidator
{
    private const LICENSE_PATH = 'app/license.key';
    private const ENCRYPTION_KEY = 'hospital-license-key-2025'; // Cambiar por una clave más segura en producción

    /**
     * Verifica si existe una licencia válida
     */
    public static function isValid(): bool
    {
        try {
            $license = self::readLicense();
            
            if (!$license) {
                return false;
            }

            // Verificar fecha de expiración
            if (!self::checkExpiration($license['valid_until'])) {
                return false;
            }

            // Verificar dominio/servidor
            if (!self::checkDomain($license['allowed_domain'])) {
                return false;
            }

            // Verificar firma
            if (!self::verifySignature($license)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error validating license: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene información de la licencia
     */
    public static function getLicenseInfo(): ?array
    {
        try {
            $license = self::readLicense();
            
            if (!$license) {
                return null;
            }

            return [
                'institution' => $license['institution'] ?? 'N/A',
                'valid_until' => $license['valid_until'] ?? 'N/A',
                'allowed_domain' => $license['allowed_domain'] ?? 'N/A',
                'features' => $license['features'] ?? [],
                'is_valid' => self::isValid(),
                'days_remaining' => self::getDaysRemaining($license['valid_until'] ?? null),
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting license info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lee y desencripta el archivo de licencia
     */
    private static function readLicense(): ?array
    {
        $licensePath = storage_path(self::LICENSE_PATH);

        if (!file_exists($licensePath)) {
            \Log::warning('License file not found at: ' . $licensePath);
            return null;
        }

        try {
            $encryptedData = file_get_contents($licensePath);
            $decryptedData = openssl_decrypt(
                $encryptedData,
                'AES-256-CBC',
                self::ENCRYPTION_KEY,
                0,
                substr(hash('sha256', self::ENCRYPTION_KEY), 0, 16)
            );

            if ($decryptedData === false) {
                \Log::error('Failed to decrypt license file');
                return null;
            }

            $license = json_decode($decryptedData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('Invalid JSON in license file');
                return null;
            }

            return $license;
        } catch (\Exception $e) {
            \Log::error('Error reading license: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si la licencia no ha expirado
     */
    private static function checkExpiration(?string $validUntil): bool
    {
        if (!$validUntil) {
            return false;
        }

        // Licencia permanente
        if (strtoupper($validUntil) === 'PERMANENT') {
            return true;
        }

        try {
            $expirationDate = Carbon::parse($validUntil);
            return Carbon::now()->lte($expirationDate);
        } catch (\Exception $e) {
            \Log::error('Error parsing expiration date: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica que el dominio/servidor coincida
     */
    private static function checkDomain(?string $allowedDomain): bool
    {
        if (!$allowedDomain || $allowedDomain === '*') {
            return true; // Licencia sin restricción de dominio
        }

        $currentDomain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? gethostname();
        
        // Normalizar dominios: remover www. para comparación flexible
        $normalizedCurrent = preg_replace('/^www\./i', '', strtolower($currentDomain));
        $normalizedAllowed = preg_replace('/^www\./i', '', strtolower($allowedDomain));
        
        // Comparar tanto exacto como sin www
        return strtolower($currentDomain) === strtolower($allowedDomain) ||
               $normalizedCurrent === $normalizedAllowed;
    }

    /**
     * Verifica la firma digital de la licencia
     */
    private static function verifySignature(array $license): bool
    {
        if (!isset($license['signature'])) {
            return false;
        }

        $dataToSign = [
            'institution' => $license['institution'] ?? '',
            'valid_until' => $license['valid_until'] ?? '',
            'allowed_domain' => $license['allowed_domain'] ?? '',
            'features' => $license['features'] ?? [],
        ];

        $calculatedSignature = hash_hmac('sha256', json_encode($dataToSign), self::ENCRYPTION_KEY);

        return hash_equals($calculatedSignature, $license['signature']);
    }

    /**
     * Calcula los días restantes de la licencia
     */
    private static function getDaysRemaining(?string $validUntil): ?int
    {
        if (!$validUntil) {
            return null;
        }

        // Licencia permanente
        if (strtoupper($validUntil) === 'PERMANENT') {
            return null; // null indica que es permanente
        }

        try {
            $expirationDate = Carbon::parse($validUntil)->startOfDay();
            $now = Carbon::now()->startOfDay();
            
            if ($now->gt($expirationDate)) {
                return 0;
            }

            // Calcular días incluyendo el día de hoy hasta el día de vencimiento
            return (int) $now->diffInDays($expirationDate, false);
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
            $license = self::readLicense();
            
            if (!$license || !self::isValid()) {
                return false;
            }

            $features = $license['features'] ?? [];
            
            return in_array($feature, $features);
        } catch (\Exception $e) {
            return false;
        }
    }
}
