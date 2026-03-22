<?php
session_start();
require_once "config.php";

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

$clienteId = $_SESSION['user_id'];
$clienteNombre = $_SESSION['user_name'] ?? 'Cliente';

// Validar que venga el id de proveedor perfil
$providerProfileId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($providerProfileId <= 0) {
    die("Proveedor no especificado.");
}

// Obtener datos del proveedor (perfil + usuario)
$sql = "SELECT p.id AS perfil_id, p.nombre_empresa, p.nombre_contacto, p.telefono_contacto,
               p.tipo_proveedor, u.id AS proveedor_user_id, u.nombre AS nombre_usuario
        FROM provider_profiles p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $providerProfileId);
$stmt->execute();
$result = $stmt->get_result();
$proveedor = $result->fetch_assoc();
$stmt->close();

if (!$proveedor) {
    die("Proveedor no encontrado.");
}

$mensajeExito = "";
$error = "";

// Procesar solicitud automática
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Asunto y mensaje generados automáticamente
    $asunto  = "Solicitud de alta como cliente";
    $mensaje = "El usuario '{$clienteNombre}' solicita ser dado de alta como cliente del proveedor '{$proveedor['nombre_empresa']}'."
             . " Esta solicitud fue generada automáticamente desde FastContact.";

    $sqlInsert = "INSERT INTO contact_requests (cliente_id, proveedor_id, asunto, mensaje)
                  VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param(
        "iiss",
        $clienteId,
        $proveedor['proveedor_user_id'],
        $asunto,
        $mensaje
    );
    if ($stmt->execute()) {
        $mensajeExito = "Tu solicitud para ser cliente de este proveedor ha sido enviada correctamente.";
    } else {
        $error = "Ocurrió un error al enviar tu solicitud. Inténtalo de nuevo.";
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar alta como cliente – FastContact</title>
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
            max-width: 520px;
            padding: 20px;
        }
        .card {
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(14px);
            border-radius: 18px;
            padding: 22px 22px 18px;
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
        h1 {
            margin: 0 0 4px;
            font-size: 20px;
        }
        .subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .provider-info {
            font-size: 12px;
            background: rgba(0,0,0,0.4);
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 14px;
        }
        .provider-info strong {
            font-weight: 600;
        }
        .msg {
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .msg-success {
            background: rgba(54,255,156,0.1);
            border: 1px solid rgba(54,255,156,0.7);
            color: #b8ffdf;
        }
        .msg-error {
            background: rgba(255, 87, 87, 0.1);
            border: 1px solid rgba(255, 120, 120, 0.8);
            color: #ffb3b3;
        }
        .btn-primary {
            padding: 10px 18px;
            border-radius: 999px;
            border: none;
            background: #ff7f32;
            color: #1b1b1b;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
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
        .note {
            font-size: 11px;
            opacity: 0.85;
            margin-top: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">

        <div class="back-link">
            <a href="panel_cliente.php">← Volver al panel del cliente</a>
        </div>

        <h1>Solicitar alta como cliente</h1>
        <p class="subtitle">
            Confirma si deseas enviar una solicitud para ser cliente de este proveedor.
        </p>

        <div class="provider-info">
            <div><strong>Proveedor:</strong> <?php echo htmlspecialchars($proveedor['nombre_empresa']); ?></div>
            <div><strong>Contacto:</strong> <?php echo htmlspecialchars($proveedor['nombre_contacto']); ?></div>
            <div><strong>Tipo de proveedor:</strong> <?php echo htmlspecialchars($proveedor['tipo_proveedor']); ?></div>
            <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($proveedor['telefono_contacto']); ?></div>
        </div>

        <?php if ($mensajeExito): ?>
            <div class="msg msg-success">
                <?php echo htmlspecialchars($mensajeExito); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg msg-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <button type="submit" class="btn-primary">
                <span class="icon">🤝</span>
                <span>Enviar solicitud al proveedor</span>
            </button>
        </form>

        <p class="note">
            El proveedor verá esta solicitud en su panel y podrá dar seguimiento para registrarte
            como cliente en sus sistemas.
        </p>

    </div>
</div>
</body>
</html>
