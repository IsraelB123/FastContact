<?php
session_save_path('/tmp'); 
session_start();
require_once "config.php";

// Verificar que haya sesión y que sea cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

// Ahora sí asignamos las variables una sola vez
$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Cliente';

// Obtener lista de proveedores
$sql = "SELECT 
            p.id,
            p.nombre_empresa,
            c.nombre_categoria,
            p.nombre_contacto,
            p.telefono_contacto,
            p.disponibilidad,
            p.tipo_proveedor
        FROM provider_profiles p
        LEFT JOIN provider_categories c ON p.categoria_id = c.id
        ORDER BY p.nombre_empresa ASC";

$result = $conn->query($sql);

// Obtener pedidos realizados por este cliente
$sqlPedidos = "
    SELECT 
        o.id,
        o.fecha_pedido,
        o.estado,
        o.total,
        p.nombre_empresa AS proveedor
    FROM orders o
    INNER JOIN provider_profiles p ON o.proveedor_id = p.id
    WHERE o.cliente_id = ?
    ORDER BY o.fecha_pedido DESC, o.id DESC
";
$stmtPedidos = $conn->prepare($sqlPedidos);
$stmtPedidos->bind_param("i", $userId);
$stmtPedidos->execute();
$pedidosResult = $stmtPedidos->get_result();
$stmtPedidos->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Cliente – FastContact</title>
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
            padding: 20px 16px 30px;
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
        .user-info {
            text-align: right;
            font-size: 13px;
        }
        .user-info strong {
            font-weight: 600;
        }
        .btn-logout {
            margin-top: 6px;
            font-size: 11px;
            border: none;
            background: transparent;
            color: #ffdddd;
            cursor: pointer;
            text-decoration: underline;
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

        .providers-card {
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 6px;
        }
        th, td {
            padding: 8px 6px;
            text-align: left;
        }
        th {
            font-size: 12px;
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
        .badge-estado-pendiente {
            background: rgba(255,196,0,0.15);
            color: #ffd56a;
        }
        .badge-estado-enproceso {
            background: rgba(0,190,255,0.15);
            color: #9de6ff;
        }
        .badge-estado-completado {
            background: rgba(87,255,135,0.15);
            color: #b4ffcf;
        }
        .badge-estado-cancelado {
            background: rgba(255,87,87,0.15);
            color: #ffb0b0;
        }

        .btn-contact {
            padding: 6px 10px;
            border-radius: 999px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: #ff7f32;
            color: #1b1b1b;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-contact:hover {
            background: #ff954f;
            transform: translateY(-1px);
        }
        .small {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 6px;
        }
        a {
            color: #ffb347;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            FastContact
            <span>Panel del cliente</span>
        </div>
        <div class="user-info">
            <div>Sesión iniciada como <strong><?php echo htmlspecialchars($userName); ?></strong></div>
            <form method="post" action="logout.php">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <!-- Tarjeta de bienvenida -->
    <section class="card">
        <h1>Bienvenido, <?php echo htmlspecialchars($userName); ?></h1>
        <p class="subtitle">
            Desde este panel podrás ver los proveedores registrados y generar pedidos directamente con ellos.
        </p>
        <p class="tagline">
            Selecciona un proveedor de la lista para iniciar un pedido y revisa el estado de tus pedidos más abajo.
        </p>
    </section>

    <!-- Lista de proveedores -->
    <section class="card providers-card">
        <h1>Proveedores disponibles</h1>
        <p class="subtitle">
            Proveedores como Coca-Cola, Bimbo, Lala y otros aliados comerciales de tu tienda.
        </p>

        <table>
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Categoría</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Disponibilidad</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_categoria'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_contacto']); ?></td>
                        <td><?php echo htmlspecialchars($row['telefono_contacto']); ?></td>
                        <td>
                            <?php if ($row['disponibilidad'] === 'disponible'): ?>
                                <span class="badge badge-disponible">Disponible</span>
                            <?php elseif ($row['disponibilidad'] === 'ocupado'): ?>
                                <span class="badge badge-ocupado">Ocupado</span>
                            <?php else: ?>
                                <span class="badge"><?php echo htmlspecialchars($row['disponibilidad']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="get" action="crear_pedido.php" style="margin:0;">
                                <input type="hidden" name="proveedor_id" value="<?php echo (int)$row['id']; ?>">
                                <button type="submit" class="btn-contact">Realizar pedido</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No hay proveedores registrados todavía.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <p class="small">
            Nota: La opción "Realizar pedido" te llevará a un apartado donde podrás seleccionar los productos del proveedor.
        </p>
    </section>

    <!-- NUEVA SECCIÓN: Estado de mis pedidos -->
    <section class="card">
        <h2>Estado de mis pedidos</h2>
        <p class="subtitle">
            Aquí puedes ver los pedidos que has realizado y su estado actual (pendiente, en proceso, completado o cancelado).
        </p>

        <table>
            <thead>
            <tr>
                <th># Pedido</th>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Total (MXN)</th>
                <th>Detalle</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($pedidosResult && $pedidosResult->num_rows > 0): ?>
                <?php while ($pedido = $pedidosResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$pedido['id']; ?></td>
                        <td><?php echo htmlspecialchars($pedido['proveedor']); ?></td>
                        <td><?php echo htmlspecialchars($pedido['fecha_pedido']); ?></td>
                        <td>
                            <?php
                            $estado = $pedido['estado'];
                            if ($estado === 'pendiente') {
                                echo '<span class="badge badge-estado-pendiente">Pendiente</span>';
                            } elseif ($estado === 'en_proceso') {
                                echo '<span class="badge badge-estado-enproceso">En proceso</span>';
                            } elseif ($estado === 'completado') {
                                echo '<span class="badge badge-estado-completado">Completado</span>';
                            } elseif ($estado === 'cancelado') {
                                echo '<span class="badge badge-estado-cancelado">Cancelado</span>';
                            } else {
                                echo '<span class="badge">'.htmlspecialchars($estado).'</span>';
                            }
                            ?>
                        </td>
                        <td>$<?php echo number_format((float)$pedido['total'], 2); ?></td>
                        <td>
                            <!-- Opcional: vista de detalle solo lectura para el cliente -->
                            <a href="detalle_pedido_cliente.php?id=<?php echo (int)$pedido['id']; ?>">Ver detalle</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Aún no has realizado pedidos desde la plataforma.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <p class="small">
            El estado de tus pedidos se actualiza conforme el proveedor los revisa y procesa.
        </p>
    </section>
</div>
</body>
</html>
<?php

?>
