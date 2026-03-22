<?php
// config.php ajustado para Docker
$host = "db";                 // "db" es el nombre del servicio en docker-compose
$user = "user_fastcontact";   // Definido en MYSQL_USER
$pass = "password_seguro";    // Definido en MYSQL_PASSWORD
$dbname = "fc_database";      // Definido en MYSQL_DATABASE

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>