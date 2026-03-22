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
// <- IMPORTANTE: usar 'product_id' según tu esquema (no 'producto_id')
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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del pedido #<?php echo (int)$pedido['id']; ?> – FastContact</title>
    <style>
        /* (estilos idénticos a los que ya usabas) */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; min-height: 100vh;
            background: radial-gradient(circle at top left, #ffb347 0, #ff7f32 30%, #1b1b1b 100%); color: #fff; }
        .page { max-width: 1000px; margin: 0 auto; padding: 20px 16px 30px; }
        header { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
        .logo { font-weight:700; letter-spacing:.5px; font-size:20px; }
        .logo span { font-weight:400; font-size:12px; display:block; opacity:.8; }
        .user-info { text-align:right; font-size:13px; }
        .card { background: rgba(0,0,0,0.45); backdrop-filter: blur(14px); border-radius: 18px; padding: 18px; box-shadow:0 16px 40px rgba(0,0,0,0.4); margin-bottom:16px; }
        .grid-two { display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:10px; }
        .info-box { background: rgba(255,255,255,0.03); border-radius:12px; padding:10px 12px; font-size:13px; }
        .label { opacity:.8; font-size:12px; } .value { font-weight:600; }
        table { width:100%; border-collapse:collapse; font-size:13px; margin-top:8px; }
        th,td { padding:8px 6px; text-align:left; }
        thead { background: rgba(255,255,255,0.06); }
        .btn-back { padding:7px 14px; border-radius:999px; border:none; font-size:12px; font-weight:600; cursor:pointer; background:#ff7f32; color:#1b1b1b; text-decoration:none; display:inline-block; margin-bottom:6px; }
        .estado-form select { border-radius:999px; border:1px solid rgba(255,255,255,0.2); padding:4px 10px; background:rgba(0,0,0,0.5); color:#fff; font-size:12px; }
        .estado-form button { padding:6px 12px; border-radius:999px; border:none; font-size:12px; font-weight:600; cursor:pointer; background:#36ff9c; color:#003321; margin-left:6px; }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            FastContact
            <span>Detalle del pedido</span>
        </div>
        <div class="user-info">
            <div><strong><?php echo htmlspecialchars($nombreEmpresa); ?></strong><br><span class="small">Proveedor</span></div>
            <form method="post" action="logout.php"><button type="submit" class="btn-logout" style="background:transparent;border:none;color:#ffdddd;text-decoration:underline;margin-top:6px;">Cerrar sesión</button></form>
        </div>
    </header>

    <a href="panel_proveedor.php" class="btn-back">← Volver al panel</a>

    <section class="card">
        <h1>Pedido #<?php echo (int)$pedido['id']; ?></h1>
        <p class="subtitle">Revisa el detalle del pedido realizado por el cliente.</p>

        <div class="grid-two">
            <div class="info-box">
                <div class="section-title">Información del cliente</div>
                <div class="label">Nombre</div>
                <div class="value"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></div>
                <div class="label" style="margin-top:6px;">Correo</div>
                <div class="value"><?php echo htmlspecialchars($pedido['email_cliente']); ?></div>
            </div>

            <div class="info-box">
                <div class="section-title">Información del pedido</div>
                <div class="label">Fecha de pedido</div>
                <div class="value"><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></div>

                <div class="label" style="margin-top:6px;">Estado actual</div>
                <div class="value">
                    <?php
                    $estado = $pedido['estado'];
                    if ($estado === 'pendiente') echo '<span class="badge badge-pendiente">Pendiente</span>';
                    elseif ($estado === 'en_proceso') echo '<span class="badge badge-proceso">En proceso</span>';
                    elseif ($estado === 'completado') echo '<span class="badge badge-completado">Completado</span>';
                    elseif ($estado === 'cancelado') echo '<span class="badge badge-cancelado">Cancelado</span>';
                    else echo '<span class="badge">'.htmlspecialchars($estado).'</span>';
                    ?>
                </div>

                <div class="label" style="margin-top:6px;">Total del pedido</div>
                <div class="value">$<?php echo number_format((float)$pedido['total'], 2); ?> MXN</div>
            </div>
        </div>

        <form class="estado-form" method="post" action="actualizar_estado_pedido.php">
            <input type="hidden" name="order_id" value="<?php echo (int)$pedido['id']; ?>">
            <span>Cambiar estado: </span>
            <select name="nuevo_estado" required>
                <?php foreach ($estadosPermitidos as $est): ?>
                    <option value="<?php echo $est; ?>" <?php echo ($est === $pedido['estado']) ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $est)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Actualizar</button>
        </form>
    </section>

    <section class="card">
        <h2>Productos del pedido</h2>
        <p class="tagline">Detalle de los productos solicitados, incluyendo cantidades y subtotales.</p>

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
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nombre_producto'] ?? '—'); ?></td>
                        <td><?php echo (int)$item['cantidad']; ?></td>
                        <td>$<?php echo number_format((float)$item['precio_unitario'], 2); ?></td>
                        <td>$<?php echo number_format((float)$item['subtotal'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;">Total:</td>
                    <td>$<?php echo number_format((float)$pedido['total'], 2); ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="4">No se encontraron productos asociados a este pedido.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
