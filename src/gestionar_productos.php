<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// 1. Seguridad: Solo proveedores
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// --- NUEVO: Buscar el ID del Perfil real ---
$sqlPerfil = "SELECT id FROM provider_profiles WHERE user_id = ?";
$stmtP = $conn->prepare($sqlPerfil);
$stmtP->bind_param("i", $userId);
$stmtP->execute();
$resP = $stmtP->get_result();
$perfil = $resP->fetch_assoc();

if (!$perfil) {
    die("Error: No tienes un perfil de proveedor activo.");
}

$proveedorId = $perfil['id']; // Ahora esto valdrá 22 para Sabritas
// ------------------------------------------

$mensaje = "";

// 2. Lógica para ELIMINAR producto (Ya usa el $proveedorId correcto)
if (isset($_GET['eliminar_id'])) {
    $id_prod = (int)$_GET['eliminar_id'];
    $stmt = $conn->prepare("DELETE FROM provider_products WHERE id = ? AND proveedor_id = ?");
    $stmt->bind_param("ii", $id_prod, $proveedorId);
    if ($stmt->execute()) {
        $mensaje = "✅ El producto ha sido eliminado permanentemente.";
        $tipo_msj = "success";
    } else {
        $mensaje = "❌ Error al intentar eliminar el producto.";
        $tipo_msj = "error";
    }
}

// 3. Obtener los productos usando el ID de perfil
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
                    <td><strong>$<?= number_format($row['precio_unitario'], 2) ?></strong></td>
                    <td>
                        <?php if ($row['stock_disponible'] <= 0): ?>
                            <span style="color: #ff5757; font-weight: bold;">🚫 Agotado</span>
                        <?php elseif ($row['stock_disponible'] < 10): ?>
                            <span style="color: #ffb347; font-weight: bold;">⚠️ <?= $row['stock_disponible'] ?> unidades</span>
                        <?php else: ?>
                            <span style="color: #8fef88;"><?= $row['stock_disponible'] ?> unidades</span>
                        <?php endif; ?>
                    </td>
                    <td><code style="background: #444; padding: 2px 5px; border-radius: 4px;"><?= htmlspecialchars($row['sku_proveedor']) ?></code></td>
                    <td>
                        <a href="?eliminar_id=<?= $row['id'] ?>" class="btn-del" onclick="return confirm('¿Estás seguro de eliminar este producto del catálogo?')">🗑️ Eliminar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <br>
        <a href="panel_proveedor.php" style="color: #888;">← Volver al Panel</a>
    </div>
</body>
</html>