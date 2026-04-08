<?php
session_save_path('/tmp'); 
session_start();
require_once "config.php";

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

$proveedorUserId   = $_SESSION['user_id'];
$proveedorNombre   = $_SESSION['user_name'] ?? 'Proveedor';

// 1) Obtener el perfil de proveedor ligado al usuario actual
$sqlProveedor = "SELECT id, nombre_empresa FROM provider_profiles WHERE user_id = ?";
$stmtProv = $conn->prepare($sqlProveedor);
$stmtProv->bind_param("i", $proveedorUserId);
$stmtProv->execute();
$resultProv = $stmtProv->get_result();
$proveedorPerfil = $resultProv->fetch_assoc();
$stmtProv->close();

if (!$proveedorPerfil) {
    // Si no hay perfil, no se pueden mostrar pedidos
    $proveedorPerfilId = null;
    $nombreEmpresa = $proveedorNombre;
} else {
    $proveedorPerfilId = $proveedorPerfil['id'];
    $nombreEmpresa = $proveedorPerfil['nombre_empresa'];
}

// 2) Obtener pedidos asociados a este proveedor
$pedidos = [];
if ($proveedorPerfilId !== null) {
    $sqlPedidos = "
        SELECT 
            o.id,
            o.cliente_id,
            o.proveedor_id,
            o.fecha_pedido,
            o.estado,
            o.total,
            u.nombre AS nombre_cliente,
            u.email AS email_cliente
        FROM orders o
        INNER JOIN users u ON o.cliente_id = u.id
        WHERE o.proveedor_id = ?
        ORDER BY o.fecha_pedido DESC
    ";

    $stmt = $conn->prepare($sqlPedidos);
    $stmt->bind_param("i", $proveedorPerfilId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Proveedor – FastContact</title>
    /* --- REEMPLAZA TU SECCIÓN DE <style> POR ESTA --- */
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: system-ui, -apple-system, sans-serif;
        min-height: 100vh;
        background: radial-gradient(circle at top left, #ffb347 0, #ff7f32 40%, #121212 100%);
        color: #fff;
    }
    .page { max-width: 1100px; margin: 0 auto; padding: 20px 16px; }
    
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        background: rgba(0,0,0,0.2);
        padding: 15px 20px;
        border-radius: 15px;
        backdrop-filter: blur(10px);
    }
    .logo h1 { margin: 0; font-size: 22px; color: #fff; }
    .logo span { font-size: 12px; opacity: 0.8; display: block; }

    .card {
        background: rgba(0,0,0,0.45);
        backdrop-filter: blur(14px);
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 16px 40px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 20px;
    }

    .btn-main {
        padding: 12px 20px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-add { background: #ff7f32; color: #1a1a1a; box-shadow: 0 4px 15px rgba(255, 127, 50, 0.4); }
    .btn-inventory { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
    .btn-main:hover { transform: translateY(-2px); opacity: 0.9; }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .summary-item {
        background: rgba(255,255,255,0.05);
        padding: 15px;
        border-radius: 15px;
        border-left: 4px solid #ff7f32;
    }
    .summary-item .label { display: block; font-size: 11px; text-transform: uppercase; opacity: 0.7; }
    .summary-item .value { font-size: 24px; font-weight: bold; }

    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th { text-align: left; font-size: 11px; text-transform: uppercase; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.2); color: #ffb347; }
    td { padding: 12px; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
    .badge-pendiente { background: rgba(255,179,71,0.2); color: #ffb347; }
</style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            FastContact
            <span>Panel del proveedor</span>
        </div>
        <div class="user-info">
            <div>
                Sesión iniciada como <strong><?php echo htmlspecialchars($proveedorNombre); ?></strong><br>
                <span class="small"><?php echo htmlspecialchars($nombreEmpresa); ?></span>
            </div>
            <form method="post" action="logout.php">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <section class="card">
        <h1>Bienvenido, <?php echo htmlspecialchars($nombreEmpresa); ?></h1>
        <div class="acciones-rapidas" style="margin-bottom: 25px; display: flex; gap: 15px;">
    
            <a href="crear_producto.php" class="btn-accion" style="
                background: #ff7f32; 
                color: #1a1a1a; 
                padding: 12px 20px; 
                border-radius: 10px; 
                text-decoration: none; 
                font-weight: bold; 
                display: flex; 
                align-items: center; 
                gap: 8px;
                box-shadow: 0 4px 15px rgba(255, 127, 50, 0.3);">
                <span>+</span> Añadir Nuevo Producto
            </a>

            <a href="gestionar_productos.php" class="btn-accion" style="
                background: rgba(255, 255, 255, 0.1); 
                color: #fff; 
                padding: 12px 20px; 
                border-radius: 10px; 
                text-decoration: none; 
                font-weight: 500; 
                border: 1px solid rgba(255, 255, 255, 0.2);">
                📦 Gestionar Mi Inventario
            </a>
    
        </div>
        <p class="subtitle">
            Desde este panel podrás ver los pedidos que los clientes han realizado a tu empresa.
        </p>
        <p class="tagline">
            Revisa los pedidos recientes, valida la información y continúa tu proceso interno de surtido y entrega.
        </p>

        <?php
        $totalPedidos = count($pedidos);
        $pendientes = 0;
        $completados = 0;
        $cancelados  = 0;
        foreach ($pedidos as $p) {
            if ($p['estado'] === 'pendiente') $pendientes++;
            if ($p['estado'] === 'completado') $completados++;
            if ($p['estado'] === 'cancelado') $cancelados++;
        }
        ?>
        <div class="summary-row">
            <div class="summary-item">
                <span class="label">Total de pedidos</span>
                <span class="value"><?php echo $totalPedidos; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Pendientes</span>
                <span class="value"><?php echo $pendientes; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Completados</span>
                <span class="value"><?php echo $completados; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Cancelados</span>
                <span class="value"><?php echo $cancelados; ?></span>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Pedidos recibidos</h2>
        <p class="subtitle">
            Aquí se listan los pedidos generados por tus clientes desde la plataforma FastContact.
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total (MXN)</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($pedidos)): ?>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['email_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></td>
                            <td>
                                <?php
                                $estado = $pedido['estado'];
                                if ($estado === 'pendiente') {
                                    echo '<span class="badge badge-pendiente">Pendiente</span>';
                                } elseif ($estado === 'en_proceso') {
                                    echo '<span class="badge badge-proceso">En proceso</span>';
                                } elseif ($estado === 'completado') {
                                    echo '<span class="badge badge-completado">Completado</span>';
                                } elseif ($estado === 'cancelado') {
                                    echo '<span class="badge badge-cancelado">Cancelado</span>';
                                } else {
                                    echo '<span class="badge">'.htmlspecialchars($estado).'</span>';
                                }
                                ?>
                            </td>
                            <td>$<?php echo number_format((float)$pedido['total'], 2); ?></td>
                            <td>
                                <!-- Aquí luego podemos enlazar a un detalle del pedido -->
                                <a href="detalle_pedido.php?id=<?php echo (int)$pedido['id']; ?>" class="btn-action">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="msg-empty">
                            Aún no se han recibido pedidos. Cuando un cliente realice un pedido,
                            aparecerá listado en este apartado.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>