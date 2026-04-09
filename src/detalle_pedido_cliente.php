<?php
session_save_path('/tmp'); 
session_start();
require_once "config.php";

// Validar sesión de cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

$clienteId = (int)$_SESSION['user_id'];
$pedidoId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pedidoId <= 0) {
    die("Pedido no válido.");
}

/**
 * 1) Obtener datos generales del pedido, asegurando que pertenezca a este cliente
 */
$sqlPedido = "
    SELECT 
        o.id,
        o.fecha_pedido,
        o.estado,
        o.total,
        p.nombre_empresa AS proveedor
    FROM orders o
    INNER JOIN provider_profiles p ON o.proveedor_id = p.id
    WHERE o.id = ? AND o.cliente_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sqlPedido);
if (!$stmt) {
    die("Error preparando consulta de pedido: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("ii", $pedidoId, $clienteId);
$stmt->execute();
$resultPedido = $stmt->get_result();
$pedido = $resultPedido->fetch_assoc();
$stmt->close();

if (!$pedido) {
    die("No se encontró el pedido o no tienes permiso para verlo.");
}

/**
 * 2) Obtener los productos del pedido
 * Intentamos con ambas variantes de nombre de columna: product_id y producto_id
 */
$sqlItemsVariants = [
    // variante A (inglés): product_id
    "
    SELECT 
        oi.cantidad,
        oi.precio_unitario,
        pr.nombre_producto
    FROM order_items oi
    INNER JOIN provider_products pr ON oi.product_id = pr.id
    WHERE oi.order_id = ?
    ",
    // variante B (español): producto_id
    "
    SELECT 
        oi.cantidad,
        oi.precio_unitario,
        pr.nombre_producto
    FROM order_items oi
    INNER JOIN provider_products pr ON oi.producto_id = pr.id
    WHERE oi.order_id = ?
    "
];

$resultItems = false;
$stmtItems = false;
$items = [];

foreach ($sqlItemsVariants as $variantSql) {
    $stmtItems = $conn->prepare($variantSql);
    if ($stmtItems) {
        // si prepare funciona, lo ejecutamos y rompemos el ciclo
        $stmtItems->bind_param("i", $pedidoId);
        if (!$stmtItems->execute()) {
            // si execute falla, liberar y continuar (poco probable)
            $stmtItems->close();
            $stmtItems = false;
            continue;
        }
        $resultItems = $stmtItems->get_result();
        break;
    }
    // si prepare falló, intentamos la siguiente variante
}

// Si ninguna variante funcionó, mostramos el error y sugerencia
if (!$stmtItems || !$resultItems) {
    // Mostrar el último error de MySQL para debugging
    die("Error obteniendo items del pedido. Detalle MySQL: " . htmlspecialchars($conn->error));
}

// Cargar resultados
while ($row = $resultItems->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();
$conn->close();

// Función para badge de estado adaptado al Deep Tech Blue
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
    <title>Detalle del pedido #<?php echo (int)$pedido['id']; ?> – FastContact</title>
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
        .page { max-width: 950px; margin: 0 auto; padding: 25px 20px; }
        
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

        .info-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; margin-top: 20px; 
        }
        .info-item {
            background: rgba(255, 255, 255, 0.02);
            padding: 20px;
            border-radius: 16px;
            border-top: 3px solid #38bdf8;
        }
        .info-item label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .info-item span { font-size: 15px; font-weight: 600; color: #f8fafc; display: block; }
        .info-item .total-val { font-size: 20px; color: #38bdf8; }

        .badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: bold; display: inline-block; }
        .badge-estado-pendiente { background: rgba(56, 189, 248, 0.15); color: #38bdf8; } /* Cyan */
        .badge-estado-enproceso { background: rgba(139, 92, 246, 0.15); color: #c4b5fd; } /* Morado */
        .badge-estado-completado { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; } /* Verde */
        .badge-estado-cancelado { background: rgba(248, 113, 113, 0.15); color: #fca5a5; } /* Rojo */

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
            <span>DETALLE DEL PEDIDO</span>
        </div>
        <div class="header-actions">
            <form method="get" action="panel_cliente.php">
                <button type="submit" class="btn-back">← Volver al panel</button>
            </form>
            <form method="post" action="logout.php">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <section class="card">
        <h1>Pedido #<?php echo (int)$pedido['id']; ?></h1>
        <p class="subtitle">Revisa el estado actual y el desglose de los productos incluidos en tu orden.</p>

        <div class="info-grid">
            <div class="info-item">
                <label>Proveedor</label>
                <span><?php echo htmlspecialchars($pedido['proveedor']); ?></span>
            </div>
            <div class="info-item">
                <label>Fecha de emisión</label>
                <span style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></span>
            </div>
            <div class="info-item" style="border-top-color: #6ee7b7;">
                <label>Estado Actual</label>
                <span><?php echo badgeEstado($pedido['estado']); ?></span>
            </div>
            <div class="info-item" style="border-top-color: #38bdf8;">
                <label>Total Estimado</label>
                <span class="total-val">$<?php echo number_format((float)$pedido['total'], 2); ?> MXN</span>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Desglose de Productos</h2>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario (MXN)</th>
                    <th>Subtotal (MXN)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $suma = 0;
            if (!empty($items)):
                foreach ($items as $item):
                    $cantidad = (int)($item['cantidad'] ?? 0);
                    $precio = (float)($item['precio_unitario'] ?? 0);
                    $subtotal = $item['subtotal'] ?? ($cantidad * $precio);
                    $suma += (float)$subtotal;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['nombre_producto'] ?? '—'); ?></strong></td>
                        <td style="color: #cbd5e1;"><?php echo $cantidad; ?> unds.</td>
                        <td>$<?php echo number_format($precio, 2); ?></td>
                        <td style="font-weight: 600;">$<?php echo number_format((float)$subtotal, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right" class="calc-label">Total calculado:</td>
                    <td style="color: #38bdf8;">$<?php echo number_format($suma, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center; padding: 30px; color: #64748b;">No se encontraron productos registrados para este pedido.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>