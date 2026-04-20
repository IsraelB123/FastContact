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
    $conn->begin_transaction(); // Inicia el modo seguro
    try {
        // CORRECCIÓN: Usamos la sesión directamente
        $sesionId = $_SESSION['user_id'];

        // 1. BUSCAR EL ID DEL PERFIL (No el del usuario)
        $sqlPerfil = "SELECT id FROM provider_profiles WHERE user_id = ?";
        $stmtP = $conn->prepare($sqlPerfil);
        $stmtP->bind_param("i", $sesionId);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        $perfil = $resP->fetch_assoc();
        
        if (!$perfil) {
            throw new Exception("No se encontró un perfil de empresa para este usuario.");
        }

        $proveedorId = $perfil['id']; 
        $nombre = trim($_POST['nombre_producto']);
        $desc = trim($_POST['descripcion']);
        $categoria = $_POST['categoria'];
        $unidad = $_POST['unidad_medida']; 
        $precio_sucio = str_replace(['$', ','], '', $_POST['precio']); 
        $precio = (float)$precio_sucio;
        $stock = (int)$_POST['stock'];
        $sku = trim($_POST['sku']);
        $activo = 1;

        // 2. VALIDACIÓN DE SKU DUPLICADO
        $checkSku = $conn->prepare("SELECT id FROM provider_products WHERE sku_proveedor = ? AND proveedor_id = ?");
        $checkSku->bind_param("si", $sku, $proveedorId);
        $checkSku->execute();
        
        if ($checkSku->get_result()->num_rows > 0) {
            $mensaje = "El SKU '$sku' ya está registrado en tu inventario.";
            $tipo = "error";
            $conn->rollback(); // Cancelamos porque hay un error de usuario
        } else {
            // 3. PROCEDER CON EL INSERT
            $sql = "INSERT INTO provider_products (proveedor_id, nombre_producto, descripcion, categoria_producto, unidad_medida, precio_unitario, stock_disponible, sku_proveedor, activo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en el prepare: " . $conn->error);
            }

            // Bind con 9 parámetros: i s s s s d i s i
            $stmt->bind_param("issssdisi", $proveedorId, $nombre, $desc, $categoria, $unidad, $precio, $stock, $sku, $activo);

            if ($stmt->execute()) {
                $conn->commit(); // GUARDADO DEFINITIVO ✅
                $mensaje = "¡Producto publicado con éxito! Redirigiendo al catálogo...";
                $tipo = "success";
                header("Refresh: 2; url=gestionar_productos.php"); 
            } else {
                throw new Exception("Error al ejecutar el insert: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        $conn->rollback(); // Si algo falló en el código, deshacemos todo
        $mensaje = "Error crítico: " . $e->getMessage();
        $tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Producto – FastContact</title>
    <style>
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body { 
            font-family: 'Inter', system-ui, sans-serif; 
            margin: 0; 
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            color: #f8fafc; 
            display: flex; 
            justify-content: center; 
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .form-card { 
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 35px 30px; 
            border-radius: 24px; 
            width: 100%; 
            max-width: 550px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.4); 
            height: fit-content;
        }

        .back-link {
            color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px; margin-bottom: 20px;
        }
        .back-link:hover { text-decoration: underline; color: #7dd3fc; }

        h1 { margin: 0 0 5px; font-size: 24px; color: #f8fafc; }
        .subtitle { color: #94a3b8; font-size: 13px; margin-bottom: 25px; }

        .msg { padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-size: 13px; font-weight: 500; }
        .msg.success { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.3); color: #6ee7b7; }
        .msg.error { background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.3); color: #fca5a5; }

        .field { margin-bottom: 18px; }
        label { display: block; font-size: 12px; margin-bottom: 8px; color: #94a3b8; font-weight: 600; }
        
        input, select, textarea { 
            width: 100%; 
            padding: 12px 14px; 
            border-radius: 12px; 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            background: rgba(0,0,0,0.3); 
            color: #fff; 
            font-size: 14px;
            font-family: inherit;
        }
        
        input::placeholder, textarea::placeholder { color: #475569; }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            background: rgba(0,0,0,0.5);
        }

        /* Estilo especial para los select options en modo oscuro */
        select option { background: #1e293b; color: #fff; }

        .row { display: flex; gap: 15px; }
        .row .field { flex: 1; }

        .btn-save { 
            width: 100%; 
            padding: 14px; 
            background: #38bdf8; 
            color: #0f172a; 
            border: none; 
            border-radius: 12px; 
            font-size: 14px;
            font-weight: 700; 
            cursor: pointer; 
            margin-top: 10px; 
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.2);
        }
        .btn-save:hover { background: #7dd3fc; transform: translateY(-2px); box-shadow: 0 12px 25px rgba(56, 189, 248, 0.3); }
        .btn-save:active { transform: scale(0.98); }
    </style>
</head>
<body>
    <div class="form-card">
        <a href="gestionar_productos.php" class="back-link">← Volver al catálogo</a>
        
        <h1>Añadir Producto</h1>
        <p class="subtitle">Ingresa los detalles del nuevo artículo para tu catálogo B2B.</p>

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
                <textarea name="descripcion" rows="3" placeholder="Breve descripción, ingredientes o detalles técnicos..."></textarea>
            </div>

            <div class="row">
                <div class="field">
                    <label>Categoría</label>
                    <select name="categoria">
                        <option value="Botanas">Botanas</option>
                        <option value="Bebidas">Bebidas</option>
                        <option value="Lácteos">Lácteos</option>
                        <option value="Panificados">Panificados</option>
                        <option value="Abarrotes">Abarrotes</option>
                    </select>
                </div>
                <div class="field">
                    <label>Unidad de Medida</label>
                    <select name="unidad_medida">
                        <option value="pieza">Pieza</option>
                        <option value="paquete">Paquete</option>
                        <option value="botella">Botella</option>
                        <option value="caja">Caja</option>
                        <option value="bolsa">Bolsa</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="field">
                    <label>Precio Unitario (MXN)</label>
                    <input type="number" step="0.01" name="precio" min="0" placeholder="15.50" required>
                </div>
                <div class="field">
                    <label>Stock Inicial</label>
                    <input type="number" name="stock" min="0" placeholder="100" required>
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