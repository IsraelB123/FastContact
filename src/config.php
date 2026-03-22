<?php
// En Docker, el "host" es el nombre del servicio en el docker-compose.yml
$host = "db"; 
$user = "user_fastcontact";
$password = "password_seguro";
$database = "fc_database";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>