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
    <title>Auditoría Global – FastContact</title>
    <style>
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body { 
            font-family: 'Inter', system-ui, sans-serif; 
            margin: 0; 
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            color: #f8fafc; 
            padding: 30px 20px;
            min-height: 100vh;
        }
        .container { max-width: 1100px; margin: 0 auto; }
        .back-link { color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 25px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(15px);
            padding: 25px; 
            border-radius: 20px; 
            border: 1px solid rgba(255,255,255,0.05);
            border-left: 4px solid #38bdf8;
        }
        .stat-label { color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-size: 28px; font-weight: 800; margin-top: 8px; color: #f8fafc; }

        .table-card { 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(20px);
            padding: 30px; 
            border-radius: 24px; 
            border: 1px solid rgba(255,255,255,0.05);
            overflow-x: auto;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #38bdf8; padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 11px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .completado { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; }
        .pendiente { background: rgba(251, 191, 36, 0.15); color: #fcd34d; }
        .cancelado { background: rgba(248, 113, 113, 0.15); color: #fca5a5; }
    </style>
</head>
<body>
    <div class="container">
        <a href="panel_admin.php" class="back-link">← Volver al Centro de Mando</a>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Ventas Finalizadas</div>
                <div class="stat-value">$<?= number_format($totales['gran_total'] ?? 0, 2) ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #6ee7b7;">
                <div class="stat-label">Pedidos en Red</div>
                <div class="stat-value"><?= $result->num_rows ?></div>
            </div>
        </div>

        <div class="table-card">
            <h2 style="margin: 0 0 20px; font-size: 20px;">Historial Global de Transacciones</h2>
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
                        <td style="color: #38bdf8; font-weight: bold;">#<?= $row['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['fecha_pedido'])) ?></td>
                        <td><?= htmlspecialchars($row['cliente']) ?></td>
                        <td><strong><?= htmlspecialchars($row['proveedor']) ?></strong></td>
                        <td style="font-weight: 600;">$<?= number_format($row['total'], 2) ?></td>
                        <td><span class="badge <?= $row['estado'] ?>"><?= $row['estado'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>