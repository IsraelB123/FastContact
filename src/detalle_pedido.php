<?php
session_save_path('/tmp'); 
session_start();
require_once "config.php";

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

// Validar parámetro id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Pedido no válido.");
}

$orderId = (int)$_GET['id'];
$proveedorUserId = (int)$_SESSION['user_id'];

// 1) Obtener el perfil del proveedor ligado al usuario
$sqlProveedor = "SELECT id, nombre_empresa FROM provider_profiles WHERE user_id = ? LIMIT 1";
$stmtProv = $conn->prepare($sqlProveedor);
if (!$stmtProv) {
    die("Error preparando consulta de perfil: " . htmlspecialchars($conn->error));
}
$stmtProv->bind_param("i", $proveedorUserId);
$stmtProv->execute();
$resultProv = $stmtProv->get_result();
$proveedorPerfil = $resultProv->fetch_assoc();
$stmtProv->close();

if (!$proveedorPerfil) {
    die("No se encontró el perfil de proveedor para el usuario en sesión.");
}

$proveedorPerfilId = (int)$proveedorPerfil['id'];
$nombreEmpresa = $proveedorPerfil['nombre_empresa'] ?? 'Proveedor';

// 2) Obtener datos generales del pedido (validando que sea de este proveedor)
$sqlPedido = "
    SELECT 
        o.id,
        o.cliente_id,
        o.proveedor_id,
        o.fecha_pedido,
        o.estado,
        o.total,
        o.notas,
        u.nombre AS nombre_cliente,
        u.email AS email_cliente
    FROM orders o
    INNER JOIN users u ON o.cliente_id = u.id
    WHERE o.id = ? AND o.proveedor_id = ?
    LIMIT 1
";

$stmtPed = $conn->prepare($sqlPedido);
if (!$stmtPed) {
    die("Error preparando consulta de pedido: " . htmlspecialchars($conn->error));
}
$stmtPed->bind_param("ii", $orderId, $proveedorPerfilId);
$stmtPed->execute();
$resultPed = $stmtPed->get_result();
$pedido = $resultPed->fetch_assoc();
$stmtPed->close();

if (!$pedido) {
    die("No se encontró el pedido o no pertenece a este proveedor.");
}

// 3) Obtener los productos del pedido
$sqlItems = "
    SELECT 
        oi.id,
        oi.product_id,
        oi.cantidad,
        oi.precio_unitario,
        oi.subtotal,
        pp.nombre_producto
    FROM order_items oi
    INNER JOIN provider_products pp ON oi.product_id = pp.id
    WHERE oi.order_id = ?
";

$stmtItems = $conn->prepare($sqlItems);
if (!$stmtItems) {
    die("Error preparando consulta de items: " . htmlspecialchars($conn->error));
}
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();

$items = [];
while ($row = $resultItems->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();

// 4) Estados permitidos (para el select)
$estadosPermitidos = ['pendiente', 'en_proceso', 'completado', 'cancelado'];

// Función para badge de estado
function badgeEstado($estado) {
    $estado = strtolower($estado);
    if ($estado === 'pendiente') {
        return '<span class="badge badge-estado-pendiente">Pendiente</span>';
    } elseif ($estado === 'en_proceso') {
        return '<span class="badge badge-estado-enproceso">En proceso</span>';
    } elseif ($estado === 'completado') {
        return '<span class="badge badge-estado-completado">Completado</span>';
    } elseif ($estado === 'cancelado') {
        return '<span class="badge badge-estado-cancelado">Cancelado</span>';
    }
    return '<span class="badge">'.htmlspecialchars($estado).'</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión del pedido #<?php echo (int)$pedido['id']; ?> – FastContact</title>
    <style>
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body { 
            font-family: 'Inter', system-ui, sans-serif; 
            margin: 0; 
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            color: #f8fafc; 
            min-height: 100vh;
        }
        .page { max-width: 1000px; margin: 0 auto; padding: 25px 20px; }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .logo h1 { margin: 0; font-size: 24px; letter-spacing: 0.5px; color: #f8fafc; }
        .logo span { font-size: 12px; color: #38bdf8; font-weight: bold; letter-spacing: 1px; display: block; margin-top: 4px; }
        
        .header-actions form { display: inline-block; margin-left: 10px; }
        
        .btn-back {
            color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
            border: 1px solid rgba(56, 189, 248, 0.3); padding: 8px 16px; border-radius: 12px;
            background: transparent; cursor: pointer;
        }
        .btn-back:hover { background: rgba(56, 189, 248, 0.1); border-color: #38bdf8; }

        .btn-logout {
            color: #fca5a5; font-size: 13px; font-weight: 600; border: none; background: transparent; 
            text-decoration: underline; cursor: pointer; padding: 8px 16px;
        }
        .btn-logout:hover { color: #f87171; }

        .card { 
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            margin-bottom: 25px;
            overflow-x: auto;
        }

        h1 { margin: 0 0 5px; font-size: 22px; color: #f8fafc; }
        h2 { margin: 0 0 15px; font-size: 18px; color: #f8fafc; }
        .subtitle { color: #94a3b8; font-size: 13px; margin-bottom: 20px; }

        .grid-two { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; margin-bottom: 25px; 
        }
        .info-box {
            background: rgba(255, 255, 255, 0.02);
            padding: 25px;
            border-radius: 16px;
            border-left: 3px solid #38bdf8;
        }
        .info-box.status-box { border-left-color: #6ee7b7; }
        
        .section-title { font-size: 14px; font-weight: 700; color: #f8fafc; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;}
        .label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; margin-top: 12px; }
        .value { font-size: 14px; font-weight: 600; color: #f8fafc; display: block; }
        
        .notas-box { background: rgba(251, 191, 36, 0.05); border: 1px solid rgba(251, 191, 36, 0.2); padding: 15px; border-radius: 12px; margin-top: 15px; }
        .notas-box .label { color: #fcd34d; margin-top: 0; }
        .notas-box .value { color: #fde68a; font-style: italic; font-size: 13px; }

        .badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: bold; display: inline-block; }
        .badge-estado-pendiente { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        .badge-estado-enproceso { background: rgba(139, 92, 246, 0.15); color: #c4b5fd; }
        .badge-estado-completado { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; }
        .badge-estado-cancelado { background: rgba(248, 113, 113, 0.15); color: #fca5a5; }

        .estado-form {
            display: flex; align-items: center; gap: 12px;
            background: rgba(0,0,0,0.3); padding: 15px 20px; border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.05); margin-top: 20px;
        }
        .estado-form span { font-size: 13px; color: #cbd5e1; font-weight: 600; }
        .estado-form select { 
            border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); padding: 8px 12px; 
            background: #1e293b; color: #fff; font-size: 13px; cursor: pointer; outline: none;
        }
        .estado-form select:focus { border-color: #38bdf8; box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2); }
        .estado-form button { 
            padding: 9px 18px; border-radius: 10px; border: none; font-size: 13px; font-weight: 700; 
            cursor: pointer; background: #6ee7b7; color: #022c22; box-shadow: 0 4px 15px rgba(110, 231, 183, 0.2);
        }
        .estado-form button:hover { background: #34d399; transform: translateY(-1px); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; color: #38bdf8; padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .total-row td { border-top: 2px solid rgba(56, 189, 248, 0.3); font-weight: 700; color: #f8fafc; font-size: 15px; }
        .total-row .calc-label { color: #94a3b8; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            <h1>FastContact</h1>
            <span>CONTROL DE PEDIDO</span>
        </div>
        <div class="header-actions">
            <form method="get" action="panel_proveedor.php">
                <button type="submit" class="btn-back">← Volver al panel</button>
            </form>
            <form method="post" action="logout.php">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <section class="card">
        <h1>Orden #<?php echo (int)$pedido['id']; ?></h1>
        <p class="subtitle">Revisa los datos del cliente y actualiza el estado de preparación o entrega.</p>

        <div class="grid-two">
            <div class="info-box">
                <div class="section-title">Datos del Cliente</div>
                <span class="label">Empresa / Nombre</span>
                <span class="value"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></span>
                
                <span class="label">Correo Electrónico</span>
                <span class="value" style="color: #cbd5e1;"><?php echo htmlspecialchars($pedido['email_cliente']); ?></span>
            </div>

            <div class="info-box status-box">
                <div class="section-title">Detalles de la Orden</div>
                <span class="label">Fecha de Solicitud</span>
                <span class="value" style="font-family: monospace;"><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></span>

                <span class="label">Estado Actual</span>
                <span class="value" style="margin-top: 4px;"><?php echo badgeEstado($pedido['estado']); ?></span>

                <span class="label">Total del Pedido</span>
                <span class="value" style="color: #38bdf8; font-size: 18px;">$<?php echo number_format((float)$pedido['total'], 2); ?> MXN</span>
            </div>
        </div>

        <?php if (!empty($pedido['notas'])): ?>
        <div class="notas-box">
            <div class="label">⚠️ NOTAS DEL CLIENTE:</div>
            <div class="value">"<?php echo nl2br(htmlspecialchars($pedido['notas'])); ?>"</div>
        </div>
        <?php endif; ?>

        <form class="estado-form" method="post" action="actualizar_estado_pedido.php">
            <input type="hidden" name="order_id" value="<?php echo (int)$pedido['id']; ?>">
            <span>Actualizar estado operativo: </span>
            <select name="nuevo_estado" required>
                <?php foreach ($estadosPermitidos as $est): ?>
                    <option value="<?php echo $est; ?>" <?php echo ($est === $pedido['estado']) ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $est)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Guardar Cambios</button>
        </form>
    </section>

    <section class="card">
        <h2>Productos Solicitados</h2>
        <p class="subtitle">Desglose del pedido (Cantidades y Subtotales).</p>

        <table>
            <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad Requerida</th>
                <th>Precio unitario (MXN)</th>
                <th>Subtotal (MXN)</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['nombre_producto'] ?? '—'); ?></strong></td>
                        <td style="color: #cbd5e1;"><?php echo (int)$item['cantidad']; ?> unds.</td>
                        <td>$<?php echo number_format((float)$item['precio_unitario'], 2); ?></td>
                        <td style="font-weight: 600;">$<?php echo number_format((float)$item['subtotal'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;" class="calc-label">Total:</td>
                    <td style="color: #38bdf8;">$<?php echo number_format((float)$pedido['total'], 2); ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No se encontraron productos asociados a este pedido.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>