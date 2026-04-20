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
    * { box-sizing: border-box; transition: all 0.3s ease; }
    body {
        margin: 0;
        font-family: 'Inter', system-ui, sans-serif;
        min-height: 100vh;
        /* CAMBIO DE FONDO: De Naranja a Azul Profundo / Carbono */
        background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
        background-attachment: fixed;
        color: #f8fafc;
    }
    .page { max-width: 1200px; margin: 0 auto; padding: 25px 20px; }
    
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: rgba(255, 255, 255, 0.03);
        padding: 20px 25px;
        border-radius: 20px;
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .card {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        margin-bottom: 25px;
    }

    /* ACENTOS: Cambiamos el naranja por un Azul Eléctrico o Cian */
    .btn-main {
        background: #38bdf8; /* Azul Cian */
        color: #0f172a;
        padding: 12px 24px;
        border-radius: 14px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2);
    }
    .btn-main:hover {
        background: #7dd3fc;
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(56, 189, 248, 0.3);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 25px;
    }
    .summary-item {
        background: rgba(255, 255, 255, 0.03);
        padding: 20px;
        border-radius: 18px;
        border-top: 3px solid #38bdf8; /* Acento en la parte superior */
    }
    .summary-item .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
    .summary-item .value { font-size: 32px; font-weight: 800; display: block; margin-top: 5px; }

    th { color: #38bdf8; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    .badge-pendiente { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
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
    
            <a href="crear_producto.php" class="btn-main">
                <span>+</span> Añadir Nuevo Producto
            </a>

            <a href="gestionar_productos.php" style="
                background: rgba(255, 255, 255, 0.05); 
                color: #f8fafc; 
                padding: 12px 20px; 
                border-radius: 14px; 
                text-decoration: none; 
                border: 1px solid rgba(255, 255, 255, 0.1);">
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