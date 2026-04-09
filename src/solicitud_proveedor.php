<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

$mensaje = "";
$tipo_msj = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre_contacto = trim($_POST['nombre_contacto'] ?? '');
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_sugerida = trim($_POST['password_sugerida'] ?? '');

    if ($nombre_contacto && $nombre_empresa && $email && $password_sugerida) {
        $sql = "INSERT INTO solicitudes_proveedores (nombre_empresa, nombre_contacto, email, password_sugerida) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssss", $nombre_empresa, $nombre_contacto, $email, $password_sugerida);
            if ($stmt->execute()) {
                $mensaje = "¡Postulación enviada con éxito! El administrador revisará tu solicitud.";
                $tipo_msj = "success";
            } else {
                $mensaje = "Error al enviar la solicitud. Intenta de nuevo.";
                $tipo_msj = "error";
            }
            $stmt->close();
        } else {
            $mensaje = "Error de base de datos.";
            $tipo_msj = "error";
        }
    } else {
        $mensaje = "Por favor, completa todos los campos.";
        $tipo_msj = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro B2B – FastContact</title>
    <style>
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* Fondo Deep Tech Blue */
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            color: #f8fafc;
            padding: 20px;
        }
        .container { width: 100%; max-width: 450px; }
        
        .card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 35px 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
        }

        .back-link { position: absolute; top: 20px; left: 20px; font-size: 12px; }
        .back-link a { color: #38bdf8; text-decoration: none; font-weight: 600; }
        .back-link a:hover { color: #7dd3fc; }

        .logo { text-align: center; margin-bottom: 25px; margin-top: 15px; }
        .logo h1 { margin: 0; font-size: 28px; letter-spacing: 0.5px; color: #f8fafc; }
        .logo span { font-size: 11px; color: #38bdf8; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }

        h2 { font-size: 16px; text-align: center; margin: 0 0 20px; font-weight: 400; color: #cbd5e1; }

        .field { margin-bottom: 16px; }
        label { display: block; font-size: 12px; margin-bottom: 6px; color: #94a3b8; font-weight: 500; }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.3);
            color: #fff;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            background: rgba(0,0,0,0.5);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: #38bdf8;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 15px;
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.2);
        }
        .btn-primary:hover {
            background: #7dd3fc;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(56, 189, 248, 0.3);
        }

        .alert {
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        .alert.success { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; border: 1px solid rgba(52, 211, 153, 0.3); }
        .alert.error { background: rgba(248, 113, 113, 0.15); color: #fca5a5; border: 1px solid rgba(248, 113, 113, 0.3); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="back-link">
                <a href="login.php">← Volver al login</a>
            </div>

            <div class="logo">
                <h1>FastContact</h1>
                <span>Registro B2B</span>
            </div>

            <h2>Solicitud de Proveedor</h2>

            <?php if ($mensaje): ?>
                <div class="alert <?= $tipo_msj ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <form method="post" action="solicitud_proveedor.php">
                <div class="field">
                    <label>Nombre del contacto</label>
                    <input type="text" name="nombre_contacto" required placeholder="Ej. Roberto Sánchez">
                </div>
                
                <div class="field">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="nombre_empresa" required placeholder="Ej. Distribuidora del Norte">
                </div>

                <div class="field">
                    <label>Correo electrónico</label>
                    <input type="email" name="email" required placeholder="ventas@empresa.com">
                </div>

                <div class="field">
                    <label>Contraseña sugerida</label>
                    <input type="password" name="password_sugerida" required placeholder="Crea una contraseña segura">
                </div>

                <button type="submit" class="btn-primary">Enviar Postulación</button>
            </form>
        </div>
    </div>
</body>
</html>