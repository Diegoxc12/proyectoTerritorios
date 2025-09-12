<?php
session_start(); 

// Establece la zona horaria a Cuenca, Ecuador
date_default_timezone_set('America/Guayaquil');

// Incluye tu archivo de conexión a la base de datos
include('../includes/conexion.php');

// Redirige si el usuario no está logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$rol_usuario = $_SESSION['rol_usuario'];
// Cambiamos el mensaje de bienvenida
$mensaje_bienvenida = "Agregar Hermanos";

$feedback_mensaje = ""; 

// --- Lógica para procesar el formulario cuando se envía ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanear los datos del formulario para nombre, segundo nombre, apellido y segundo apellido
    $nombre_hermano = trim($_POST['nombre_hermano'] ?? '');
    $segundo_nombre_hermano = trim($_POST['segundo_nombre_hermano'] ?? ''); // Nuevo campo
    $apellido_hermano = trim($_POST['apellido_hermano'] ?? '');
    $segundo_apellido_hermano = trim($_POST['segundo_apellido_hermano'] ?? ''); // Nuevo campo

    // Validaciones básicas (solo nombre y apellido son obligatorios)
    if (empty($nombre_hermano) || empty($apellido_hermano)) {
        $feedback_mensaje = "Error: El nombre y el apellido son obligatorios.";
    } else {
        try {
            // Prepara la consulta SQL para insertar los datos en la tabla 'hermanos'
            // Asegúrate de que tu tabla 'hermanos' tiene las columnas 'nombre', 'segundo_nombre', 'apellido', 'segundo_apellido'
            $stmt = $conn->prepare("INSERT INTO hermanos (nombre, segundo_nombre, apellido, segundo_apellido) VALUES (?, ?, ?, ?)");

            // Ejecuta la consulta con los valores proporcionados
            $stmt->execute([$nombre_hermano, $segundo_nombre_hermano, $apellido_hermano, $segundo_apellido_hermano]);

            $feedback_mensaje = "¡Hermano añadido exitosamente!";

            // Opcional: Limpiar los campos del formulario después del envío exitoso
            $nombre_hermano = '';
            $segundo_nombre_hermano = '';
            $apellido_hermano = '';
            $segundo_apellido_hermano = '';

        } catch (PDOException $e) {
            // Registra el error para depuración
            error_log("Error al añadir hermano: " . $e->getMessage());
            $feedback_mensaje = "Error al añadir el hermano. Por favor, intente de nuevo más tarde." . $e->getMessage();
        }
    }
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
    <title>Agregar Hermano</title>
    <link rel="stylesheet" href="../assets/styles/usuarios/agregar_hermanos.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="agregar_hermano"><?php echo htmlspecialchars($mensaje_bienvenida); ?></h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo strpos($feedback_mensaje, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="agregar_hermanos.php" method="POST">
                
                <div class="form-group">
                    <h2>Primer Nombre</h2>
                    <input type="text" 
                           id="nombre_hermano" 
                           name="nombre_hermano" 
                           placeholder="Ingresa el primer nombre" 
                           value="<?php echo htmlspecialchars($nombre_hermano ?? ''); ?>"
                           required 
                           maxlength="100">
                </div>

                <div class="form-group">
                    <h2>Segundo Nombre (Opcional)</h2>
                    <input type="text" 
                           id="segundo_nombre_hermano" 
                           name="segundo_nombre_hermano" 
                           placeholder="Ingresa el segundo nombre" 
                           value="<?php echo htmlspecialchars($segundo_nombre_hermano ?? ''); ?>"
                           maxlength="100">
                </div>

                <div class="form-group">
                    <h2>Primer Apellido</h2>
                    <input type="text" 
                           id="apellido_hermano" 
                           name="apellido_hermano" 
                           placeholder="Ingresa el primer apellido" 
                           value="<?php echo htmlspecialchars($apellido_hermano ?? ''); ?>"
                           required 
                           maxlength="100">
                </div>

                <div class="form-group">
                    <h2>Segundo Apellido (Opcional)</h2>
                    <input type="text" 
                           id="segundo_apellido_hermano" 
                           name="segundo_apellido_hermano" 
                           placeholder="Ingresa el segundo apellido" 
                           value="<?php echo htmlspecialchars($segundo_apellido_hermano ?? ''); ?>"
                           maxlength="100">
                </div>

                <button type="submit">
                    Añadir Hermano
                </button>
            </form>
        </div>
    </main>

    <script>
        // Validación en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input'); // Selecciona todos los inputs

            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });
            
            function validateField(field) {
                const formGroup = field.closest('.form-group');
                if (!formGroup) return;
                
                formGroup.classList.remove('error', 'success');
                
                // Solo validamos los campos obligatorios ('nombre_hermano' y 'apellido_hermano')
                if (field.hasAttribute('required') && !field.value.trim()) {
                    formGroup.classList.add('error');
                } else if (field.value.trim()) {
                    formGroup.classList.add('success');
                }
            }
            
            // Smooth scroll para pantallas pequeñas
            if (window.innerWidth <= 768) {
                form.addEventListener('submit', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>
</body>
</html>