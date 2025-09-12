<?php
session_start();

// Incluye tu archivo de conexión a la base de datos
include('../includes/conexion.php');

// Redirige si el usuario no está logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$rol_usuario = $_SESSION['rol_usuario'];
$mensaje_bienvenida = "Editar Evento"; // Cambiado para reflejar la acción

$feedback_mensaje = "";
$evento = null; 

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $evento_id = $_GET['id'];

    try {
        // Consultar el evento existente para precargar el formulario
        // Asegúrate de seleccionar también 'visible' y 'fecha_expiracion' de la tabla 'eventos'
        $stmt = $conn->prepare("SELECT id, fecha_evento, titulo_evento, descripcion_evento, visible, fecha_expiracion FROM eventos WHERE id = ?");
        $stmt->execute([$evento_id]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evento) {
            $feedback_mensaje = "Error: Evento no encontrado.";
            // Puedes redirigir a una página de error o a la lista de eventos si el ID no es válido
            // header('Location: dashboard.php');
            // exit;
        }

    } catch (PDOException $e) {
        error_log("Error al obtener evento para editar: " . $e->getMessage());
        $feedback_mensaje = "Error al cargar el evento. Por favor, intente de nuevo más tarde.";
    }
} else {
    $feedback_mensaje = "Error: ID de evento no proporcionado.";
    // Redirige si no hay ID o no es numérico
    // header('Location: dashboard.php');
    // exit;
}


// --- Lógica para procesar el formulario cuando se envía (UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $evento) { // Solo procesa si hay un evento válido
    $evento_id_post = $_POST['evento_id'] ?? ''; // Recuperar el ID del campo oculto
    $fecha_evento = $_POST['fecha_evento'] ?? '';
    $titulo_evento = trim($_POST['titulo_evento'] ?? '');
    $descripcion_evento = trim($_POST['descripcion_evento'] ?? '');

    // 1. Lógica para 'eliminar_automatico' (afecta solo 'fecha_expiracion')
    $eliminar_automatico_marcado = isset($_POST['eliminar_automatico']);
    $fecha_expiracion = null; // Por defecto no hay fecha de expiración

    if ($eliminar_automatico_marcado) {
        // Si se marca "Eliminar automáticamente", calcula la fecha de expiración (por ejemplo, 2 semanas después)
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+2 seconds'));
    }
    // Si no está marcado, $fecha_expiracion se queda en null, lo que desactiva la eliminación automática.

    // 2. Lógica para 'marcar_no_visible' (afecta solo 'visible')
    $marcar_no_visible_marcado = isset($_POST['marcar_no_visible']);
    $visible = $marcar_no_visible_marcado ? 0 : 1; // 0 si está marcada, 1 si no está marcada

    // Asegurarse de que el ID del POST coincida con el ID cargado
    if ($evento_id_post != $evento['id']) {
        $feedback_mensaje = "Error de seguridad: ID de evento no coincide.";
    } elseif (empty($fecha_evento) || empty($titulo_evento) || empty($descripcion_evento)) {
        $feedback_mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        try {
            // Prepara la consulta SQL para ACTUALIZAR los datos
            // Ambas columnas (visible y fecha_expiracion) se actualizan de forma independiente
            $stmt = $conn->prepare("UPDATE eventos SET fecha_evento = ?, titulo_evento = ?, descripcion_evento = ?, visible = ?, fecha_expiracion = ? WHERE id = ?");

            // Ejecuta la consulta con los valores proporcionados
            $stmt->execute([$fecha_evento, $titulo_evento, $descripcion_evento, $visible, $fecha_expiracion, $evento['id']]);

            $feedback_mensaje = "¡Evento actualizado exitosamente!";

            // Opcional: Recargar los datos del evento después de la actualización para reflejar cambios
            $stmt = $conn->prepare("SELECT id, fecha_evento, titulo_evento, descripcion_evento, visible, fecha_expiracion FROM eventos WHERE id = ?");
            $stmt->execute([$evento_id]);
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al actualizar evento: " . $e->getMessage());
            $feedback_mensaje = "Error al actualizar el evento. Por favor, intente de nuevo más tarde.";
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
    <title>Editar Evento</title>
    <link rel="stylesheet" href="../assets/styles/anuncios_eventos/editar_evento.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="agregar_anuncio">Editar evento: <?php echo htmlspecialchars($evento['titulo_evento']); ?></h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo strpos($feedback_mensaje, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <?php if ($evento): ?>
                <form action="editar_evento.php?id=<?php echo htmlspecialchars($evento['id']); ?>" method="POST">
                    <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($evento['id']); ?>">

                    <div class="form-group">
                        <h2>Editar fecha del Evento</h2>
                        <input type="date"
                               id="fecha_evento"
                               name="fecha_evento"
                               value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($evento['fecha_evento']))); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <h2>Editar título del Evento</h2>
                        <input type="text"
                               id="titulo_evento"
                               name="titulo_evento"
                               placeholder="Escribe un título para el evento"
                               value="<?php echo htmlspecialchars($evento['titulo_evento']); ?>"
                               required
                               maxlength="255">
                    </div>

                    <div class="form-group">
                        <h2>Editar descripción del Evento</h2>
                        <textarea id="descripcion_evento"
                                  name="descripcion_evento"
                                  rows="5"
                                  placeholder="Describe los detalles importantes del evento..."
                                  required><?php echo htmlspecialchars($evento['descripcion_evento']); ?></textarea>
                    </div>

                    <div class="checkbox-container">
                        <label class="radio-option" id="option-eliminar-automatico" for="eliminar_automatico">
                            <input type="checkbox"
                                id="eliminar_automatico"
                                name="eliminar_automatico"
                                class="custom-radio"
                                <?php echo ($evento['fecha_expiracion'] !== null) ? 'checked' : ''; ?>>
                            <span class="check">
                                <svg width="22px" height="22px" viewBox="0 0 18 18">
                                    <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                    <polyline points="1 9 7 14 15 4"></polyline>
                                </svg>
                            </span>
                            <span>Eliminar automáticamente</span>
                        </label>
                    </div>


                    <div class="checkbox-container">
                        <label class="radio-option" id="option-marcar-no-visible" for="marcar_no_visible">
                            <input type="checkbox"
                                id="marcar_no_visible"
                                name="marcar_no_visible"
                                class="custom-radio"
                                <?php echo ($evento['visible'] == 0) ? 'checked' : ''; ?>>
                            <span class="check">
                                <svg width="22px" height="22px" viewBox="0 0 18 18">
                                    <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                    <polyline points="1 9 7 14 15 4"></polyline>
                                </svg>
                            </span>
                            <span>Eliminar evento</span>
                        </label>
                    </div>


                    <button type="submit">
                        Actualizar Evento
                    </button>
                </form>
            <?php else: ?>
                <p class="error-message">No se pudo cargar el evento para editar. Verifique el ID.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Validación en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) { // Asegurarse de que el formulario exista
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

                // Contador de caracteres para el título
                const tituloInput = document.getElementById('titulo_evento');
                const maxLength = tituloInput ? tituloInput.getAttribute('maxlength') : null;

                if (tituloInput) {
                    tituloInput.addEventListener('input', function() {
                        const remaining = maxLength - this.value.length;
                        // Aquí puedes agregar un contador visual si lo deseas
                        // Por ejemplo: document.getElementById('contador_titulo').textContent = `Caracteres restantes: ${remaining}`;
                    });
                }
            }
        });
    </script>
</body>
</html>