<?php
// index.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FastContact – Inicio</title>
    <style>
        * { box-sizing: border-box; transition: all 0.3s ease; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
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
            padding: 35px 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            text-align: center;
        }
        .logo {
            margin-bottom: 20px;
        }
        .logo h1 {
            margin: 0;
            font-size: 36px;
            letter-spacing: 0.5px;
            color: #f8fafc;
            font-weight: 800;
        }
        .logo span {
            font-size: 13px;
            color: #38bdf8; /* Azul Cian */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-top: 5px;
        }
        .subtitle {
            font-size: 15px;
            line-height: 1.5;
            margin-top: 15px;
            color: #cbd5e1;
        }
        .actions {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            border-radius: 14px;
            border: none;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
        }
        .btn-primary {
            background: #38bdf8;
            color: #0f172a;
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.2);
        }
        .btn-primary:hover {
            background: #7dd3fc;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(56, 189, 248, 0.3);
        }
        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #38bdf8;
            color: #38bdf8;
            transform: translateY(-2px);
        }
        .footer {
            margin-top: 25px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>FastContact</h1>
                <span>Conexión B2B Directa</span>
            </div>

            <p class="subtitle">
                La plataforma inteligente que conecta a supermercados y tiendas de conveniencia 
                con sus proveedores clave en tiempo real.
            </p>

            <div class="actions">
                <a href="login.php" class="btn btn-primary">
                    <span>Entrar a la plataforma</span>
                    <span>➡️</span>
                </a>
                <a href="lista_proveedores.php" class="btn btn-outline">
                    <span>📋</span>
                    <span>Ver directorio público</span>
                </a>
            </div>

            <div class="footer">
                FastContact © 2026<br>
                Sistema de gestión de inventarios y pedidos.
            </div>
        </div>
    </div>
</body>
</html>