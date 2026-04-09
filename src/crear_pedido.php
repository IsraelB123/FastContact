<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// 2. Verificamos la sesión
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
                 WHERE proveedor_id = ? AND activo = 1
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
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body { 
            font-family: 'Inter', system-ui, sans-serif; 
            margin: 0; 
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            color: #f8fafc; 
            min-height: 100vh;
        }
        .page { max-width: 1100px; margin: 0 auto; padding: 25px 20px; }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .logo h1 { margin: 0; font-size: 24px; letter-spacing: 0.5px; color: #f8fafc; }
        .logo span { font-size: 12px; color: #38bdf8; font-weight: bold; letter-spacing: 1px; display: block; margin-top: 4px; }
        
        .btn-back {
            color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
            border: 1px solid rgba(56, 189, 248, 0.3); padding: 8px 16px; border-radius: 12px;
        }
        .btn-back:hover { background: rgba(56, 189, 248, 0.1); border-color: #38bdf8; }

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

        h2 { margin: 0 0 5px; font-size: 20px; color: #f8fafc; }
        .subtitle { color: #94a3b8; font-size: 13px; margin-bottom: 15px; }

        .provider-info { display: flex; justify-content: space-between; align-items: flex-start; background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; border-left: 3px solid #38bdf8; }
        .provider-info strong { font-size: 18px; display: block; margin-bottom: 5px; color: #f8fafc; }
        .small { font-size: 12px; color: #94a3b8; display: block; line-height: 1.5; }
        
        .badge { padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }
        .badge-disponible { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; }
        .badge-ocupado { background: rgba(251, 191, 36, 0.15); color: #fcd34d; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; color: #38bdf8; padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .input-cantidad {
            width: 80px; padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); 
            background: rgba(0,0,0,0.3); color: #fff; font-size: 14px;
        }
        .input-cantidad:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2); }
        
        textarea {
            width: 100%; min-height: 80px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.3); color: #fff; padding: 12px 14px; font-size: 14px; resize: vertical; font-family: inherit;
        }
        textarea:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2); }

        .btn-primary { 
            background: #38bdf8; color: #0f172a; padding: 14px 24px; border: none; border-radius: 12px; 
            font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 20px; box-shadow: 0 8px 20px rgba(56, 189, 248, 0.2);
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary:hover { background: #7dd3fc; transform: translateY(-2px); box-shadow: 0 12px 25px rgba(56, 189, 248, 0.3); }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 500; }
        .alert-success { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.3); color: #6ee7b7; }
        .alert-error { background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.3); color: #fca5a5; }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            <h1>FastContact</h1>
            <span>NUEVO PEDIDO B2B</span>
        </div>
        <a href="panel_cliente.php" class="btn-back">← Volver al panel</a>
    </header>

    <section class="card">
        <h2>Detalles del Proveedor</h2>
        <p class="subtitle">Estás levantando un pedido con el siguiente aliado comercial:</p>

        <div class="provider-info">
            <div>
                <strong><?php echo htmlspecialchars($proveedor['nombre_empresa']); ?></strong>
                <span class="small">
                    <?php echo ucfirst(htmlspecialchars($proveedor['tipo_proveedor'])); ?>
                    <?php if (!empty($proveedor['nombre_categoria'])): ?>
                        · <?php echo htmlspecialchars($proveedor['nombre_categoria']); ?>
                    <?php endif; ?>
                </span>
                <span class="small" style="margin-top: 5px;">
                    Contacto: <?php echo htmlspecialchars($proveedor['nombre_contacto'] ?? 'No especificado'); ?>
                    <?php if (!empty($proveedor['telefono_contacto'])): ?>
                        · Tel: <?php echo htmlspecialchars($proveedor['telefono_contacto']); ?>
                    <?php endif; ?>
                </span>
            </div>
            <div>
                <?php if ($proveedor['disponibilidad'] === 'disponible'): ?>
                    <span class="badge badge-disponible">Proveedor Disponible</span>
                <?php elseif ($proveedor['disponibilidad'] === 'ocupado'): ?>
                    <span class="badge badge-ocupado">Con Alta Demanda</span>
                <?php else: ?>
                    <span class="badge"><?php echo htmlspecialchars($proveedor['disponibilidad']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Catálogo de productos</h2>
        <p class="subtitle">Indica la cantidad de cada producto que deseas pedir. Solo se añadirán los productos con cantidad mayor a cero.</p>

        <?php if (!empty($mensajeExito)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensajeExito); ?></div>
        <?php endif; ?>

        <?php if (!empty($mensajeError)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($mensajeError); ?></div>
        <?php endif; ?>

        <form method="post" action="crear_pedido.php">
            <input type="hidden" name="proveedor_id" value="<?php echo (int)$proveedorId; ?>">

            <table>
                <thead>
                <tr>
                    <th>Producto</th>
                    <th>Unidad</th>
                    <th>Precio (MXN)</th>
                    <th>Stock Disponible</th>
                    <th>Cantidad a Pedir</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($productosResult && $productosResult->num_rows > 0): ?>
                    <?php while ($prod = $productosResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($prod['nombre_producto']); ?></strong><br>
                                <span class="small" style="margin-top: 3px; font-family: monospace;"><?php echo htmlspecialchars($prod['sku_proveedor']); ?></span>
                            </td>
                            <td style="color: #cbd5e1;"><?php echo htmlspecialchars($prod['unidad_medida']); ?></td>
                            <td style="font-weight: 600;">$ <?php echo number_format($prod['precio_unitario'], 2); ?></td>
                            <td>
                                <?php if ($prod['stock_disponible'] <= 0): ?>
                                    <span style="color: #fca5a5; font-weight: bold; font-size: 12px;">Agotado</span>
                                <?php else: ?>
                                    <span style="color: #6ee7b7; font-size: 13px;"><?php echo (int)$prod['stock_disponible']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="hidden" name="producto_id[]" value="<?php echo (int)$prod['id']; ?>">
                                <input type="hidden" name="precio_unitario[]" value="<?php echo (float)$prod['precio_unitario']; ?>">
                                <input type="number" name="cantidad[]" min="0" max="<?php echo (int)$prod['stock_disponible']; ?>" class="input-cantidad" value="0" <?php echo ($prod['stock_disponible'] <= 0) ? 'disabled' : ''; ?>>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">Este proveedor aún no tiene productos registrados en su catálogo activo.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 25px;">
                <label for="notas" style="color: #38bdf8; font-size: 13px;">Notas adicionales para el proveedor (Opcional):</label>
                <textarea id="notas" name="notas" placeholder="Ejemplo: Favor de entregar por la mañana, acceso por la rampa de descarga trasera..."></textarea>
            </div>

            <button type="submit" name="crear_pedido" class="btn-primary">
                <span>✓</span> Confirmar y Enviar Pedido
            </button>
            <p class="small" style="margin-top: 15px;">
                Al confirmar, tu pedido quedará registrado como <strong>Pendiente</strong> y el proveedor será notificado para dar seguimiento.
            </p>
        </form>
    </section>
</div>
</body>
</html>