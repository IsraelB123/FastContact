<?php
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

// Cerrar conexión (se puede dejar hasta después de renderizar si prefieres)
$conn->close();

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
    <title>Detalle del pedido #<?php echo (int)$pedido['id']; ?> – FastContact</title>
    <style>
        /* (estilos como antes) */
        * { box-sizing: border-box; }
        body { margin:0; font-family:system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; min-height:100vh; background: radial-gradient(circle at top left, #ffb347 0, #ff7f32 30%, #1f1f1f 100%); color:#fff; }
        .page { max-width:900px; margin:0 auto; padding:20px 16px 30px; }
        header { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
        .logo { font-weight:700; letter-spacing:.5px; font-size:20px; }
        .logo span { display:block; font-size:12px; opacity:.8; }
        .btn-back, .btn-logout { background:transparent; border:none; color:#ffb347; text-decoration:underline; cursor:pointer; font-size:13px; }
        .card { background: rgba(0,0,0,0.45); backdrop-filter: blur(14px); border-radius: 18px; padding: 18px; margin-bottom:16px; box-shadow:0 12px 30px rgba(0,0,0,0.35); }
        .info-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-top:10px; }
        .info-item label { display:block; font-size:11px; opacity:.8; text-transform:uppercase; }
        table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13px; }
        th, td { padding:8px 6px; text-align:left; }
        thead { background: rgba(255,255,255,0.06); }
        .badge { padding:3px 8px; border-radius:999px; font-size:11px; display:inline-block; }
        .badge-estado-pendiente { background: rgba(255,196,0,0.15); color:#ffd56a; }
        .badge-estado-enproceso { background: rgba(0,190,255,0.15); color:#9de6ff; }
        .badge-estado-completado { background: rgba(87,255,135,0.15); color:#b4ffcf; }
        .badge-estado-cancelado { background: rgba(255,87,87,0.15); color:#ffb0b0; }
        .total-row td { border-top:1px solid rgba(255,255,255,0.3); font-weight:600; }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">FastContact <span>Detalle del pedido</span></div>
        <div>
            <form method="get" action="panel_cliente.php" style="display:inline;"><button type="submit" class="btn-back">← Volver al panel</button></form>
            <form method="post" action="logout.php" style="display:inline;"><button type="submit" class="btn-logout">Cerrar sesión</button></form>
        </div>
    </header>

    <section class="card">
        <h1>Pedido #<?php echo (int)$pedido['id']; ?></h1>
        <p class="subtitle">Revisa el detalle de los productos incluidos en tu pedido y el estado actual del mismo.</p>

        <div class="info-grid">
            <div class="info-item"><label>Proveedor</label><span><?php echo htmlspecialchars($pedido['proveedor']); ?></span></div>
            <div class="info-item"><label>Fecha del pedido</label><span><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></span></div>
            <div class="info-item"><label>Estado</label><span><?php echo badgeEstado($pedido['estado']); ?></span></div>
            <div class="info-item"><label>Total estimado</label><span>$<?php echo number_format((float)$pedido['total'], 2); ?> MXN</span></div>
        </div>
    </section>

    <section class="card">
        <h2>Productos del pedido</h2>
        <table>
            <thead>
                <tr><th>Producto</th><th>Cantidad</th><th>Precio unitario (MXN)</th><th>Subtotal (MXN)</th></tr>
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
                        <td><?php echo htmlspecialchars($item['nombre_producto'] ?? '—'); ?></td>
                        <td><?php echo $cantidad; ?></td>
                        <td>$<?php echo number_format($precio, 2); ?></td>
                        <td>$<?php echo number_format((float)$subtotal, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row"><td colspan="3" style="text-align:right">Total calculado:</td><td>$<?php echo number_format($suma, 2); ?></td></tr>
            <?php else: ?>
                <tr><td colspan="4">No hay productos registrados para este pedido.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
