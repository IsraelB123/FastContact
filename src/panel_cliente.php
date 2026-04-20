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
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            color: #f8fafc;
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 25px 20px 30px;
        }
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
        .logo {
            font-weight: 700;
            letter-spacing: 0.5px;
            font-size: 24px;
        }
        .logo span {
            font-weight: 400;
            font-size: 12px;
            display: block;
            color: #38bdf8;
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
            color: #94a3b8;
            cursor: pointer;
            text-decoration: underline;
        }
        .btn-logout:hover {
            color: #f8fafc;
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
        h1 {
            font-size: 22px;
            margin: 0 0 8px;
            color: #f8fafc;
        }
        h2 {
            font-size: 18px;
            margin: 0 0 8px;
            color: #f8fafc;
        }
        .subtitle {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .tagline {
            font-size: 13px;
            color: #64748b;
        }

        .providers-card {
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 10px;
            text-align: left;
        }
        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #38bdf8;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        td {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-disponible {
            background: rgba(52, 211, 153, 0.15);
            color: #6ee7b7;
        }
        .badge-ocupado {
            background: rgba(251, 191, 36, 0.15);
            color: #fcd34d;
        }
        .badge-estado-pendiente {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
        }
        .badge-estado-enproceso {
            background: rgba(139, 92, 246, 0.15);
            color: #c4b5fd;
        }
        .badge-estado-completado {
            background: rgba(52, 211, 153, 0.15);
            color: #6ee7b7;
        }
        .badge-estado-cancelado {
            background: rgba(248, 113, 113, 0.15);
            color: #fca5a5;
        }

        .btn-contact {
            padding: 8px 16px;
            border-radius: 12px;
            border: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            background: #38bdf8;
            color: #0f172a;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(56, 189, 248, 0.2);
        }
        .btn-contact:hover {
            background: #7dd3fc;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.3);
        }
        
        .btn-detail {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-detail:hover {
            background: rgba(56, 189, 248, 0.1);
            border-color: #38bdf8;
        }

        .small {
            font-size: 11px;
            color: #64748b;
            margin-top: 10px;
            display: block;
        }
    </style>
</head>
<body>
<div class="page">
    <header>
        <div class="logo">
            FastContact
            <span>PANEL DEL CLIENTE</span>
        </div>
        <div class="user-info">
            <div>Sesión iniciada como <strong><?php echo htmlspecialchars($userName); ?></strong></div>
            <form method="post" action="logout.php">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <section class="card">
        <h1>Bienvenido, <?php echo htmlspecialchars($userName); ?></h1>
        <p class="subtitle">
            Desde este panel podrás ver los proveedores registrados y generar pedidos directamente con ellos.
        </p>
        <p class="tagline">
            Selecciona un proveedor de la lista para iniciar un pedido y revisa el estado de tus pedidos más abajo.
        </p>
    </section>

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
                        <td><strong><?php echo htmlspecialchars($row['nombre_empresa']); ?></strong></td>
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
                    <td colspan="6" style="text-align:center; opacity:0.5; padding: 20px;">No hay proveedores registrados todavía.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <p class="small">
            Nota: La opción "Realizar pedido" te llevará a un apartado donde podrás seleccionar los productos del proveedor.
        </p>
    </section>

    <section class="card">
        <h2>Estado de mis pedidos</h2>
        <p class="subtitle">
            Aquí puedes ver los pedidos que has realizado y su estado actual.
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
                        <td>#<?php echo (int)$pedido['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($pedido['proveedor']); ?></strong></td>
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
                            <a href="detalle_pedido_cliente.php?id=<?php echo (int)$pedido['id']; ?>" class="btn-detail">Ver detalle</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; opacity:0.5; padding: 20px;">Aún no has realizado pedidos desde la plataforma.</td>
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