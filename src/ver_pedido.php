<?php
session_save_path('/tmp'); 
session_start();
require_once "config.php";

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

$proveedorId = $_SESSION['user_id'];

// Validar ID de pedido
if (!isset($_GET['id']) && !isset($_POST['order_id'])) {
    die("Pedido no especificado.");
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['order_id'];

$mensajeExito = "";
$mensajeError = "";

// Si el proveedor actualiza el estado del pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $nuevoEstado = $_POST['estado'] ?? 'pendiente';

    // Validar estado permitido
    $estadosPermitidos = ['pendiente', 'confirmado', 'en_proceso', 'completado', 'cancelado'];
    if (!in_array($nuevoEstado, $estadosPermitidos)) {
        $mensajeError = "Estado no válido.";
    } else {
        // Actualizar sólo si el pedido pertenece a este proveedor
        $sqlUpdate = "UPDATE orders SET estado = ? WHERE id = ? AND proveedor_id = ?";
        $stmtUp = $conn->prepare($sqlUpdate);
        $stmtUp->bind_param("sii", $nuevoEstado, $orderId, $proveedorId);
        if ($stmtUp->execute() && $stmtUp->affected_rows > 0) {
            $mensajeExito = "Estado del pedido actualizado correctamente.";
        } else {
            $mensajeError = "No se pudo actualizar el estado o el pedido no pertenece a tu cuenta.";
        }
        $stmtUp->close();
    }
}

// Obtener información del pedido (encabezado)
$sqlPedido = "SELECT 
                o.id,
                o.cliente_id,
                o.proveedor_id,
                o.fecha_creacion,
                o.total,
                o.estado,
                o.notas,
                u.nombre AS nombre_cliente,
                u.email AS email_cliente
              FROM orders o
              INNER JOIN users u ON o.cliente_id = u.id
              WHERE o.id = ? AND o.proveedor_id = ?";

$stmt = $conn->prepare($sqlPedido);
$stmt->bind_param("ii", $orderId, $proveedorId);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    die("Pedido no encontrado o no pertenece a este proveedor.");
}

// Obtener detalle de productos del pedido
$sqlItems = "SELECT 
                oi.product_id,
                oi.cantidad,
                oi.precio_unitario,
                oi.subtotal,
                p.nombre_producto,
                p.sku_proveedor,
                p.unidad_medida
             FROM order_items oi
             INNER JOIN provider_products p ON oi.product_id = p.id
             WHERE oi.order_id = ?";

$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();
$stmtItems->close();

function badgeEstado($estado) {
    if ($estado === 'pendiente') {
        return '<span class="badge badge-pendiente">Pendiente</span>';
    } elseif ($estado === 'confirmado') {
        return '<span class="badge badge-confirmado">Confirmado</span>';
    } elseif ($estado === 'en_proceso') {
        return '<span class="badge badge-proceso">En proceso</span>';
    } elseif ($estado === 'completado') {
        return '<span class="badge badge-completado">Completado</span>';
    } elseif ($estado === 'cancelado') {
        return '<span class="badge badge-cancelado">Cancelado</span>';
    }
    return '<span class="badge">'.htmlspecialchars($estado).'</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del pedido – FastContact</title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px 16px 40px;
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
        .btn-back {
            font-size: 12px;
            border-radius: 999px;
            padding: 6px 12px;
            border: none;
            background: rgba(0,0,0,0.4);
            color: #fff;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-back:hover {
            background: rgba(0,0,0,0.6);
        }
        .card {
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(14px);
            border-radius: 18px;
            padding: 18px 18px 16px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.4);
            margin-bottom: 16px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 6px;
        }
        h2 {
            font-size: 18px;
            margin: 0 0 6px;
        }
        .subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            font-size: 13px;
        }
        .small-label {
            font-size: 11px;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .value {
            font-size: 13px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-pendiente {
            background: rgba(255,196,0,0.1);
            color: #ffd56a;
            border: 1px solid rgba(255,196,0,0.7);
        }
        .badge-confirmado {
            background: rgba(0,190,255,0.1);
            color: #9de6ff;
            border: 1px solid rgba(0,190,255,0.7);
        }
        .badge-proceso {
            background: rgba(181,129,255,0.1);
            color: #ddc1ff;
            border: 1px solid rgba(181,129,255,0.7);
        }
        .badge-completado {
            background: rgba(87,255,135,0.1);
            color: #b4ffcf;
            border: 1px solid rgba(87,255,135,0.7);
        }
        .badge-cancelado {
            background: rgba(255,87,87,0.1);
            color: #ffb3b3;
            border: 1px solid rgba(255,87,87,0.7);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 8px;
        }
        th, td {
            padding: 7px 6px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background: rgba(255,255,255,0.02);
        }
        .text-right { text-align: right; }
        .alert {
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .alert-success {
            background: rgba(54,255,156,0.12);
            border: 1px solid rgba(54,255,156,0.6);
            color: #a7ffd2;
        }
        .alert-error {
            background: rgba(255,87,87,0.12);
            border: 1px solid rgba(255,87,87,0.7);
            color: #ffb3b3;
        }
        select {
            background: rgba(0,0,0,0.4);
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            padding: 5px 10px;
            font-size: 12px;
        }
        .btn-primary {
            padding: 7px 14px;
            border-radius: 999px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: #ff7f32;
            color: #1b1b1b;
            margin-left: 6px;
        }
        .btn-primary:hover {
            background: #ff954f;
        }
        .small {
            font-size: 11px;
            opacity: 0.8;
        }
        textarea {
            width: 100%;
            min-height: 60px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(0,0,0,0.4);
            color: #fff;
            padding: 8px;
            font-size: 13px;
            resize: vertical;
        }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            FastContact
            <span>Detalle del pedido</span>
        </div>
        <a href="panel_proveedor.php" class="btn-back">← Volver a pedidos</a>
    </header>

    <section class="card">
        <h1>Pedido #<?php echo (int)$pedido['id']; ?></h1>
        <p class="subtitle">Revisa la información del pedido y actualiza su estado según el avance.</p>

        <?php if (!empty($mensajeExito)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensajeExito); ?></div>
        <?php endif; ?>
        <?php if (!empty($mensajeError)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($mensajeError); ?></div>
        <?php endif; ?>

        <div class="info-grid">
            <div>
                <div class="small-label">Cliente</div>
                <div class="value">
                    <?php echo htmlspecialchars($pedido['nombre_cliente']); ?><br>
                    <span class="small"><?php echo htmlspecialchars($pedido['email_cliente']); ?></span>
                </div>
            </div>
            <div>
                <div class="small-label">Fecha del pedido</div>
                <div class="value"><?php echo htmlspecialchars($pedido['fecha_creacion']); ?></div>
            </div>
            <div>
                <div class="small-label">Total del pedido</div>
                <div class="value">$ <?php echo number_format($pedido['total'], 2); ?> MXN</div>
            </div>
            <div>
                <div class="small-label">Estado actual</div>
                <div class="value"><?php echo badgeEstado($pedido['estado']); ?></div>
            </div>
        </div>

        <?php if (!empty($pedido['notas'])): ?>
            <div style="margin-top: 12px;">
                <div class="small-label">Notas del cliente</div>
                <div class="value small">
                    <?php echo nl2br(htmlspecialchars($pedido['notas'])); ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Productos del pedido</h2>

        <table>
            <thead>
            <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th>Unidad</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio unitario</th>
                <th class="text-right">Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($itemsResult && $itemsResult->num_rows > 0): ?>
                <?php while ($item = $itemsResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nombre_producto']); ?></td>
                        <td><?php echo htmlspecialchars($item['sku_proveedor']); ?></td>
                        <td><?php echo htmlspecialchars($item['unidad_medida']); ?></td>
                        <td class="text-right"><?php echo (int)$item['cantidad']; ?></td>
                        <td class="text-right">$ <?php echo number_format($item['precio_unitario'], 2); ?></td>
                        <td class="text-right">$ <?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Este pedido no tiene productos registrados.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Actualizar estado del pedido</h2>
        <p class="small">Utiliza este control para marcar el avance real del pedido (confirmado, en proceso, completado o cancelado).</p>

        <form method="post" action="ver_pedido.php">
            <input type="hidden" name="order_id" value="<?php echo (int)$pedido['id']; ?>">
            <label for="estado" class="small-label">Estado</label><br>
            <select name="estado" id="estado">
                <?php
                $estados = [
                    'pendiente'   => 'Pendiente',
                    'confirmado'  => 'Confirmado',
                    'en_proceso'  => 'En proceso',
                    'completado'  => 'Completado',
                    'cancelado'   => 'Cancelado',
                ];
                foreach ($estados as $valor => $texto):
                    $selected = ($pedido['estado'] === $valor) ? 'selected' : '';
                    echo "<option value=\"$valor\" $selected>$texto</option>";
                endforeach;
                ?>
            </select>
            <button type="submit" name="actualizar_estado" class="btn-primary">
                Guardar cambios
            </button>
        </form>
    </section>
</div>
</body>
</html>