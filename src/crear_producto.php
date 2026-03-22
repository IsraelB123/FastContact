<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// Seguridad: Solo proveedores
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $proveedorId = $_SESSION['user_id'];
    $nombre = trim($_POST['nombre_producto']);
    $desc = trim($_POST['descripcion']);
    $categoria = $_POST['categoria'];
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $sku = trim($_POST['sku']);

    // 1. SQL ajustado a los nombres reales de TablePlus
    $sql = "INSERT INTO provider_products (proveedor_id, nombre_producto, descripcion, categoria_producto, precio_unitario, stock_disponible, sku_proveedor, activo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // Esto nos dirá si falló otra columna (ej. precio_unitario)
        die("Error en el prepare: " . $conn->error);
    }

    $activo = 1;
    // 2. Ahora enviamos el texto de la categoría (ej. 'Botanas')
    // Usamos 's' para categoria_producto
    $stmt->bind_param("isssdisi", $proveedorId, $nombre, $desc, $categoria, $precio, $stock, $sku, $activo);

    if ($stmt->execute()) {
        header("Refresh: 2; url=gestionar_productos.php"); 
        $mensaje = "¡Producto publicado con éxito! Redirigiendo...";
        $tipo = "success";
    } else {
        die("ERROR AL EJECUTAR: " . $stmt->error);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Producto – FastContact</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #fff; display: flex; justify-content: center; padding: 40px; }
        .form-card { background: #2a2a2a; padding: 25px; border-radius: 15px; width: 100%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .field { margin-bottom: 15px; }
        label { display: block; font-size: 13px; margin-bottom: 5px; color: #ff7f32; }
        input, select, textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #444; background: #1a1a1a; color: #fff; box-sizing: border-box; }
        .btn-save { width: 100%; padding: 12px; background: #ff7f32; border: none; border-radius: 8px; color: #fff; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .msg { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-size: 14px; }
        .success { background: rgba(75, 181, 67, 0.2); color: #8fef88; }
        .error { background: rgba(255, 87, 87, 0.2); color: #ffb3b3; }
    </style>
</head>
<body>
    <div class="form-card">
        <a href="gestionar_productos.php" style="color: #888; text-decoration: none; font-size: 12px;">← Volver a mi lista</a>
        <h1 style="font-size: 22px; margin-top: 10px;">Añadir Producto</h1>

        <?php if ($mensaje): ?>
            <div class="msg <?= $tipo ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label>Nombre del Producto</label>
                <input type="text" name="nombre_producto" placeholder="Ej. Papas Sabritas Sal 45g" required>
            </div>
            <div class="field">
                <label>Descripción</label>
                <textarea name="descripcion" rows="2" placeholder="Breve descripción del producto..."></textarea>
            </div>
            <div class="field">
                <label>Categoría</label>
                <select name="categoria">
                    <option value="Botanas">Botanas</option>
                    <option value="Bebidas">Bebidas</option>
                    <option value="Lácteos">Lácteos</option>
                    <option value="Panificados">Panificados</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <div class="field" style="flex: 1;">
                    <label>Precio Unitario (MXN)</label>
                    <input type="number" step="0.01" name="precio" placeholder="15.50" required>
                </div>
                <div class="field" style="flex: 1;">
                    <label>Stock Inicial</label>
                    <input type="number" name="stock" placeholder="100" required>
                </div>
            </div>
            <div class="field">
                <label>SKU (Código Interno)</label>
                <input type="text" name="sku" placeholder="SAB-SAL-45" required>
            </div>
            <button type="submit" class="btn-save">Publicar Producto</button>
        </form>
    </div>
</body>
</html>