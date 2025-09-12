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
$mensaje_bienvenida = "Anuncios";

$feedback_mensaje = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanear los datos del formulario
    $fecha_anuncio = $_POST['fecha_anuncio'] ?? '';
    $titulo_anuncio = trim($_POST['titulo_anuncio'] ?? '');
    $descripcion_anuncio = trim($_POST['descripcion_anuncio'] ?? '');
    $eliminar_automatico = isset($_POST['eliminar_automatico']) ? 0 : 1; // 0 si se marca, 1 si no

    if (empty($fecha_anuncio) || empty($titulo_anuncio) || empty($descripcion_anuncio)) {
        $feedback_mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        try {
            // Calcula la fecha de expiración solo si se marcó "Eliminar automático"
            $fecha_expiracion = null;
            if ($eliminar_automatico === 0) {
                $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+2 weeks'));
            }

            $stmt = $conn->prepare("INSERT INTO anuncios (fecha_anuncio, titulo_anuncio, descripcion_anuncio, fecha_expiracion) VALUES (?, ?, ?, ?)");

            // Ejecuta la consulta con los valores proporcionados
            $stmt->execute([$fecha_anuncio, $titulo_anuncio, $descripcion_anuncio, $fecha_expiracion]);

            $feedback_mensaje = "¡Anuncio creado exitosamente!";

            // Opcional: Limpiar los campos del formulario después del envío exitoso
            $fecha_anuncio = '';
            $titulo_anuncio = '';
            $descripcion_anuncio = '';

        } catch (PDOException $e) {
            // Registra el error para depuración
            error_log("Error al crear anuncio: " . $e->getMessage());
            $feedback_mensaje = "Error al crear el anuncio. Por favor, intente de nuevo más tarde.";
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
    <title>Agregar Anuncio</title>
    <link rel="stylesheet" href="../assets/styles/anuncios_eventos/agregar_anuncios.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="agregar_anuncio">Agregar anuncio</h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo strpos($feedback_mensaje, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Contenedor del formulario -->
        <div class="form-container">
            <form action="agregar_anuncios.php" method="POST">
                
                <div class="form-group">
                    <h2>Fecha del Anuncio</h2>
                    <input type="date" 
                           id="fecha_anuncio" 
                           name="fecha_anuncio" 
                           value="<?php echo htmlspecialchars($fecha_anuncio ?? date('Y-m-d')); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <h2>Título del Anuncio</h2>
                    <input type="text" 
                           id="titulo_anuncio" 
                           name="titulo_anuncio" 
                           placeholder="Escribe un título para el anuncio" 
                           value="<?php echo htmlspecialchars($titulo_anuncio ?? ''); ?>"
                           required 
                           maxlength="255">
                </div>

                <div class="form-group">
                    <h2>Descripción del Anuncio</h2>
                    <textarea id="descripcion_anuncio" 
                              name="descripcion_anuncio" 
                              rows="5" 
                              placeholder="Describe los detalles del anuncio..." 
                              required><?php echo htmlspecialchars($descripcion_anuncio ?? ''); ?></textarea>
                </div>

                <div class="checkbox-container">
                    <label class="radio-option" id="option-eliminar-automatico" for="eliminar_automatico">
                        <input type="checkbox" 
                            id="eliminar_automatico" 
                            name="eliminar_automatico"
                            class="custom-radio"
                            <?php echo (isset($_POST['eliminar_automatico']) || (!isset($_POST['eliminar_automatico']) && empty($_POST))) ? 'checked' : ''; ?>>
                        <span class="check">
                            <svg width="22px" height="22px" viewBox="0 0 18 18">
                                <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                <polyline points="1 9 7 14 15 4"></polyline>
                            </svg>
                        </span>
                        <span>Eliminar automáticamente</span>
                    </label>
                </div>


                <button type="submit">
                    Crear Anuncio
                </button>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });
            
            function validateField(field) {
                const formGroup = field.closest('.form-group');
                if (!formGroup) return;
                
                formGroup.classList.remove('error', 'success');
                
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
        
        const tituloInput = document.getElementById('titulo_anuncio');
        const maxLength = tituloInput.getAttribute('maxlength');
        
        tituloInput.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
        });
    </script>
</body>
</html>