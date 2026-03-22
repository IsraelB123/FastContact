<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// 1. Seguridad: Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado.");
}

$mensaje = "";

// 2. LÓGICA DE APROBACIÓN (La transferencia)
if (isset($_GET['aprobar_id'])) {
    $solicitud_id = (int)$_GET['aprobar_id'];

    // Buscamos los datos en la tabla de tránsito
    $stmt = $conn->prepare("SELECT * FROM solicitudes_proveedores WHERE id = ?");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();

    if ($solicitud) {
        $conn->begin_transaction();
        try {
            // A. Insertar en tabla oficial USERS
            $sqlU = "INSERT INTO users (nombre, email, password_hash, rol, estado) VALUES (?, ?, ?, 'proveedor', 'activo')";
            $stmtU = $conn->prepare($sqlU);
            $stmtU->bind_param("sss", $solicitud['nombre_contacto'], $solicitud['email'], $solicitud['password_sugerida']);
            $stmtU->execute();
            $newUserId = $conn->insert_id;

            // B. Insertar en tabla oficial PROVIDER_PROFILES
            $sqlP = "INSERT INTO provider_profiles (user_id, nombre_empresa, nombre_contacto, disponibilidad) VALUES (?, ?, ?, 'disponible')";
            $stmtP = $conn->prepare($sqlP);
            $stmtP->bind_param("iss", $newUserId, $solicitud['nombre_empresa'], $solicitud['nombre_contacto']);
            $stmtP->execute();

            // C. Borrar de la tabla de tránsito
            $conn->query("DELETE FROM solicitudes_proveedores WHERE id = $solicitud_id");

            $conn->commit();
            $mensaje = "¡Proveedor aprobado y transferido con éxito!";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al procesar: " . $e->getMessage();
        }
    }
}

// 3. Obtener solicitudes pendientes actuales
$result = $conn->query("SELECT * FROM solicitudes_proveedores ORDER BY fecha_solicitud DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin B2B – FastContact</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #fff; padding: 30px; }
        .card { background: #2a2a2a; padding: 20px; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #ff7f32; border-bottom: 2px solid #444; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #333; }
        .btn { background: #4bb543; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; }
        .msg { background: rgba(75, 181, 67, 0.2); color: #8fef88; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Solicitudes de Proveedores Pendientes</h1>
        <?php if ($mensaje): ?><div class="msg"><?= $mensaje ?></div><?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Fecha Solicitud</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nombre_empresa']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nombre_contacto']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $row['fecha_solicitud'] ?></td>
                            <td><a href="?aprobar_id=<?= $row['id'] ?>" class="btn">APROBAR</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px;">No hay solicitudes pendientes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <a href="panel_cliente.php" style="color:#888;">← Regresar al Panel</a>
    </div>
</body>
</html>