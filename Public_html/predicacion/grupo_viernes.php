<?php
session_start();
date_default_timezone_set('America/Guayaquil');

include('../includes/conexion.php'); 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$rol_usuario = $_SESSION['rol_usuario'];
$mensaje_bienvenida = "Grupo del viernes";

$feedback_mensaje = "";
$feedback_tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['eliminar_id'])) {
        $id_eliminar = intval($_POST['eliminar_id']);
        try {
            $stmt_eliminar = $conn->prepare("UPDATE territorio_viernes SET visible = 0 WHERE id_territorio = ?");
            $stmt_eliminar->execute([$id_eliminar]);
            echo json_encode(['mensaje' => 'Territorio eliminado exitosamente', 'tipo' => 'success']);
        } catch (PDOException $e) {
            error_log("Error al eliminar territorio: " . $e->getMessage());
            echo json_encode(['mensaje' => 'Error al eliminar territorio: ' . $e->getMessage(), 'tipo' => 'error']);
        }
        exit();
    }

    if (isset($_POST['territorios']) && is_array($_POST['territorios'])) {
        $territorios_a_insertar = [];
        $errores_en_territorios = false;

        foreach ($_POST['territorios'] as $index => $territorio_data) {
            $numero_territorio_str = trim($territorio_data['nombre_territorio'] ?? ''); 
            $fecha_expiracion_str = trim($territorio_data['fecha_expiracion'] ?? '');
            $tipo_territorio_str = trim($territorio_data['tipo_territorio'] ?? '');

            if (empty($numero_territorio_str)) {
                $feedback_mensaje = "Error: El territorio #" . ($index + 1) . " no puede estar vacío.";
                $feedback_tipo = "error";
                $errores_en_territorios = true;
                break;
            }

            if (filter_var($numero_territorio_str, FILTER_VALIDATE_INT) !== false) {
                if (empty($fecha_expiracion_str)) {
                    $feedback_mensaje = "Error: La fecha de expiración para el territorio #" . ($index + 1) . " no puede estar vacía.";
                    $feedback_tipo = "error";
                    $errores_en_territorios = true;
                    break;
                }
                $fecha_expiracion_obj = DateTime::createFromFormat('Y-m-d', $fecha_expiracion_str);
                if (!$fecha_expiracion_obj || $fecha_expiracion_obj->format('Y-m-d') !== $fecha_expiracion_str) {
                    $feedback_mensaje = "Error: La fecha de expiración para el territorio #" . ($index + 1) . " no tiene un formato de fecha válido (YYYY-MM-DD).";
                    $feedback_tipo = "error";
                    $errores_en_territorios = true;
                    break;
                }
                $fecha_expiracion_con_hora = $fecha_expiracion_str . ' 23:59:59';

                $territorios_a_insertar[] = [
                    'numero' => (int)$numero_territorio_str,
                    'fecha_expiracion' => $fecha_expiracion_con_hora,
                    'tipo' => htmlspecialchars($tipo_territorio_str, ENT_QUOTES, 'UTF-8')
                ];
            } else {
                $feedback_mensaje = "Error: El territorio #" . ($index + 1) . " debe ser un número entero válido.";
                $feedback_tipo = "error";
                $errores_en_territorios = true;
                break;
            }
        }

        if (!$errores_en_territorios && !empty($territorios_a_insertar)) {
            try {
                $conn->beginTransaction();
                $fecha_asignacion = date('Y-m-d H:i:s');

                $stmt_territorio = $conn->prepare("INSERT INTO territorio_viernes (territorios_asignado, fecha_asignacion, fecha_expiracion_territorio, tipo, visible) VALUES (?, ?, ?, ?, 1)");

                foreach ($territorios_a_insertar as $territorio) {
                    $stmt_territorio->execute([
                        $territorio['numero'],
                        $fecha_asignacion,
                        $territorio['fecha_expiracion'],
                        $territorio['tipo']
                    ]);
                }

                $conn->commit();
                $feedback_mensaje = "¡Territorios añadidos exitosamente!";
                $feedback_tipo = "success";
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Error al añadir territorios: " . $e->getMessage());
                $feedback_mensaje = "Error al añadir los territorios. Por favor, intente de nuevo más tarde. (Detalle: " . $e->getMessage() . ")";
                $feedback_tipo = "error";
            }
        } else if (empty($territorios_a_insertar) && !$errores_en_territorios) {
            $feedback_mensaje = "No se recibieron territorios para añadir.";
            $feedback_tipo = "info";
        }
    } else {
        $feedback_mensaje = "Error: No se recibieron datos de territorios.";
        $feedback_tipo = "error";
    }

    echo json_encode(['mensaje' => $feedback_mensaje, 'tipo' => $feedback_tipo]);
    exit();
}

$feedback_mensaje = "";
$feedback_tipo = "";

// Obtener los territorios asignados
$territorios_asignados = [];
try {
    $stmt_fetch_territorios = $conn->prepare("SELECT id_territorio, territorios_asignado, tipo FROM territorio_viernes WHERE fecha_expiracion_territorio > NOW() AND visible = 1 ORDER BY territorios_asignado ASC;");
    $stmt_fetch_territorios->execute();
    $territorios_asignados = $stmt_fetch_territorios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al obtener territorios asignados para la página: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Viernes</title>
    <script src="../assets/js/script_grupos_predicacion.js"></script>
    <link rel="stylesheet" href="../assets/styles/grupos_predicacion.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

     <main>
        <h2 id="agregar_campana_titulo">Grupo de la mañana</h2>
        <div class="form-container">
            <form id="territorioFormManana" action="grupo_viernes.php" method="POST">
                <div class="form-group">
                    <h2>Hora: 8:30 AM</h2>
                </div>

                <div class="form-group">
                    <h2>Conductor: Diego Jr</h2>
                </div>

                <div class="form-group">
                    <h2>Lugar: Casa Adriana Jara</h2>
                </div>

                <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
                <div class="mensaje-importante">
                    <p>Para asignar territorios para la mañana<span> no escriba nada en el campo </span><span> "Tipo de territorio"</span></p>
                </div>
                
                <?php endif; ?>

                <hr class="section-divider">
                <h2 class="section-header">Territorios para el Viernes</h2>
                
                <div class="territorio-button-container">
                    <?php
                    if (!empty($territorios_asignados)) {
                        foreach ($territorios_asignados as $territorio) {

                            if (isset($territorio['tipo']) && $territorio['tipo'] === '') {
                                $numero_territorio = htmlspecialchars($territorio['territorios_asignado']);
                                $id_territorio = htmlspecialchars($territorio['id_territorio']);
                                
                                echo '<div style="display: flex; gap: 10px; align-items: center;">';

                                echo '<button type="button" class="btn-grupos" data-aos="fade-up" data-id-imagen="' . $numero_territorio . '">' . $numero_territorio . '</button>';

                                if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano') {
                                    echo '<button type="button" class="btn-eliminar" data-id="' . $id_territorio . '">Borrar</button>';
                                }

                                echo '</div>';
                            }
                        }
                        
                    } else {
                        echo '<p style="text-align: center;">No hay territorios asignados</p>';
                    }
                    ?>
                </div>

                <div class="feedback-area-territorios"></div>
                
                <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
                    <div class="territorios-container"></div>
                    <div class="message-container"></div>
                    <button type="button" class="btn-secondary add-territorio-btn">Agregar Territorio</button>
                    <button type="submit" class="btn-primary submit-territorios-btn">Añadir Territorios</button>
                <?php endif; ?>
            </form>
        </div>

        <h2 id="agregar_campana_titulo">Grupo de la tarde</h2>
        <div class="form-container">
            <form id="territorioFormNoche" action="grupo_viernes.php" method="POST">
                <div class="form-group">
                    <h2>Hora: 3:00 PM</h2>
                </div>

                <div class="form-group">
                    <h2>Conductor: Ruth Cardenas</h2>
                </div>

                <div class="form-group">
                    <h2>Lugar: Zoom</h2>
                </div>

                <div class="form-group">
                    <a href="https://zoom.us/es/join">Unirse</a>
                </div>
            </form>
        </div>

    </main>
</body>
</html>