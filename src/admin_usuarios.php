<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// 1. Seguridad: Solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    die("Acceso denegado.");
}

$mensaje = "";

// 2. LÓGICA DE APROBACIÓN (La transferencia)
if (isset($_GET['aprobar_id'])) {
    $solicitud_id = (int)$_GET['aprobar_id'];

    // Buscamos los datos en la tabla de tránsito
    $stmt = $conn->prepare("SELECT * FROM solicitudes_proveedores WHERE id = ?");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();

    if ($solicitud) {
        $conn->begin_transaction();
        try {
            // A. Insertar en tabla oficial USERS
            $sqlU = "INSERT INTO users (nombre, email, password_hash, rol, estado) VALUES (?, ?, ?, 'proveedor', 'activo')";
            $stmtU = $conn->prepare($sqlU);
            $stmtU->bind_param("sss", $solicitud['nombre_contacto'], $solicitud['email'], $solicitud['password_sugerida']);
            $stmtU->execute();
            $newUserId = $conn->insert_id;

            // B. Insertar en tabla oficial PROVIDER_PROFILES
            $sqlP = "INSERT INTO provider_profiles (user_id, nombre_empresa, nombre_contacto, disponibilidad) VALUES (?, ?, ?, 'disponible')";
            $stmtP = $conn->prepare($sqlP);
            $stmtP->bind_param("iss", $newUserId, $solicitud['nombre_empresa'], $solicitud['nombre_contacto']);
            $stmtP->execute();

            // C. Borrar de la tabla de tránsito
            $conn->query("DELETE FROM solicitudes_proveedores WHERE id = $solicitud_id");

            $conn->commit();
            $mensaje = "¡Proveedor aprobado y transferido con éxito!";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al procesar: " . $e->getMessage();
        }
    }
}

// 3. Obtener solicitudes pendientes actuales
$result = $conn->query("SELECT * FROM solicitudes_proveedores ORDER BY fecha_solicitud DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes B2B – FastContact Admin</title>
    <style>
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body { 
            font-family: 'Inter', system-ui, sans-serif; 
            margin: 0; 
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            color: #f8fafc; 
            padding: 30px 20px;
            min-height: 100vh;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .back-link { color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 25px; }
        
        .card { 
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 35px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        }

        h1 { margin: 0 0 10px; font-size: 26px; color: #f8fafc; }
        .msg { 
            background: rgba(52, 211, 153, 0.1); 
            border: 1px solid rgba(52, 211, 153, 0.3); 
            color: #6ee7b7; 
            padding: 12px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            font-size: 14px;
            text-align: center;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; color: #38bdf8; padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .btn-approve { 
            background: #38bdf8; 
            color: #0f172a; 
            padding: 8px 16px; 
            border-radius: 10px; 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 12px;
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2);
        }
        .btn-approve:hover { background: #7dd3fc; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <a href="panel_admin.php" class="back-link">← Volver al Panel</a>
        <div class="card">
            <h1>Solicitudes Pendientes</h1>
            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 25px;">Revisa y aprueba a los nuevos aliados comerciales de la red.</p>
            
            <?php if ($mensaje): ?><div class="msg"><?= $mensaje ?></div><?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Email</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nombre_empresa']) ?></strong></td>
                                <td><?= htmlspecialchars($row['nombre_contacto']) ?></td>
                                <td style="color: #cbd5e1;"><?= htmlspecialchars($row['email']) ?></td>
                                <td style="font-size: 12px; opacity: 0.7;"><?= date('d/m/Y', strtotime($row['fecha_solicitud'])) ?></td>
                                <td><a href="?aprobar_id=<?= $row['id'] ?>" class="btn-approve">APROBAR</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:40px; color: #64748b;">No hay solicitudes en este momento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>