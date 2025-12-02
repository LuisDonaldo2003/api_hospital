<?php
/**
 * Generador de Licencias para Sistema Hospital
 * 
 * Este script genera archivos de licencia encriptados para el sistema.
 * SOLO DEBE SER EJECUTADO POR EL DESARROLLADOR/PROVEEDOR.
 * 
 * Uso:
 * php generate-license.php
 */

// Configuración (debe coincidir con LicenseValidator)
const ENCRYPTION_KEY = 'hospital-license-key-2025'; // Cambiar por una clave más segura en producción
const OUTPUT_DIR = 'licenses/';

/**
 * Genera una licencia encriptada
 */
function generateLicense(array $data): string
{
    // Generar firma digital
    $dataToSign = [
        'institution' => $data['institution'],
        'valid_until' => $data['valid_until'],
        'allowed_domain' => $data['allowed_domain'],
        'features' => $data['features'],
    ];

    $signature = hash_hmac('sha256', json_encode($dataToSign), ENCRYPTION_KEY);

    // Agregar firma a los datos
    $data['signature'] = $signature;
    $data['generated_at'] = date('Y-m-d H:i:s');
    $data['generated_by'] = 'License Generator v1.0';

    // Convertir a JSON
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);

    // Encriptar datos
    $encryptedData = openssl_encrypt(
        $jsonData,
        'AES-256-CBC',
        ENCRYPTION_KEY,
        0,
        substr(hash('sha256', ENCRYPTION_KEY), 0, 16)
    );

    return $encryptedData;
}

/**
 * Solicita datos al usuario de forma interactiva
 */
function promptLicenseData(): array
{
    echo "\n";
    echo "==============================================\n";
    echo "   GENERADOR DE LICENCIAS - SISTEMA HOSPITAL\n";
    echo "==============================================\n\n";

    // Nombre de la institución
    echo "Nombre de la Institución: ";
    $institution = trim(fgets(STDIN));

    // Fecha de expiración
    echo "Fecha de expiración (YYYY-MM-DD) [ejemplo: 2026-12-31]: ";
    $validUntil = trim(fgets(STDIN));

    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil)) {
        die("Error: Formato de fecha inválido. Use YYYY-MM-DD\n");
    }

    // Dominio permitido
    echo "Dominio permitido (deje vacío para cualquier dominio) [ejemplo: hospital.gob.mx]: ";
    $allowedDomain = trim(fgets(STDIN));
    if (empty($allowedDomain)) {
        $allowedDomain = '*';
    }

    // Características/Módulos
    echo "\nMódulos disponibles:\n";
    echo "1. modulo_archivo\n";
    echo "2. modulo_personal\n";
    echo "3. modulo_ensenanza\n";
    echo "4. modulo_reportes\n";
    echo "5. modulo_estadisticas\n";
    echo "6. modulo_completo (todos los módulos)\n";
    echo "\nIngrese los números de módulos separados por comas (ejemplo: 1,2,3): ";
    $modulesInput = trim(fgets(STDIN));

    $availableModules = [
        1 => 'modulo_archivo',
        2 => 'modulo_personal',
        3 => 'modulo_ensenanza',
        4 => 'modulo_reportes',
        5 => 'modulo_estadisticas',
        6 => 'modulo_completo',
    ];

    $features = [];
    $selectedModules = array_map('trim', explode(',', $modulesInput));

    foreach ($selectedModules as $module) {
        if (isset($availableModules[$module])) {
            if ($availableModules[$module] === 'modulo_completo') {
                $features = array_values($availableModules);
                break;
            }
            $features[] = $availableModules[$module];
        }
    }

    if (empty($features)) {
        $features = ['modulo_completo']; // Por defecto todos los módulos
    }

    return [
        'institution' => $institution,
        'valid_until' => $validUntil,
        'allowed_domain' => $allowedDomain,
        'features' => $features,
    ];
}

/**
 * Guarda la licencia en un archivo
 */
function saveLicense(string $encryptedData, string $institution): string
{
    // Crear directorio si no existe
    if (!is_dir(OUTPUT_DIR)) {
        mkdir(OUTPUT_DIR, 0755, true);
    }

    // Generar nombre de archivo seguro
    $filename = preg_replace('/[^a-z0-9]+/i', '_', strtolower($institution));
    $filename = trim($filename, '_');
    $filename = date('Y-m-d') . '_' . $filename . '.license';
    $filepath = OUTPUT_DIR . $filename;

    // Guardar archivo
    file_put_contents($filepath, $encryptedData);

    return $filepath;
}

/**
 * Muestra resumen de la licencia generada
 */
function displaySummary(array $data, string $filepath): void
{
    echo "\n";
    echo "==============================================\n";
    echo "   LICENCIA GENERADA EXITOSAMENTE\n";
    echo "==============================================\n\n";
    echo "Institución: " . $data['institution'] . "\n";
    echo "Válida hasta: " . $data['valid_until'] . "\n";
    echo "Dominio permitido: " . $data['allowed_domain'] . "\n";
    echo "Módulos habilitados:\n";
    foreach ($data['features'] as $feature) {
        echo "  - " . $feature . "\n";
    }
    echo "\nArchivo generado: " . $filepath . "\n\n";
    echo "INSTRUCCIONES DE INSTALACIÓN:\n";
    echo "------------------------------\n";
    echo "1. Envíe el archivo '$filepath' al cliente\n";
    echo "2. El cliente debe copiarlo en: storage/app/license.key\n";
    echo "3. Reiniciar el servidor Laravel (si está en caché)\n";
    echo "4. La licencia se validará automáticamente\n\n";
}

// Ejecutar generador
try {
    $licenseData = promptLicenseData();
    $encryptedLicense = generateLicense($licenseData);
    $filepath = saveLicense($encryptedLicense, $licenseData['institution']);
    displaySummary($licenseData, $filepath);
    
    echo "✓ Proceso completado exitosamente\n\n";
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
