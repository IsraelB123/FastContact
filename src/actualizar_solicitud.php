<?php
session_start();
require_once "config.php";

// Verificar que sea proveedor
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

$proveedorId = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $solicitudId = isset($_POST['solicitud_id']) ? (int)$_POST['solicitud_id'] : 0;
    $accion      = $_POST['accion'] ?? '';

    if ($solicitudId <= 0 || ($accion !== 'aceptar' && $accion !== 'rechazar')) {
        header("Location: panel_proveedor.php");
        exit;
    }

    // Validar que la solicitud pertenece a este proveedor
    $sqlCheck = "SELECT id, proveedor_id FROM contact_requests WHERE id = ?";
    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param("i", $solicitudId);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitud = $result->fetch_assoc();
    $stmt->close();

    if (!$solicitud || (int)$solicitud['proveedor_id'] !== $proveedorId) {
        header("Location: panel_proveedor.php");
        exit;
    }

    // Nuevo estado según la acción
    if ($accion === 'aceptar') {
        $nuevoEstado = 'aprobado';   // <<< aquí cambiamos
    } else { // rechazar
        $nuevoEstado = 'rechazado';  // <<< y aquí
    }

    $sqlUpdate = "UPDATE contact_requests
                  SET estado = ?
                  WHERE id = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("si", $nuevoEstado, $solicitudId);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: panel_proveedor.php");
exit;
