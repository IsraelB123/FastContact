<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// 1. Seguridad: Solo proveedores
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

$proveedorId = $_SESSION['user_id'];
$mensaje = "";

// 2. Lógica para ELIMINAR producto
if (isset($_GET['eliminar_id'])) {
    $id_prod = (int)$_GET['eliminar_id'];
    // Validamos que el producto realmente pertenezca a este proveedor antes de borrar
    $stmt = $conn->prepare("DELETE FROM provider_products WHERE id = ? AND proveedor_id = ?");
    $stmt->bind_param("ii", $id_prod, $proveedorId);
    if ($stmt->execute()) {
        $mensaje = "Producto eliminado correctamente.";
    }
}

// 3. Obtener solo los productos de este proveedor
$sql = "SELECT * FROM provider_products WHERE proveedor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proveedorId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Productos – FastContact</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #fff; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: #2a2a2a; padding: 20px; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #ff7f32; border-bottom: 2px solid #444; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #333; font-size: 14px; }
        .btn-add { background: #ff7f32; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; float: right; }
        .btn-del { color: #ff5757; text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="crear_producto.php" class="btn-add">+ Nuevo Producto</a>
        <h1>Gestión de Inventario</h1>
        <p>Administra los productos que ofreces a tus clientes.</p>

        <?php if ($mensaje): ?><p style="color: #8fef88;"><?= $mensaje ?></p><?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>SKU</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['nombre_producto']) ?></strong></td>
                    <td>$<?= number_format($row['precio_unitario'], 2) ?></td>
                    <td>
                        <span style="color: <?= ($row['stock_disponible'] < 10) ? '#ff5757' : '#8fef88' ?>; font-weight: bold;">
                            <?= $row['stock_disponible'] ?> unidades
                        </span>
                    </td>
                    <td><code style="background: #444; padding: 2px 5px; border-radius: 4px;"><?= htmlspecialchars($row['sku_proveedor']) ?></code></td>
                    <td>
                        <a href="?eliminar_id=<?= $row['id'] ?>" class="btn-del" onclick="return confirm('¿Estás seguro de eliminar este producto del catálogo?')">🗑️ Eliminar</a>
                    </td>
                </tr>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <br>
        <a href="panel_proveedor.php" style="color: #888;">← Volver al Panel</a>
    </div>
</body>
</html>