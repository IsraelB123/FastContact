<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// 2. Verificamos la sesión (esto ahora sí debería funcionar)
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

$clienteId = $_SESSION['user_id'];

// Verificar proveedor_id
if (!isset($_GET['proveedor_id']) && !isset($_POST['proveedor_id'])) {
    die("Proveedor no especificado.");
}

// Soportar proveedor_id vía GET (al entrar) y vía POST (al enviar formulario)
$proveedorId = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : (int)$_POST['proveedor_id'];

$mensajeExito = "";
$mensajeError = "";

// Si se envió el formulario para crear el pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {

    if (!isset($_POST['producto_id']) || !is_array($_POST['producto_id'])) {
        $mensajeError = "No se recibió la información de productos.";
    } else {

        $productos = $_POST['producto_id'];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio_unitario'] ?? [];

        $itemsSeleccionados = [];
        $total = 0;

        // Construir lista de productos seleccionados (cantidad > 0)
        foreach ($productos as $index => $productId) {
            $productId = (int)$productId;
            $cantidad = isset($cantidades[$index]) ? (int)$cantidades[$index] : 0;
            $precioUnitario = isset($precios[$index]) ? (float)$precios[$index] : 0;

            if ($cantidad > 0) {
                $subtotal = $cantidad * $precioUnitario;
                $total += $subtotal;

                $itemsSeleccionados[] = [
                    'product_id' => $productId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal
                ];
            }
        }

        if (empty($itemsSeleccionados)) {
            $mensajeError = "Debes seleccionar al menos un producto con cantidad mayor a cero.";
        } else {
            // Insertar en orders y order_items
            $conn->begin_transaction();

            try {
                $notas = isset($_POST['notas']) ? trim($_POST['notas']) : null;

                // Insertar encabezado del pedido
                $sqlOrder = "INSERT INTO orders (cliente_id, proveedor_id, total, notas, estado)
                             VALUES (?, ?, ?, ?, 'pendiente')";
                $stmtOrder = $conn->prepare($sqlOrder);
                $stmtOrder->bind_param("iids", $clienteId, $proveedorId, $total, $notas);
                $stmtOrder->execute();
                $orderId = $stmtOrder->insert_id;
                $stmtOrder->close();

                // Insertar detalle
                $sqlItem = "INSERT INTO order_items (order_id, product_id, cantidad, precio_unitario, subtotal)
                            VALUES (?, ?, ?, ?, ?)";
                $stmtItem = $conn->prepare($sqlItem);

                foreach ($itemsSeleccionados as $item) {
                    $stmtItem->bind_param(
                        "iiidd",
                        $orderId,
                        $item['product_id'],
                        $item['cantidad'],
                        $item['precio_unitario'],
                        $item['subtotal']
                    );
                    $stmtItem->execute();
                }

                $stmtItem->close();
                $conn->commit();

                $mensajeExito = "Tu pedido se ha registrado correctamente. Total: $ " . number_format($total, 2) . " MXN";
            } catch (Exception $e) {
                $conn->rollback();
                $mensajeError = "Ocurrió un error al registrar tu pedido. Intenta nuevamente.";
            }
        }
    }
}

// Obtener información del proveedor
$sqlProveedor = "SELECT 
                    p.id,
                    p.nombre_empresa,
                    p.nombre_contacto,
                    p.telefono_contacto,
                    p.tipo_proveedor,
                    p.disponibilidad,
                    c.nombre_categoria
                 FROM provider_profiles p
                 LEFT JOIN provider_categories c ON p.categoria_id = c.id
                 WHERE p.id = ?";
$stmtProv = $conn->prepare($sqlProveedor);
$stmtProv->bind_param("i", $proveedorId);
$stmtProv->execute();
$proveedor = $stmtProv->get_result()->fetch_assoc();
$stmtProv->close();

if (!$proveedor) {
    die("Proveedor no encontrado.");
}

// Obtener productos del proveedor
$sqlProductos = "SELECT 
                    id,
                    nombre_producto,
                    descripcion,
                    categoria_producto,
                    unidad_medida,
                    precio_unitario,
                    sku_proveedor,
                    stock_disponible
                 FROM provider_products
                 WHERE proveedor_id = ?
                 ORDER BY nombre_producto ASC";

$stmtProd = $conn->prepare($sqlProductos);
$stmtProd->bind_param("i", $proveedorId);
$stmtProd->execute();
$productosResult = $stmtProd->get_result();
$stmtProd->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear pedido – FastContact</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #ffb347 0, #ff7f32 30%, #1f1f1f 100%);
            color: #fff;
        }
        .page {
            max-width: 1100px;
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
            background: rgba(0,0,0,0.45);
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
        .tagline {
            font-size: 12px;
            opacity: 0.85;
        }
        .provider-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 13px;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            display: inline-block;
        }
        .badge-disponible {
            background: rgba(54,255,156,0.15);
            color: #36ff9c;
        }
        .badge-ocupado {
            background: rgba(255,196,87,0.18);
            color: #ffd27f;
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
        }
        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        tr:nth-child(even) {
            background: rgba(255,255,255,0.02);
        }
        tr:hover {
            background: rgba(0,0,0,0.35);
        }
        .input-cantidad {
            width: 70px;
            padding: 4px 6px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(0,0,0,0.4);
            color: #fff;
            font-size: 12px;
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
        .btn-primary {
            padding: 8px 16px;
            border-radius: 999px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: #ff7f32;
            color: #1b1b1b;
            transition: background 0.2s, transform 0.1s;
            margin-top: 10px;
        }
        .btn-primary:hover {
            background: #ff954f;
            transform: translateY(-1px);
        }
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
        .small {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            FastContact
            <span>Nuevo pedido</span>
        </div>
        <a href="panel_cliente.php" class="btn-back">← Volver al panel</a>
    </header>

    <section class="card">
        <h1>Crear pedido</h1>
        <p class="subtitle">
            Estás levantando un pedido con el proveedor:
        </p>

        <div class="provider-info">
            <div>
                <strong><?php echo htmlspecialchars($proveedor['nombre_empresa']); ?></strong><br>
                <span class="small">
                    <?php echo htmlspecialchars($proveedor['tipo_proveedor']); ?>
                    <?php if (!empty($proveedor['nombre_categoria'])): ?>
                        · <?php echo htmlspecialchars($proveedor['nombre_categoria']); ?>
                    <?php endif; ?>
                </span><br>
                <span class="small">
                    Contacto: <?php echo htmlspecialchars($proveedor['nombre_contacto'] ?? 'No especificado'); ?>
                    <?php if (!empty($proveedor['telefono_contacto'])): ?>
                        · Tel: <?php echo htmlspecialchars($proveedor['telefono_contacto']); ?>
                    <?php endif; ?>
                </span>
            </div>
            <div>
                <?php if ($proveedor['disponibilidad'] === 'disponible'): ?>
                    <span class="badge badge-disponible">Proveedor disponible</span>
                <?php elseif ($proveedor['disponibilidad'] === 'ocupado'): ?>
                    <span class="badge badge-ocupado">Con alta demanda</span>
                <?php else: ?>
                    <span class="badge"><?php echo htmlspecialchars($proveedor['disponibilidad']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Catálogo de productos</h2>
        <p class="tagline">Indica la cantidad de cada producto que deseas pedir.</p>

        <?php if (!empty($mensajeExito)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensajeExito); ?></div>
        <?php endif; ?>

        <?php if (!empty($mensajeError)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($mensajeError); ?></div>
        <?php endif; ?>

        <form method="post" action="crear_pedido.php">
            <input type="hidden" name="proveedor_id" value="<?php echo (int)$proveedorId; ?>">

            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Descripción</th>
                        <th>Unidad</th>
                        <th>Precio unitario</th>
                        <th>Stock</th>
                        <th>Cantidad</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($productosResult && $productosResult->num_rows > 0): ?>
                        <?php while ($prod = $productosResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($prod['nombre_producto']); ?><br>
                                    <span class="small"><?php echo htmlspecialchars($prod['sku_proveedor']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($prod['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($prod['unidad_medida']); ?></td>
                                <td>$ <?php echo number_format($prod['precio_unitario'], 2); ?></td>
                                <td><?php echo (int)$prod['stock_disponible']; ?></td>
                                <td>
                                    <input type="hidden" name="producto_id[]" value="<?php echo (int)$prod['id']; ?>">
                                    <input type="hidden" name="precio_unitario[]" value="<?php echo (float)$prod['precio_unitario']; ?>">
                                    <input type="number" name="cantidad[]" min="0" max="<?php echo (int)$prod['stock_disponible']; ?>" class="input-cantidad" value="0">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Este proveedor aún no tiene productos registrados.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 10px;">
                <label for="notas" class="small">Notas adicionales para el proveedor (opcional):</label>
                <textarea id="notas" name="notas" placeholder="Ejemplo: Favor de entregar por la mañana, entrada por bodega..."></textarea>
            </div>

            <button type="submit" name="crear_pedido" class="btn-primary">
                Confirmar pedido
            </button>
            <p class="small">
                Al confirmar, tu pedido quedará registrado como <strong>pendiente</strong> y el proveedor podrá verlo en su panel para dar seguimiento.
            </p>
        </form>
    </section>
</div>
</body>
</html>
<?php
$conn->close();
?>
