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

$proveedorId = $perfil['id']; 
// ------------------------------------------

$mensaje = "";
$tipo_msj = "";

// 2. Lógica para ELIMINAR producto 
if (isset($_GET['eliminar_id'])) {
    $id_prod = (int)$_GET['eliminar_id'];
    $stmt = $conn->prepare("DELETE FROM provider_products WHERE id = ? AND proveedor_id = ?");
    $stmt->bind_param("ii", $id_prod, $proveedorId);
    if ($stmt->execute()) {
        $mensaje = "El producto ha sido eliminado permanentemente del catálogo.";
        $tipo_msj = "success";
    } else {
        $mensaje = "Error al intentar eliminar el producto.";
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
        * { box-sizing: border-box; transition: all 0.2s ease; }
        body { 
            font-family: 'Inter', system-ui, sans-serif; 
            margin: 0; 
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            color: #f8fafc; 
            min-height: 100vh;
            padding: 30px 20px;
        }
        .page { max-width: 1000px; margin: 0 auto; }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .back-link {
            color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 14px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .back-link:hover { text-decoration: underline; color: #7dd3fc; }

        .btn-add { 
            background: #38bdf8; 
            color: #0f172a; 
            padding: 10px 20px; 
            border-radius: 12px; 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 13px;
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.2);
        }
        .btn-add:hover { background: #7dd3fc; transform: translateY(-2px); box-shadow: 0 12px 25px rgba(56, 189, 248, 0.3); }

        .card { 
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 35px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            overflow-x: auto;
        }

        h1 { margin: 0 0 5px; font-size: 26px; color: #f8fafc; }
        .subtitle { color: #94a3b8; font-size: 14px; margin-bottom: 25px; }

        .msg { padding: 12px 15px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; text-align: center; font-weight: 500; }
        .msg.success { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.3); color: #6ee7b7; }
        .msg.error { background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.3); color: #fca5a5; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; color: #38bdf8; padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .sku-badge { 
            background: rgba(0,0,0,0.4); padding: 4px 8px; border-radius: 8px; 
            font-family: monospace; font-size: 12px; color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); 
        }

        .stock-agotado { color: #fca5a5; font-weight: bold; background: rgba(248, 113, 113, 0.1); padding: 4px 8px; border-radius: 8px; font-size: 12px;}
        .stock-bajo { color: #fcd34d; font-weight: bold; background: rgba(251, 191, 36, 0.1); padding: 4px 8px; border-radius: 8px; font-size: 12px;}
        .stock-ok { color: #6ee7b7; background: rgba(52, 211, 153, 0.1); padding: 4px 8px; border-radius: 8px; font-size: 12px;}

        .btn-del { 
            color: #fca5a5; text-decoration: none; font-size: 12px; font-weight: 600;
            padding: 8px 14px; border: 1px solid rgba(248, 113, 113, 0.3); border-radius: 10px; 
            background: rgba(248, 113, 113, 0.05); display: inline-block;
        }
        .btn-del:hover { background: rgba(248, 113, 113, 0.15); border-color: #fca5a5; transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="page">
        <div class="header-actions">
            <a href="panel_proveedor.php" class="back-link">← Volver al Panel</a>
            <a href="crear_producto.php" class="btn-add"><span>+</span> Nuevo Producto</a>
        </div>
        
        <div class="card">
            <h1>Gestión de Inventario</h1>
            <p class="subtitle">Administra los productos y el stock que ofreces a tus clientes.</p>

            <?php if ($mensaje): ?>
                <div class="msg <?= $tipo_msj ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio (MXN)</th>
                        <th>Stock</th>
                        <th>SKU</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nombre_producto']) ?></strong></td>
                            <td style="font-weight: 600;">$<?= number_format($row['precio_unitario'], 2) ?></td>
                            <td>
                                <?php if ($row['stock_disponible'] <= 0): ?>
                                    <span class="stock-agotado">🚫 Agotado</span>
                                <?php elseif ($row['stock_disponible'] < 10): ?>
                                    <span class="stock-bajo">⚠️ <?= $row['stock_disponible'] ?> unds.</span>
                                <?php else: ?>
                                    <span class="stock-ok">✅ <?= $row['stock_disponible'] ?> unds.</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="sku-badge"><?= htmlspecialchars($row['sku_proveedor']) ?></span></td>
                            <td>
                                <a href="?eliminar_id=<?= $row['id'] ?>" class="btn-del" onclick="return confirm('¿Estás seguro de eliminar este producto del catálogo? Esta acción no se puede deshacer.')">🗑️ Eliminar</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">Aún no tienes productos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>