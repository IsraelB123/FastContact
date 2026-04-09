<?php
echo "========================================\n";
echo " INICIANDO PRUEBAS AUTOMÁTICAS (CI/CD) \n";
echo "========================================\n\n";

$errores = 0;

// Prueba 1: Verificar que la carpeta principal existe
if (is_dir(__DIR__ . '/src')) {
    echo "[OK] Directorio 'src/' encontrado.\n";
} else {
    echo "[ERROR] No se encontró el directorio 'src/'.\n";
    $errores++;
}

// Prueba 2: Verificar que el archivo de configuración vital existe
$configFile = __DIR__ . '/src/config.php';
if (file_exists($configFile)) {
    echo "[OK] Archivo 'config.php' encontrado.\n";
} else {
    echo "[ERROR] El archivo 'config.php' no existe.\n";
    $errores++;
}

// Prueba 3: Sanity Check básico de PHP
$a = 5;
$b = 5;
if (($a + $b) === 10) {
    echo "[OK] Motor de PHP funcionando correctamente.\n";
} else {
    echo "[ERROR] Fallo lógico en PHP.\n";
    $errores++;
}

echo "\n----------------------------------------\n";
if ($errores > 0) {
    echo "❌ RESULTADO: Las pruebas fallaron ($errores errores).\n";
    exit(1); // El código 1 le dice a Jenkins que aborte el proceso
} else {
    echo "✅ RESULTADO: Todas las pruebas pasaron con éxito.\n";
    exit(0); // El código 0 le dice a Jenkins que todo está perfecto y puede continuar
}