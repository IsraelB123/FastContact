<?php
// index.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FastContact – Inicio</title>
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
        }
        .logo {
            text-align: center;
            margin-bottom: 16px;
        }
        .logo h1 {
            margin: 0;
            font-size: 32px;
            letter-spacing: 0.5px;
        }
        .logo span {
            font-size: 12px;
            opacity: 0.85;
        }
        .subtitle {
            font-size: 14px;
            text-align: center;
            margin-top: 8px;
            color: #f5f5f5;
        }
        .actions {
            margin-top: 22px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px 18px;
            border-radius: 999px;
            border: none;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.15s ease, background 0.2s ease, border-color 0.2s;
            white-space: nowrap;
        }
        .btn-primary {
            background: #ff7f32;
            color: #1b1b1b;
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
        }
        .btn-primary:hover {
            background: #ff954f;
            transform: translateY(-1px);
        }
        .btn-outline {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .btn-outline:hover {
            background: rgba(0,0,0,0.25);
            transform: translateY(-1px);
        }
        .btn span.icon {
            font-size: 16px;
        }
        .footer {
            margin-top: 16px;
            font-size: 11px;
            text-align: center;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>FastContact</h1>
                <span>Comunicación directa cliente–proveedor</span>
            </div>

            <p class="subtitle">
                Plataforma para que supermercados, abarrotes y tiendas de conveniencia
                contacten de forma rápida a sus proveedores.
            </p>

            <div class="actions">
                <a href="login.php" class="btn btn-primary">
                    <span class="icon">🔐</span>
                    <span>Iniciar sesión</span>
                </a>
                <a href="lista_proveedores.php" class="btn btn-outline">
                    <span class="icon">📋</span>
                    <span>Ver proveedores registrados</span>
                </a>
            </div>

            <div class="footer">
                FastContact · Prototipo para mejorar la comunicación entre tiendas y proveedores.
            </div>
        </div>
    </div>
</body>
</html>
