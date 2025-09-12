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
$mensaje_bienvenida = "Editar Campaña";

$feedback_mensaje = "";
$campana = null; // Variable para almacenar los datos de la campaña

// Verificar si se recibió un ID de campaña en la URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $campana_id = $_GET['id'];

    try {
        // Consultar la campaña existente para precargar el formulario
        $stmt = $conn->prepare("SELECT id_campaña, fecha_campaña, lugar, titulo_campaña, descripcion_campaña, visible, fecha_expiracion FROM campañas WHERE id_campaña = ?");
        $stmt->execute([$campana_id]);
        $campana = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$campana) {
            $feedback_mensaje = "Error: Campaña no encontrada.";
            
            // Puedes redirigir a una página de error o a la lista de campañas si el ID no es válido
            // header('Location: dashboard.php');
            // exit;
        }

    } catch (PDOException $e) {
        error_log("Error al obtener campaña para editar: " . $e->getMessage());
        $feedback_mensaje = "Error al cargar la campaña. Por favor, intente de nuevo más tarde.";
    }
} else {
    $feedback_mensaje = "Error: ID de campaña no proporcionado.";
    // Redirige si no hay ID o no es numérico
    // header('Location: dashboard.php');
    // exit;
}


// --- Lógica para procesar el formulario cuando se envía (UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $campana) { // Solo procesa si hay una campaña válida
    $campana_id_post = $_POST['campana_id'] ?? ''; // Recuperar el ID del campo oculto
    $fecha_campana = $_POST['fecha_campana'] ?? '';
    $lugar = trim($_POST['lugar'] ?? '');
    $titulo_campana = trim($_POST['titulo_campana'] ?? '');
    $descripcion_campana = trim($_POST['descripcion_campana'] ?? '');

    // 1. Lógica para 'eliminar_automatico' (afecta solo 'fecha_expiracion')
    $eliminar_automatico_marcado = isset($_POST['eliminar_automatico']);
    $fecha_expiracion = null; // Por defecto no hay fecha de expiración

    if ($eliminar_automatico_marcado) {
        // Si se marca "Eliminar automáticamente", calcula la fecha de expiración
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+2 weeks'));
    }
    // Si no está marcado, $fecha_expiracion se queda en null, lo que desactiva la eliminación automática.

    // 2. Lógica para 'marcar_no_visible' (afecta solo 'visible')
    $marcar_no_visible_marcado = isset($_POST['marcar_no_visible']);
    $visible = $marcar_no_visible_marcado ? 0 : 1; // 0 si está marcada, 1 si no está marcada

    // Asegurarse de que el ID del POST coincida con el ID cargado
    if ($campana_id_post != $campana['id_campaña']) {
        $feedback_mensaje = "Error de seguridad: ID de campaña no coincide.";
    } elseif (empty($fecha_campana) || empty($lugar) || empty($titulo_campana) || empty($descripcion_campana)) {
        $feedback_mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        try {
            // Prepara la consulta SQL para ACTUALIZAR los datos
            // Ambas columnas (visible y fecha_expiracion) se actualizan de forma independiente
            $stmt = $conn->prepare("UPDATE campañas SET fecha_campaña = ?, lugar = ?, titulo_campaña = ?, descripcion_campaña = ?, visible = ?, fecha_expiracion = ? WHERE id_campaña = ?");

            // Ejecuta la consulta con los valores proporcionados
            $stmt->execute([$fecha_campana, $lugar, $titulo_campana, $descripcion_campana, $visible, $fecha_expiracion, $campana['id_campaña']]);

            $feedback_mensaje = "¡Campaña actualizada exitosamente!";

            // Opcional: Recargar los datos de la campaña después de la actualización para reflejar cambios
            $stmt = $conn->prepare("SELECT id_campaña, fecha_campaña, lugar, titulo_campaña, descripcion_campaña, visible, fecha_expiracion FROM campañas WHERE id_campaña = ?");
            $stmt->execute([$campana_id]);
            $campana = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al actualizar campaña: " . $e->getMessage());
            $feedback_mensaje = "Error al actualizar la campaña. Por favor, intente de nuevo más tarde.";
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
    <title>Editar Campaña</title>
    <link rel="stylesheet" href="../assets/styles/campañas/editar_campaña_individual.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="editar_campana">Editar <?php echo htmlspecialchars($campana['titulo_campaña']); ?></h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo strpos($feedback_mensaje, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <?php if ($campana): ?>
                <form action="editar_campaña_individual.php?id=<?php echo htmlspecialchars($campana['id_campaña']); ?>" method="POST">
                    <input type="hidden" name="campana_id" value="<?php echo htmlspecialchars($campana['id_campaña']); ?>">

                    <div class="form-group">
                        <h2>Editar fecha de la Campaña</h2>
                        <input type="date"
                               id="fecha_campana"
                               name="fecha_campana"
                               value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($campana['fecha_campaña']))); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <h2>Editar lugar de la Campaña</h2>
                        <input type="text"
                               id="lugar"
                               name="lugar"
                               placeholder="Escribe el lugar donde se realizará la campaña"
                               value="<?php echo htmlspecialchars($campana['lugar']); ?>"
                               required
                               maxlength="255">
                    </div>

                    <div class="form-group">
                        <h2>Editar título de la Campaña</h2>
                        <input type="text"
                               id="titulo_campana"
                               name="titulo_campana"
                               placeholder="Escribe un título para la campaña"
                               value="<?php echo htmlspecialchars($campana['titulo_campaña']); ?>"
                               required
                               maxlength="255">
                    </div>

                    <div class="form-group">
                        <h2>Editar descripción de la Campaña</h2>
                        <textarea id="descripcion_campana"
                                  name="descripcion_campana"
                                  rows="5"
                                  placeholder="Describe los detalles importantes de la campaña..."
                                  required><?php echo htmlspecialchars($campana['descripcion_campaña']); ?></textarea>
                    </div>

                    <div class="checkbox-container">
                        <label class="radio-option" id="option-eliminar-automatico" for="eliminar_automatico">
                            <input type="checkbox"
                                id="eliminar_automatico"
                                name="eliminar_automatico"
                                class="custom-radio"
                                <?php echo ($campana['fecha_expiracion'] !== null) ? 'checked' : ''; ?>>
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
                                <?php echo ($campana['visible'] == 0) ? 'checked' : ''; ?>>
                            <span class="check">
                                <svg width="22px" height="22px" viewBox="0 0 18 18">
                                    <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                    <polyline points="1 9 7 14 15 4"></polyline>
                                </svg>
                            </span>
                            <span>Eliminar campaña</span>
                        </label>
                    </div>

                    <button type="submit">
                        Actualizar Campaña
                    </button>
                </form>
            <?php else: ?>
                <p class="error-message">No se pudo cargar la campaña para editar. Verifique el ID.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) { // Asegurarse de que el formulario exista
                const inputs = form.querySelectorAll('input, textarea');
                const eliminarAutomaticoCheckbox = document.getElementById('eliminar_automatico');
                const marcarNoVisibleCheckbox = document.getElementById('marcar_no_visible');

                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                });

                // Función de validación de campos
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

                const tituloInput = document.getElementById('titulo_campana');
                const lugarInput = document.getElementById('lugar');
                
                if (tituloInput) {
                    const maxLength = tituloInput.getAttribute('maxlength');
                    tituloInput.addEventListener('input', function() {
                        const remaining = maxLength - this.value.length;
                    });
                }

                if (lugarInput) {
                    const maxLength = lugarInput.getAttribute('maxlength');
                    lugarInput.addEventListener('input', function() {
                        const remaining = maxLength - this.value.length;
                    });
                }
            }
        });
    </script>
</body>
</html>