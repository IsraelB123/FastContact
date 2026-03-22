<?php
require_once "config.php";
$mensaje = "";
$tipo_mensaje = ""; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; 
    $empresa = trim($_POST['empresa'] ?? '');

    // 1. Verificamos si ya existe una solicitud con ese email en la tabla de tránsito
    $check = $conn->prepare("SELECT id FROM solicitudes_proveedores WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $mensaje = "Ya tenemos una solicitud pendiente con este correo.";
        $tipo_mensaje = "error";
    } else {
        // 2. INSERT simple a la tabla de solicitudes_proveedores
        $sql = "INSERT INTO solicitudes_proveedores (nombre_contacto, email, password_sugerida, nombre_empresa) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $email, $password, $empresa);
        
        if ($stmt->execute()) {
            $mensaje = "¡Solicitud enviada! Aparecerá en el Panel Admin para su aprobación.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al enviar: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud Proveedor – FastContact</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at top left, #ffb347 0, #ff7f32 30%, #1f1f1f 100%);
            color: #fff;
        }
        .container { width: 100%; max-width: 420px; padding: 20px; }
        .card {
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(14px);
            border-radius: 18px;
            padding: 26px 24px 22px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.4);
            position: relative;
        }
        .back-link { position: absolute; top: 12px; left: 16px; font-size: 11px; }
        .back-link a { color: #ffb347; text-decoration: none; font-weight: 500; }
        .logo { text-align: center; margin-bottom: 12px; }
        .logo h1 { margin: 0; font-size: 30px; }
        .logo span { font-size: 11px; opacity: 0.85; }
        h2 { font-size: 16px; text-align: center; margin: 8px 0 14px; font-weight: 500; }
        .field { margin-bottom: 14px; }
        label { display: block; font-size: 12px; margin-bottom: 4px; color: #f0f0f0; }
        input {
            width: 100%; padding: 9px 11px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.25); background: rgba(0,0,0,0.35);
            color: #fff; font-size: 13px;
        }
        .btn-primary {
            width: 100%; padding: 10px; border-radius: 999px; border: none;
            background: #ff7f32; color: #1b1b1b; font-size: 14px; font-weight: 600;
            cursor: pointer; margin-top: 6px; box-shadow: 0 8px 24px rgba(0,0,0,0.35);
        }
        .success { background: rgba(75, 181, 67, 0.2); border: 1px solid #4bb543; color: #8fef88; padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 12px; text-align: center; }
        .error { background: rgba(255, 87, 87, 0.2); border: 1px solid #ff7878; color: #ffb3b3; padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="back-link"><a href="login.php">← Volver al login</a></div>
            <div class="logo"><h1>FastContact</h1><span>Registro B2B</span></div>
            <h2>Solicitud de Proveedor</h2>

            <?php if ($mensaje): ?>
                <div class="<?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="field">
                    <label>Nombre del contacto</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="field">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="empresa" required>
                </div>
                <div class="field">
                    <label>Correo electrónico</label>
                    <input type="email" name="email" required>
                </div>
                <div class="field">
                    <label>Contraseña sugerida</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Enviar Postulación</button>
            </form>
        </div>
    </div>
</body>
</html>