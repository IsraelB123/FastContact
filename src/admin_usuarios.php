<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado.");
}

if (isset($_GET['activar_id'])) {
    $id_activar = (int)$_GET['activar_id'];
    $stmt = $conn->prepare("UPDATE users SET estado = 'activo' WHERE id = ?");
    $stmt->bind_param("i", $id_activar);
    $stmt->execute();
    $mensaje = "¡Usuario activado exitosamente!";
}

// CONSULTA SIMPLIFICADA (Para evitar el error de num_rows)
// Consulta mejorada con LEFT JOIN para ver usuarios aunque el perfil falle
$sql = "SELECT u.id, u.nombre, u.email, 
               IFNULL(p.nombre_empresa, 'PERFIL NO CREADO') as nombre_empresa
        FROM users u 
        LEFT JOIN provider_profiles p ON u.id = p.user_id 
        WHERE u.estado = 'pendiente' AND u.rol = 'proveedor'";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin – FastContact</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #fff; padding: 40px; }
        .admin-card { background: #2a2a2a; border-radius: 15px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; border-bottom: 2px solid #ff7f32; padding: 10px; color: #ff7f32; }
        td { padding: 10px; border-bottom: 1px solid #444; }
        .btn-activar { background: #4bb543; color: white; padding: 5px 15px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold; }
        .btn-activar:hover { background: #3a9634; }
        .msg { background: rgba(75, 181, 67, 0.2); color: #8fef88; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .no-data { text-align: center; padding: 40px; color: #888; }
    </style>
</head>
<body>
    <div class="admin-card">
        <h1>Gestión de Solicitudes B2B</h1>
        <p>Aquí puedes aprobar a los proveedores que se postularon para vender en la plataforma.</p>

        <?php if (isset($mensaje)): ?>
            <div class="msg"><?= $mensaje ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nombre_empresa']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $row['fecha_registro'] ?></td>
                            <td>
                                <a href="?activar_id=<?= $row['id'] ?>" class="btn-activar" onclick="return confirm('¿Confirmar activación de esta empresa?')">APROBAR</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">No hay solicitudes pendientes en este momento.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <a href="panel_cliente.php" style="color: #aaa; font-size: 12px;">← Volver al panel principal</a>
    </div>
</body>
</html>