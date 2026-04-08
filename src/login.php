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
        $sql = "SELECT id, nombre, email, password_hash, rol, estado FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Error en la base de datos: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

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
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* CAMBIO DE FONDO: Deep Tech Blue */
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            color: #f8fafc;
        }
        .container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px 25px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 12px;
        }
        .back-link a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link a:hover {
            color: #7dd3fc;
        }
        .logo {
            text-align: center;
            margin-bottom: 15px;
            margin-top: 10px;
        }
        .logo h1 {
            margin: 0;
            font-size: 32px;
            letter-spacing: 0.5px;
            color: #f8fafc;
        }
        .logo span {
            font-size: 11px;
            color: #38bdf8;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        h2 {
            font-size: 18px;
            text-align: center;
            margin: 10px 0 20px;
            font-weight: 500;
            color: #e2e8f0;
        }
        .field {
            margin-bottom: 18px;
        }
        label {
            display: block;
            font-size: 12px;
            margin-bottom: 6px;
            color: #94a3b8;
            font-weight: 500;
        }
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
        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            color: #475569;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            background: rgba(0,0,0,0.5);
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: none;
            background: #38bdf8;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.2);
        }
        .btn-primary:hover {
            background: #7dd3fc;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(56, 189, 248, 0.3);
        }
        .btn-primary:active {
            transform: scale(0.98);
        }
        .error {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.3);
            color: #fca5a5;
            padding: 10px 15px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
        .extra-links {
            margin-top: 25px;
            font-size: 12px;
            text-align: center;
            color: #94a3b8;
            line-height: 1.6;
        }
        .extra-links a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
        }
        .extra-links a:hover {
            color: #7dd3fc;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">

            <div class="back-link">
                <a href="index.php">← Inicio</a>
            </div>

            <div class="logo">
                <h1>FastContact</h1>
                <span>ACCESO AL SISTEMA</span>
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
                </div>

                <button type="submit" class="btn-primary">
                    <span>Entrar al panel</span>
                    <span style="font-size: 16px;">➡️</span>
                </button>
            </form>

            <div class="extra-links">
                ¿Aún no tienes cuenta?<br>
                <a href="registro_cliente.php">Registro Cliente</a> &nbsp;·&nbsp; 
                <a href="solicitud_proveedor.php">Registro Proveedor</a>
            </div>
        </div>
    </div>
</body>
</html>