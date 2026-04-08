<?php
// En Docker, el "host" es el nombre del servicio en el docker-compose.yml
$host = "fc_db"; 
$user = "user_fastcontact";
$password = "password_seguro";
$database = "fc_database";

mysqli_report(MYSQLI_REPORT_OFF); // Evita errores fatales automáticos
$conn = @new mysqli($host, $user, $pass, $db);

if (!$conn) {
    die("Lo sentimos, el sistema de inventario está en mantenimiento temporal.");
}
?>