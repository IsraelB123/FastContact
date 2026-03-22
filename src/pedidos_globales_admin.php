<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// SEGURIDAD: Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado.");
}

// Consulta corregida
$sql = "SELECT o.id, 
               u.nombre as cliente, 
               p.nombre_empresa as proveedor, 
               o.total, 
               o.estado, 
               o.fecha_pedido
        FROM orders o
        JOIN users u ON o.cliente_id = u.id
        JOIN provider_profiles p ON o.proveedor_id = p.user_id
        ORDER BY o.fecha_pedido DESC";
$result = $conn->query($sql);

// Calcular ventas totales para el resumen
$totales = $conn->query("SELECT SUM(total) as gran_total FROM orders WHERE estado = 'completado'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Global – FastContact</title>
    <style>
        body { font-family: sans-serif; background: #121212; color: #fff; padding: 30px; }
        .stats-bar { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #1e1e1e; padding: 20px; border-radius: 12px; flex: 1; border-left: 4px solid #ff7f32; }
        .table-container { background: #1e1e1e; padding: 20px; border-radius: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; color: #ff7f32; padding: 12px; border-bottom: 2px solid #333; font-size: 14px; }
        td { padding: 12px; border-bottom: 1px solid #222; font-size: 13px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .pendiente { background: #ffb347; color: #000; }
        .completado { background: #4bb543; color: #fff; }
        .cancelado { background: #ff5757; color: #fff; }
    </style>
</head>
<body>
    <a href="panel_admin.php" style="color: #888; text-decoration: none; font-size: 12px;">← Volver al Panel Maestro</a>
    <h1>Historial de Transacciones Global</h1>

    <div class="stats-bar">
        <div class="stat-card">
            <div style="color: #888; font-size: 12px;">Ventas Finalizadas</div>
            <div style="font-size: 24px; font-weight: bold;">$<?= number_format($totales['gran_total'] ?? 0, 2) ?> MXN</div>
        </div>
        <div class="stat-card" style="border-left-color: #4bb543;">
            <div style="color: #888; font-size: 12px;">Pedidos Totales</div>
            <div style="font-size: 24px; font-weight: bold;"><?= $result->num_rows ?></div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Proveedor</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['fecha_pedido'])) ?></td>
                    <td><?= htmlspecialchars($row['cliente']) ?></td>
                    <td><strong><?= htmlspecialchars($row['proveedor']) ?></strong></td>
                    <td>$<?= number_format($row['total'], 2) ?></td>
                    <td>
                        <span class="badge <?= $row['estado'] ?>">
                            <?= $row['estado'] ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>