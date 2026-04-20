<?php
session_save_path('/tmp'); 
session_start();
require_once "config.php";

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'proveedor') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: panel_proveedor.php");
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$nuevoEstado = $_POST['nuevo_estado'] ?? '';

$estadosPermitidos = ['pendiente', 'en_proceso', 'completado', 'cancelado'];

if ($orderId <= 0 || !in_array($nuevoEstado, $estadosPermitidos)) {
    die("Datos inválidos.");
}

// Validar que el pedido pertenezca al proveedor logueado
$proveedorUserId = $_SESSION['user_id'];

$sqlProveedor = "SELECT id FROM provider_profiles WHERE user_id = ?";
$stmtProv = $conn->prepare($sqlProveedor);
$stmtProv->bind_param("i", $proveedorUserId);
$stmtProv->execute();
$resultProv = $stmtProv->get_result();
$proveedorPerfil = $resultProv->fetch_assoc();
$stmtProv->close();

if (!$proveedorPerfil) {
    die("No se encontró el perfil del proveedor.");
}

$proveedorPerfilId = $proveedorPerfil['id'];

// Actualizar solo si el pedido es de este proveedor
$sqlUpdate = "UPDATE orders SET estado = ? WHERE id = ? AND proveedor_id = ?";
$stmtUp = $conn->prepare($sqlUpdate);
$stmtUp->bind_param("sii", $nuevoEstado, $orderId, $proveedorPerfilId);

if ($stmtUp->execute()) {
    $stmtUp->close();
    // Regresar al detalle del pedido
    header("Location: detalle_pedido.php?id=".$orderId);
    exit;
} else {
    $stmtUp->close();
    die("Error al actualizar el estado del pedido.");
}
