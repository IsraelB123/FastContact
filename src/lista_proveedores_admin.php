<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// SEGURIDAD: Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado.");
}

// Lógica para SUSPENDER/ACTIVAR
if (isset($_GET['cambiar_estado'])) {
    $uid = (int)$_GET['uid'];
    $nuevo_estado = ($_GET['estado'] === 'activo') ? 'suspendido' : 'activo';
    
    $stmt = $conn->prepare("UPDATE users SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $uid);
    $stmt->execute();
}

// Consulta para ver proveedores y contar sus productos
$sql = "SELECT u.id as user_id, p.nombre_empresa, u.email, u.estado,
        (SELECT COUNT(*) FROM provider_products WHERE proveedor_id = u.id) as total_prod
        FROM users u
        JOIN provider_profiles p ON u.id = p.user_id
        WHERE u.rol = 'proveedor'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Proveedores – Admin</title>
    <style>
        body { font-family: sans-serif; background: #121212; color: #fff; padding: 30px; }
        .table-container { background: #1e1e1e; padding: 20px; border-radius: 15px; border: 1px solid #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #ff7f32; padding: 12px; border-bottom: 2px solid #333; }
        td { padding: 12px; border-bottom: 1px solid #222; }
        .status-badge { padding: 4px 8px; border-radius: 5px; font-size: 11px; font-weight: bold; }
        .activo { background: rgba(75, 181, 67, 0.2); color: #8fef88; }
        .suspendido { background: rgba(255, 87, 87, 0.2); color: #ffb3b3; }
        .btn-toggle { font-size: 12px; color: #aaa; text-decoration: none; border: 1px solid #444; padding: 5px 10px; border-radius: 5px; }
        .btn-toggle:hover { border-color: #ff7f32; color: #ff7f32; }
    </style>
</head>
<body>
    <div class="table-container">
        <a href="panel_admin.php" style="color: #888; text-decoration: none; font-size: 12px;">← Volver al Panel Maestro</a>
        <h1>Directorio de Proveedores</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Email</th>
                    <th>Productos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['nombre_empresa']) ?></strong></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= $row['total_prod'] ?> items</td>
                    <td>
                        <span class="status-badge <?= $row['estado'] ?>">
                            <?= strtoupper($row['estado']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="?uid=<?= $row['user_id'] ?>&cambiar_estado=1&estado=<?= $row['estado'] ?>" class="btn-toggle">
                            <?= ($row['estado'] === 'activo') ? '🚫 Suspender' : '✅ Activar' ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>