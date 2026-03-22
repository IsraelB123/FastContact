<?php
require_once "config.php";
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $empresa = trim($_POST['empresa'] ?? '');

    // 1. Verificar si el correo ya existe para evitar duplicados
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $mensaje = "Este correo ya tiene una solicitud en proceso.";
        $tipo_mensaje = "error";
    } else {
        $conn->begin_transaction();
        try {
            // 2. Insertar como PENDIENTE
            $stmt = $conn->prepare("INSERT INTO users (nombre, email, password_hash, rol, estado) VALUES (?, ?, ?, 'proveedor', 'pendiente')");
            $stmt->bind_param("sss", $nombre, $email, $password); 
            $stmt->execute();
            $userId = $conn->insert_id;

            $stmtProv = $conn->prepare("INSERT INTO provider_profiles (user_id, nombre_empresa, disponibilidad) VALUES (?, ?, 'no_disponible')");
            $stmtProv->bind_param("is", $userId, $empresa);
            $stmtProv->execute();

            $conn->commit();
            $mensaje = "¡Solicitud enviada! Nuestro equipo revisará tus datos y te notificará por correo cuando tu cuenta sea activada.";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}
?>