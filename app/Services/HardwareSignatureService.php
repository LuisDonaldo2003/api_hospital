<?php

namespace App\Services;

class HardwareSignatureService
{
    /**
     * Genera la firma de hardware única del servidor actual
     * Combina MAC address, UUID del sistema y hostname
     */
    public static function generateSignature(): string
    {
        $components = [
            'mac' => self::getPrimaryMacAddress(),
            'uuid' => self::getSystemUUID(),
            'hostname' => self::getHostname(),
        ];

        // Crear hash único combinando todos los componentes
        $dataToHash = json_encode($components);
        return hash('sha256', $dataToHash);
    }

    /**
     * Obtiene información detallada del hardware para mostrar al usuario
     */
    public static function getHardwareInfo(): array
    {
        return [
            'mac_address' => self::getPrimaryMacAddress(),
            'system_uuid' => self::getSystemUUID(),
            'hostname' => self::getHostname(),
            'os' => self::getOperatingSystem(),
            'signature' => self::generateSignature(),
        ];
    }

    /**
     * Valida que la firma de hardware actual coincida con la esperada
     */
    public static function validateSignature(string $expectedSignature): bool
    {
        $currentSignature = self::generateSignature();
        return hash_equals($expectedSignature, $currentSignature);
    }

    /**
     * Obtiene la dirección MAC de la interfaz de red principal
     */
    private static function getPrimaryMacAddress(): string
    {
        try {
            $os = strtoupper(substr(PHP_OS, 0, 3));

            if ($os === 'WIN') {
                // Windows
                $output = shell_exec('getmac /FO CSV /NH');
                if ($output) {
                    $lines = explode("\n", trim($output));
                    if (!empty($lines[0])) {
                        $parts = str_getcsv($lines[0]);
                        $mac = trim($parts[0]);
                        return strtoupper(str_replace('-', ':', $mac));
                    }
                }
            } else {
                // Linux/Unix
                $output = shell_exec("ip link show | grep -m 1 'link/ether' | awk '{print $2}'");
                if ($output) {
                    return strtoupper(trim($output));
                }

                // Alternativa para sistemas sin 'ip' command
                $output = shell_exec("ifconfig | grep -m 1 'ether' | awk '{print $2}'");
                if ($output) {
                    return strtoupper(trim($output));
                }
            }

            // Fallback: buscar en /sys/class/net (Linux)
            $interfaces = glob('/sys/class/net/*/address');
            if (!empty($interfaces)) {
                foreach ($interfaces as $interface) {
                    if (strpos($interface, 'lo') === false) { // Ignorar loopback
                        $mac = file_get_contents($interface);
                        if ($mac && $mac !== '00:00:00:00:00:00') {
                            return strtoupper(trim($mac));
                        }
                    }
                }
            }

            return 'UNKNOWN_MAC';
        } catch (\Exception $e) {
            \Log::error('Error getting MAC address: ' . $e->getMessage());
            return 'UNKNOWN_MAC';
        }
    }

    /**
     * Obtiene el UUID único del sistema
     */
    private static function getSystemUUID(): string
    {
        try {
            $os = strtoupper(substr(PHP_OS, 0, 3));

            if ($os === 'WIN') {
                // Windows: obtener UUID de WMIC
                $output = shell_exec('wmic csproduct get UUID 2>&1');
                if ($output) {
                    $lines = explode("\n", trim($output));
                    if (count($lines) > 1) {
                        return strtoupper(trim($lines[1]));
                    }
                }
            } else {
                // Linux: intentar leer machine-id
                $machineId = @file_get_contents('/etc/machine-id');
                if ($machineId) {
                    return strtoupper(trim($machineId));
                }

                // Alternativa: /var/lib/dbus/machine-id
                $machineId = @file_get_contents('/var/lib/dbus/machine-id');
                if ($machineId) {
                    return strtoupper(trim($machineId));
                }

                // Alternativa: DMI UUID
                $output = shell_exec('cat /sys/class/dmi/id/product_uuid 2>/dev/null');
                if ($output) {
                    return strtoupper(trim($output));
                }
            }

            // Fallback: generar a partir de información del servidor
            $fallback = gethostname() . php_uname('n') . php_uname('m');
            return strtoupper(hash('md5', $fallback));
        } catch (\Exception $e) {
            \Log::error('Error getting system UUID: ' . $e->getMessage());
            return 'UNKNOWN_UUID';
        }
    }

    /**
     * Obtiene el hostname del servidor
     */
    private static function getHostname(): string
    {
        try {
            $hostname = gethostname();
            if ($hostname === false) {
                $hostname = php_uname('n');
            }
            return $hostname ?: 'UNKNOWN_HOST';
        } catch (\Exception $e) {
            \Log::error('Error getting hostname: ' . $e->getMessage());
            return 'UNKNOWN_HOST';
        }
    }

    /**
     * Obtiene el sistema operativo
     */
    private static function getOperatingSystem(): string
    {
        return php_uname('s') . ' ' . php_uname('r');
    }

    /**
     * Genera un identificador de servidor legible para el usuario
     */
    public static function getServerIdentifier(): string
    {
        $mac = self::getPrimaryMacAddress();
        $hostname = self::getHostname();
        
        return sprintf(
            '%s (%s)',
            $hostname,
            substr($mac, -8) // Últimos 8 caracteres de MAC para identificación
        );
    }
}
