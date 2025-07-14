<?php
$host = 'localhost';
$dbname = 'totoranorte_db';
$user = 'root';
$pass = ''; // PON AQUÍ LA CONTRASEÑA SI TU MYSQL LA USA

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
?>
