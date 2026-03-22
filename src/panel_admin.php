<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

// SEGURIDAD: Solo el admin puede entrar
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Opcional: Obtener algunas estadísticas rápidas
$total_solicitudes = $conn->query("SELECT COUNT(*) as total FROM solicitudes_proveedores")->fetch_assoc()['total'];
$total_proveedores = $conn->query("SELECT COUNT(*) as total FROM users WHERE rol = 'proveedor' AND estado = 'activo'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Maestro – FastContact Admin</title>
    <style>
        body { font-family: sans-serif; background: #121212; color: #fff; margin: 0; padding: 40px; }
        .header { margin-bottom: 40px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #ff7f32; }
        
        .grid-menu { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
        }

        .menu-card {
            background: #1e1e1e;
            border: 1px solid #333;
            padding: 25px;
            border-radius: 15px;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .menu-card:hover {
            border-color: #ff7f32;
            transform: translateY(-5px);
            background: #252525;
        }

        .icon { font-size: 30px; }
        .title { font-size: 18px; font-weight: bold; color: #ff7f32; }
        .desc { font-size: 13px; color: #aaa; }
        .badge {
            background: #ff5757;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            width: fit-content;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Panel de Administración Maestro</h1>
        <p>Bienvenido, Administrador. Gestiona el ecosistema de FastContact.</p>
    </div>

    <div class="grid-menu">
        <a href="admin_usuarios.php" class="menu-card">
            <div class="icon">👤</div>
            <div class="title">Solicitudes Nuevas</div>
            <div class="desc">Aprobar o rechazar nuevos proveedores que desean unirse.</div>
            <?php if($total_solicitudes > 0): ?>
                <div class="badge"><?= $total_solicitudes ?> pendientes</div>
            <?php endif; ?>
        </a>

        <a href="#" class="menu-card">
            <div class="icon">🏭</div>
            <div class="title">Proveedores Activos</div>
            <div class="desc">Ver lista de empresas registradas (<?= $total_proveedores ?> actuales).</div>
        </a>

        <a href="#" class="menu-card">
            <div class="icon">📊</div>
            <div class="title">Historial de Pedidos</div>
            <div class="desc">Monitorear todas las transacciones entre clientes y proveedores.</div>
        </a>

        <a href="logout.php" class="menu-card" style="border-color: #444;">
            <div class="icon">🚪</div>
            <div class="title">Cerrar Sesión</div>
            <div class="desc">Salir del panel administrativo de forma segura.</div>
        </a>
    </div>

</body>
</html>