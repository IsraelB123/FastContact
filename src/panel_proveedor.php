<?php
session_save_path('/tmp'); 
session_start();
require_once "config.php";

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

$proveedorUserId   = $_SESSION['user_id'];
$proveedorNombre   = $_SESSION['user_name'] ?? 'Proveedor';

// 1) Obtener el perfil de proveedor ligado al usuario actual
$sqlProveedor = "SELECT id, nombre_empresa FROM provider_profiles WHERE user_id = ?";
$stmtProv = $conn->prepare($sqlProveedor);
$stmtProv->bind_param("i", $proveedorUserId);
$stmtProv->execute();
$resultProv = $stmtProv->get_result();
$proveedorPerfil = $resultProv->fetch_assoc();
$stmtProv->close();

if (!$proveedorPerfil) {
    // Si no hay perfil, no se pueden mostrar pedidos
    $proveedorPerfilId = null;
    $nombreEmpresa = $proveedorNombre;
} else {
    $proveedorPerfilId = $proveedorPerfil['id'];
    $nombreEmpresa = $proveedorPerfil['nombre_empresa'];
}

// 2) Obtener pedidos asociados a este proveedor
$pedidos = [];
if ($proveedorPerfilId !== null) {
    $sqlPedidos = "
        SELECT 
            o.id,
            o.cliente_id,
            o.proveedor_id,
            o.fecha_pedido,
            o.estado,
            o.total,
            u.nombre AS nombre_cliente,
            u.email AS email_cliente
        FROM orders o
        INNER JOIN users u ON o.cliente_id = u.id
        WHERE o.proveedor_id = ?
        ORDER BY o.fecha_pedido DESC
    ";

    $stmt = $conn->prepare($sqlPedidos);
    $stmt->bind_param("i", $proveedorPerfilId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Proveedor – FastContact</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #ffb347 0, #ff7f32 30%, #1b1b1b 100%);
            color: #fff;
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px 16px 30px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .logo {
            font-weight: 700;
            letter-spacing: 0.5px;
            font-size: 20px;
        }
        .logo span {
            font-weight: 400;
            font-size: 12px;
            display: block;
            opacity: 0.8;
        }
        .user-info {
            text-align: right;
            font-size: 13px;
        }
        .user-info strong {
            font-weight: 600;
        }
        .btn-logout {
            margin-top: 6px;
            font-size: 11px;
            border: none;
            background: transparent;
            color: #ffdddd;
            cursor: pointer;
            text-decoration: underline;
        }

        .card {
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(14px);
            border-radius: 18px;
            padding: 18px 18px 16px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.4);
            margin-bottom: 16px;
        }
        h1, h2 {
            margin: 0 0 6px;
        }
        h1 { font-size: 20px; }
        h2 { font-size: 18px; }

        .subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        .tagline {
            font-size: 12px;
            opacity: 0.85;
        }

        .summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .summary-item {
            flex: 1 1 160px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 12px;
        }
        .summary-item span.label {
            display: block;
            opacity: 0.8;
            margin-bottom: 2px;
        }
        .summary-item span.value {
            font-weight: 600;
            font-size: 16px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            padding: 8px 6px;
            text-align: left;
        }
        thead {
            background: rgba(255,255,255,0.06);
        }
        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        tbody tr:nth-child(even) {
            background: rgba(255,255,255,0.02);
        }
        tbody tr:hover {
            background: rgba(0,0,0,0.35);
        }
        .small {
            font-size: 11px;
            opacity: 0.8;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            display: inline-block;
            font-weight: 600;
        }
        .badge-pendiente {
            background: rgba(255,196,87,0.18);
            color: #ffd27f;
        }
        .badge-proceso {
            background: rgba(0,190,255,0.18);
            color: #9de6ff;
        }
        .badge-completado {
            background: rgba(54,255,156,0.15);
            color: #36ff9c;
        }
        .badge-cancelado {
            background: rgba(255,87,87,0.18);
            color: #ff9b9b;
        }

        .btn-action {
            padding: 6px 10px;
            border-radius: 999px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: #ff7f32;
            color: #1b1b1b;
            transition: background 0.2s, transform 0.1s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-action:hover {
            background: #ff954f;
            transform: translateY(-1px);
        }

        .msg-empty {
            text-align: center;
            padding: 18px 10px;
            font-size: 12px;
            opacity: 0.9;
        }

        @media (max-width: 720px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            FastContact
            <span>Panel del proveedor</span>
        </div>
        <div class="user-info">
            <div>
                Sesión iniciada como <strong><?php echo htmlspecialchars($proveedorNombre); ?></strong><br>
                <span class="small"><?php echo htmlspecialchars($nombreEmpresa); ?></span>
            </div>
            <form method="post" action="logout.php">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <section class="card">
        <h1>Bienvenido, <?php echo htmlspecialchars($nombreEmpresa); ?></h1>
        <div class="acciones-rapidas" style="margin-bottom: 25px; display: flex; gap: 15px;">
    
            <a href="crear_producto.php" class="btn-accion" style="
                background: #ff7f32; 
                color: #1a1a1a; 
                padding: 12px 20px; 
                border-radius: 10px; 
                text-decoration: none; 
                font-weight: bold; 
                display: flex; 
                align-items: center; 
                gap: 8px;
                box-shadow: 0 4px 15px rgba(255, 127, 50, 0.3);">
                <span>+</span> Añadir Nuevo Producto
            </a>

            <a href="gestionar_productos.php" class="btn-accion" style="
                background: rgba(255, 255, 255, 0.1); 
                color: #fff; 
                padding: 12px 20px; 
                border-radius: 10px; 
                text-decoration: none; 
                font-weight: 500; 
                border: 1px solid rgba(255, 255, 255, 0.2);">
                📦 Gestionar Mi Inventario
            </a>
    
        </div>
        <p class="subtitle">
            Desde este panel podrás ver los pedidos que los clientes han realizado a tu empresa.
        </p>
        <p class="tagline">
            Revisa los pedidos recientes, valida la información y continúa tu proceso interno de surtido y entrega.
        </p>

        <?php
        $totalPedidos = count($pedidos);
        $pendientes = 0;
        $completados = 0;
        $cancelados  = 0;
        foreach ($pedidos as $p) {
            if ($p['estado'] === 'pendiente') $pendientes++;
            if ($p['estado'] === 'completado') $completados++;
            if ($p['estado'] === 'cancelado') $cancelados++;
        }
        ?>
        <div class="summary-row">
            <div class="summary-item">
                <span class="label">Total de pedidos</span>
                <span class="value"><?php echo $totalPedidos; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Pendientes</span>
                <span class="value"><?php echo $pendientes; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Completados</span>
                <span class="value"><?php echo $completados; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Cancelados</span>
                <span class="value"><?php echo $cancelados; ?></span>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Pedidos recibidos</h2>
        <p class="subtitle">
            Aquí se listan los pedidos generados por tus clientes desde la plataforma FastContact.
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total (MXN)</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($pedidos)): ?>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['email_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></td>
                            <td>
                                <?php
                                $estado = $pedido['estado'];
                                if ($estado === 'pendiente') {
                                    echo '<span class="badge badge-pendiente">Pendiente</span>';
                                } elseif ($estado === 'en_proceso') {
                                    echo '<span class="badge badge-proceso">En proceso</span>';
                                } elseif ($estado === 'completado') {
                                    echo '<span class="badge badge-completado">Completado</span>';
                                } elseif ($estado === 'cancelado') {
                                    echo '<span class="badge badge-cancelado">Cancelado</span>';
                                } else {
                                    echo '<span class="badge">'.htmlspecialchars($estado).'</span>';
                                }
                                ?>
                            </td>
                            <td>$<?php echo number_format((float)$pedido['total'], 2); ?></td>
                            <td>
                                <!-- Aquí luego podemos enlazar a un detalle del pedido -->
                                <a href="detalle_pedido.php?id=<?php echo (int)$pedido['id']; ?>" class="btn-action">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="msg-empty">
                            Aún no se han recibido pedidos. Cuando un cliente realice un pedido,
                            aparecerá listado en este apartado.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>
