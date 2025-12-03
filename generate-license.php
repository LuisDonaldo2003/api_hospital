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

    // Tipo de licencia
    echo "\nTipo de licencia:\n";
    echo "1. Mensual (31 días)\n";
    echo "2. Anual (365 días)\n";
    echo "3. Permanente (sin vencimiento)\n";
    echo "4. Fecha personalizada\n";
    echo "Seleccione opción [1-4]: ";
    $licenseType = trim(fgets(STDIN));

    $validUntil = '';
    switch ($licenseType) {
        case '1':
            // Mensual: 31 días de duración (hasta el mismo día del mes siguiente)
            $validUntil = date('Y-m-d', strtotime('+31 days'));
            echo "→ Expira: $validUntil (31 días de duración)\n";
            break;
        case '2':
            $validUntil = date('Y-m-d', strtotime('+365 days'));
            echo "→ Expira: $validUntil (365 días de duración)\n";
            break;
        case '3':
            $validUntil = 'PERMANENT';
            echo "→ Licencia PERMANENTE (sin vencimiento)\n";
            break;
        case '4':
            echo "Fecha de expiración (YYYY-MM-DD) [ejemplo: 2026-12-31]: ";
            $validUntil = trim(fgets(STDIN));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil)) {
                die("Error: Formato de fecha inválido. Use YYYY-MM-DD\n");
            }
            break;
        default:
            die("Error: Opción inválida\n");
    }

    // Dominio permitido
    echo "\nDominio permitido:\n";
    echo "  Presione ENTER para cualquier dominio (*)\n";
    echo "  O ingrese dominio específico (ejemplo: api_imss.gob.mx)\n";
    echo "Dominio: ";
    $allowedDomain = trim(fgets(STDIN));
    if (empty($allowedDomain)) {
        $allowedDomain = '*';
        echo "→ Cualquier dominio permitido\n";
    } else {
        // Limpiar www. automáticamente si existe
        $allowedDomain = preg_replace('/^www\./i', '', $allowedDomain);
        echo "→ Dominio: $allowedDomain\n";
    }

    // Características/Módulos - Siempre todos los módulos
    echo "\n→ Módulos: TODOS (Acceso completo al sistema)\n";
    
    $features = [
        'modulo_archivo',
        'modulo_personal',
        'modulo_ensenanza',
        'modulo_reportes',
        'modulo_estadisticas',
        'modulo_completo'
    ];

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
    echo "1. Envíe el archivo al cliente\n";
    echo "   Archivo: $filepath\n\n";
    echo "2. El cliente debe seguir estos pasos:\n";
    echo "   a) Acceder al panel web del sistema\n";
    echo "   b) Ir a la sección 'Subir Licencia'\n";
    echo "   c) Cargar el archivo .license\n";
    echo "   d) La licencia se activará automáticamente\n\n";
    echo "   NOTA: La licencia cuenta desde el día de activación\n";
    echo "         (ejemplo: activada el 2 dic, mensual vence el 2 ene)\n\n";
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
