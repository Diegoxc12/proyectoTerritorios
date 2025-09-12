<?php
session_start(); 

date_default_timezone_set('America/Guayaquil');

// Incluye tu archivo de conexión a la base de datos
include('../includes/conexion.php');

// Redirige si el usuario no está logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$rol_usuario = $_SESSION['rol_usuario'];
$mensaje_bienvenida = "Listado de Hermanos";

$hermanos = [];
$feedback_mensaje = "";

try {
    // Prepara la consulta para obtener todos los hermanos
    $stmt = $conn->prepare("SELECT nombre, segundo_nombre, apellido, segundo_apellido FROM hermanos ORDER BY apellido, nombre");
    $stmt->execute();
    $hermanos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al obtener hermanos: " . $e->getMessage());
    $feedback_mensaje = "Error al cargar el listado de hermanos. Por favor, intente de nuevo más tarde.";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/styles/agregar_anuncio.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Ver Hermanos</title>
   <link rel="stylesheet" href="../assets/styles/usuarios/ver_hermanos.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="ver_hermanos_titulo"><?php echo htmlspecialchars($mensaje_bienvenida); ?></h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo strpos($feedback_mensaje, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($hermanos)): ?>
                <p style="text-align: center; color: var(--color-text-light);">No hay hermanos registrados aún.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Segundo Nombre</th>
                            <th>Apellido</th>
                            <th>Segundo Apellido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hermanos as $hermano): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hermano['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($hermano['segundo_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($hermano['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($hermano['segundo_apellido']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>