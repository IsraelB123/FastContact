<?php
session_save_path('/tmp');
session_start();
require_once "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password_ingresada = trim($_POST['password'] ?? ''); 

    if ($email === '' || $password_ingresada === '') {
        $error = "Por favor, ingresa tu correo y contraseña.";
    } else {
        $stmt = $conn->prepare("SELECT id, nombre, email, password_hash, rol, estado FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // AQUÍ ESTÁ EL CAMBIO CLAVE: fetch_assoc()
        if ($row = $result->fetch_assoc()) {
            $password_valida = false;

            // 1. Validar con BCrypt (Hash)
            if (password_verify($password_ingresada, $row['password_hash'])) {
                $password_valida = true;
            } 
            // 2. Validar Texto Plano (Para tu usuario admin@test.com)
            elseif ($password_ingresada === $row['password_hash']) {
                $password_valida = true;
            }

            if ($password_valida) {
                if ($row['estado'] !== 'activo') {
                    $error = "Tu cuenta no se encuentra activa.";
                } else {
                    $_SESSION['user_id']   = $row['id'];
                    $_SESSION['user_name'] = $row['nombre'];
                    $_SESSION['user_rol']  = $row['rol'];

                    // Ejemplo de lógica de redirección por rol
                    if ($_SESSION['user_rol'] === 'admin') {
                        header("Location: panel_admin.php");
                    } elseif ($_SESSION['user_rol'] === 'proveedor') {
                        header("Location: panel_proveedor.php");
                    } else {
                        header("Location: panel_cliente.php");
                    }
                    exit;
                }
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            // Esto te ayudará a saber si realmente encontró el correo
            $error = "No se encontró una cuenta con el correo: " . htmlspecialchars($email);
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión – FastContact</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at top left, #ffb347 0, #ff7f32 30%, #1f1f1f 100%);
            color: #fff;
        }
        .container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .card {
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(14px);
            border-radius: 18px;
            padding: 26px 24px 22px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.4);
            position: relative;
        }
        .back-link {
            position: absolute;
            top: 12px;
            left: 16px;
            font-size: 11px;
        }
        .back-link a {
            color: #ffb347;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .logo {
            text-align: center;
            margin-bottom: 12px;
        }
        .logo h1 {
            margin: 0;
            font-size: 30px;
            letter-spacing: 0.5px;
        }
        .logo span {
            font-size: 11px;
            opacity: 0.85;
        }
        h2 {
            font-size: 16px;
            text-align: center;
            margin: 8px 0 14px;
            font-weight: 500;
        }
        .field {
            margin-bottom: 14px;
        }
        label {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
            color: #f0f0f0;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 9px 11px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.25);
            background: rgba(0,0,0,0.35);
            color: #fff;
            font-size: 13px;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            color: rgba(255,255,255,0.5);
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #ffb347;
            box-shadow: 0 0 0 2px rgba(255,180,71,0.3);
            background: rgba(0,0,0,0.55);
        }
        .btn-primary {
            width: 100%;
            padding: 10px 18px;
            border-radius: 999px;
            border: none;
            background: #ff7f32;
            color: #1b1b1b;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
            transition: background 0.2s, transform 0.1s, box-shadow 0.15s;
        }
        .btn-primary:hover {
            background: #ff954f;
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: scale(0.98);
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
        }
        .btn-primary span.icon {
            font-size: 16px;
        }
        .error {
            background: rgba(255, 87, 87, 0.1);
            border: 1px solid rgba(255, 120, 120, 0.8);
            color: #ffb3b3;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 12px;
        }
        .extra-links {
            margin-top: 12px;
            font-size: 11px;
            text-align: center;
            color: #e0e0e0;
        }
        .extra-links a {
            color: #ffb347;
            text-decoration: none;
            font-weight: 500;
        }
        .extra-links a:hover {
            text-decoration: underline;
        }
        .hint {
            font-size: 10px;
            color: #c9c9c9;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">

            <div class="back-link">
                <a href="index.php">← Volver al inicio</a>
            </div>

            <div class="logo">
                <h1>FastContact</h1>
                <span>Acceso a la plataforma</span>
            </div>

            <h2>Iniciar sesión</h2>

            <?php if ($error): ?>
                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        placeholder="tucorreo@ejemplo.com"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Ingresa tu contraseña"
                        required
                    >
                    <div class="hint">
                       
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <span class="icon">➡️</span>
                    <span>Entrar</span>
                </button>
            </form>

            <div class="extra-links">
                ¿Eres nuevo en FastContact?<br>
                <a href="registro_cliente.php">Solicitar alta como cliente</a> · 
                <a href="solicitud_proveedor.php">Registrar proveedor</a>
            </div>
        </div>
    </div>
</body>
</html>
