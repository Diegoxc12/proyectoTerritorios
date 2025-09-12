<?php
session_start();

date_default_timezone_set('America/Guayaquil');

include('../includes/conexion.php'); 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['obtener_detalles']) && isset($_GET['id_recuadro'])) {
    $id_recuadro = intval($_GET['id_recuadro']);
    
    try {
        $sql_detalles = "SELECT esta_casa, no_visitar, descripcion_casa, es_estudio, descripcion_estudio, id_propiedad, numero_propiedad FROM recuadros WHERE id_recuadro = :id_recuadro";
        $stmt_detalles = $conn->prepare($sql_detalles);
        $stmt_detalles->bindParam(':id_recuadro', $id_recuadro, PDO::PARAM_INT);
        $stmt_detalles->execute();
        $detalles = $stmt_detalles->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        if ($detalles) {
            echo json_encode(['success' => true, 'detalles' => $detalles]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontraron detalles']);
        }
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['obtener_detalles_unidad']) && isset($_GET['id_recuadro'])) {
    $id_recuadro = intval($_GET['id_recuadro']);
    
    try {
        $sql_unidad = "SELECT * FROM unidades WHERE id_recuadro = :id_recuadro";
        $stmt_unidad = $conn->prepare($sql_unidad);
        $stmt_unidad->bindParam(':id_recuadro', $id_recuadro, PDO::PARAM_INT);
        $stmt_unidad->execute();
        $detalles_unidad = $stmt_unidad->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        if ($detalles_unidad) {
            echo json_encode(['success' => true, 'detalles' => $detalles_unidad]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontraron detalles para la unidad.']);
        }
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error de base de datos al obtener detalles de la unidad: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_GET['obtener_unidades']) && isset($_GET['id_recuadro'])) {
    header('Content-Type: application/json');
    $id_recuadro = intval($_GET['id_recuadro']);

    try {
        $sql_get_unidades = "SELECT * FROM unidades WHERE id_recuadro = :id_recuadro ORDER BY id_unidad";
        $stmt = $conn->prepare($sql_get_unidades);
        $stmt->bindParam(':id_recuadro', $id_recuadro, PDO::PARAM_INT);
        $stmt->execute();
        $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'unidades' => $unidades]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos en unidad: ' . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_unidad'])) {
    header('Content-Type: application/json');

    try {
        $id_recuadro = intval($_POST['id_recuadro']);
        $nombre_unidad = $_POST['tipo_unidad'] ?? '';
        $descripcion_unidad = $_POST['descripcion_tipo'] ?? '';

        if (empty($id_recuadro) || empty($nombre_unidad)) {
            echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
            exit;
        }

        $sql_insert_unidad = "INSERT INTO unidades 
                             (id_recuadro, nombre_unidad, descripcion_unidad) 
                             VALUES 
                             (:id_recuadro, :nombre_unidad, :descripcion_unidad)";
        $stmt = $conn->prepare($sql_insert_unidad);
        $stmt->bindParam(':id_recuadro', $id_recuadro, PDO::PARAM_INT);
        $stmt->bindParam(':nombre_unidad', $nombre_unidad, PDO::PARAM_STR);
        $stmt->bindParam(':descripcion_unidad', $descripcion_unidad, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Unidad guardada correctamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar la unidad']);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos en unidad: ' . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_unidad'])) {
    header('Content-Type: application/json');
    
    try {
        // Obtener datos del POST
        $id_unidad = intval($_POST['id_unidad']);
        $descripcion_unidad = $_POST['descripcion_unidad'] ?? '';
        $esta_casa_unidad = isset($_POST['esta_casa_unidad']) ? intval($_POST['esta_casa_unidad']) : 0;
        $no_visitar_unidad = intval($_POST['no_visitar_unidad'] ?? 0);
        $es_estudio_unidad = intval($_POST['es_estudio_unidad'] ?? 0);
        $descripcion_estudio_unidad = $_POST['descripcion_estudio_unidad'] ?? '';

        // Validar que tenemos un ID de unidad válido
        if ($id_unidad <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de unidad inválido']);
            exit;
        }

        // Preparar y ejecutar la actualización
        $sql = "UPDATE unidades SET
                descripcion_unidad = :descripcion_unidad,
                esta_casa_unidad = :esta_casa_unidad,
                no_visitar_unidad = :no_visitar_unidad,
                es_estudio_unidad = :es_estudio_unidad,
                descripcion_estudio_unidad = :descripcion_estudio_unidad
                WHERE id_unidad = :id_unidad";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':descripcion_unidad', $descripcion_unidad, PDO::PARAM_STR);
        $stmt->bindParam(':esta_casa_unidad', $esta_casa_unidad, PDO::PARAM_INT);
        $stmt->bindParam(':no_visitar_unidad', $no_visitar_unidad, PDO::PARAM_INT);
        $stmt->bindParam(':es_estudio_unidad', $es_estudio_unidad, PDO::PARAM_INT);
        $stmt->bindParam(':descripcion_estudio_unidad', $descripcion_estudio_unidad, PDO::PARAM_STR);
        $stmt->bindParam(':id_unidad', $id_unidad, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Unidad actualizada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Error al actualizar la unidad'
            ]);
        }
        exit;
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error de base de datos: ' . $e->getMessage()
        ]);
        exit;
    }
}


$rol_usuario = $_SESSION['rol_usuario'];
$feedback_mensaje = "";
$feedback_tipo = "";

$id_imagen_url = isset($_GET['id_imagen']) ? intval($_GET['id_imagen']) : 0;

$nombre_archivo_imagen_solo = "sin_imagen.jpg"; 

if ($id_imagen_url > 0) {
    if (isset($conn) && $conn instanceof PDO) {
        try {
            $sql_get_image_name = "SELECT nombre_archivo FROM imagenes WHERE id_imagen = :id_imagen";
            $stmt_get_image_name = $conn->prepare($sql_get_image_name);
            
            if (!$stmt_get_image_name) {
                error_log("Error al preparar la consulta: " . implode(" ", $conn->errorInfo()));
                $mensaje_bienvenida = "Error de Consulta DB";
            } else {
                $stmt_get_image_name->bindParam(':id_imagen', $id_imagen_url, PDO::PARAM_INT);
                $stmt_get_image_name->execute();
                $row_image = $stmt_get_image_name->fetch(PDO::FETCH_ASSOC);

                if ($row_image) {
                    $nombre_archivo_imagen_solo = htmlspecialchars($row_image['nombre_archivo']);
                    $mensaje_bienvenida = "Territorio " . $id_imagen_url;
                } else {
                    $mensaje_bienvenida = "Territorio " . $id_imagen_url . " No Encontrado";
                }
            }
        } catch (PDOException $e) {
            error_log("Error de base de datos al obtener nombre de imagen: " . $e->getMessage());
            $mensaje_bienvenida = "Error de BD";
        }
    } else {
        $mensaje_bienvenida = "Error: Conexión DB no disponible";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_recuadro'])) {
    $numero_propiedad = $_POST['numero_propiedad'] ?? '';
    $descripcion_casa = $_POST['descripcion_casa'] ?? '';

    $ancho = $_POST['ancho'] ?? 0;
    $alto = $_POST['alto'] ?? 0;

    $coordenadas_post = [];
    $coordenadas_post['x_pos'] = $_POST['x_pos'] ?? null;
    $coordenadas_post['y_pos'] = $_POST['y_pos'] ?? null;

    for ($i = 3; $i <= 62; $i++) {
        $key = 'cor' . $i;
        $coordenadas_post[$key] = !empty($_POST[$key]) ? $_POST[$key] : null;
    }

    try {
        $sql = "INSERT INTO recuadros (
            id_imagen, numero_propiedad, descripcion_casa, ancho, alto, 
            x_pos, y_pos, cor3, cor4, cor5, cor6, cor7, cor8, cor9, cor10, 
            cor11, cor12, cor13, cor14, cor15, cor16, cor17, cor18, cor19, cor20, 
            cor21, cor22, cor23, cor24, cor25, cor26, cor27, cor28, cor29, cor30,
            cor31, cor32, cor33, cor34, cor35, cor36, cor37, cor38, cor39, cor40,
            cor41, cor42, cor43, cor44, cor45, cor46, cor47, cor48, cor49, cor50,
            cor51, cor52, cor53, cor54, cor55, cor56, cor57, cor58, cor59, cor60,
            cor61, cor62
        ) VALUES (
            :id_imagen, :numero_propiedad, :descripcion_casa, :ancho, :alto,
            :x_pos, :y_pos, :cor3, :cor4, :cor5, :cor6, :cor7, :cor8, :cor9, :cor10, 
            :cor11, :cor12, :cor13, :cor14, :cor15, :cor16, :cor17, :cor18, :cor19, :cor20, 
            :cor21, :cor22, :cor23, :cor24, :cor25, :cor26, :cor27, :cor28, :cor29, :cor30,
            :cor31, :cor32, :cor33, :cor34, :cor35, :cor36, :cor37, :cor38, :cor39, :cor40,
            :cor41, :cor42, :cor43, :cor44, :cor45, :cor46, :cor47, :cor48, :cor49, :cor50,
            :cor51, :cor52, :cor53, :cor54, :cor55, :cor56, :cor57, :cor58, :cor59, :cor60,
            :cor61, :cor62
        )";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':id_imagen', $id_imagen_url, PDO::PARAM_INT);
        $stmt->bindParam(':numero_propiedad', $numero_propiedad, PDO::PARAM_STR);
        $stmt->bindParam(':descripcion_casa', $descripcion_casa, PDO::PARAM_STR);
        $stmt->bindParam(':ancho', $ancho, PDO::PARAM_INT);
        $stmt->bindParam(':alto', $alto, PDO::PARAM_INT);

        foreach ($coordenadas_post as $key => &$value) {
            if ($value === null) {
                $stmt->bindParam(':' . $key, $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':' . $key, $value, PDO::PARAM_INT);
            }
        }
        unset($value); 

        if ($stmt->execute()) {
            $feedback_mensaje = "Forma guardada exitosamente";
            $feedback_tipo = "exito";
        } else {
            $feedback_mensaje = "Error al guardar la forma: " . implode(" ", $stmt->errorInfo());
            $feedback_tipo = "error";
        }
    } catch (PDOException $e) {
        error_log("Error de base de datos al guardar forma: " . $e->getMessage());
        $feedback_mensaje = "Error de base de datos: " . $e->getMessage();
        $feedback_tipo = "error";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_recuadro'])) {
    $id_recuadro = intval($_POST['id_recuadro']);
    $numero_propiedad = $_POST['numero_propiedad'] ?? '';
    $descripcion_casa = $_POST['descripcion_casa'] ?? '';
    
    $esta_casa = isset($_POST['esta_casa']) ? intval($_POST['esta_casa']) : 0;
    $no_visitar = isset($_POST['no_visitar']) ? 1 : 0;
    $es_estudio = isset($_POST['es_estudio']) ? 1 : 0;
    $descripcion_estudio = $_POST['descripcion_estudio'] ?? '';

    try {
        $sql_update = "UPDATE recuadros SET 
                        numero_propiedad = :numero_propiedad,
                        descripcion_casa = :descripcion_casa,
                        esta_casa = :esta_casa,
                        no_visitar = :no_visitar,
                        es_estudio = :es_estudio,
                        descripcion_estudio = :descripcion_estudio
                      WHERE id_recuadro = :id_recuadro";

        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(':numero_propiedad', $numero_propiedad, PDO::PARAM_STR);
        $stmt_update->bindParam(':descripcion_casa', $descripcion_casa, PDO::PARAM_STR);
        $stmt_update->bindParam(':esta_casa', $esta_casa, PDO::PARAM_INT);
        $stmt_update->bindParam(':no_visitar', $no_visitar, PDO::PARAM_INT);
        $stmt_update->bindParam(':es_estudio', $es_estudio, PDO::PARAM_INT);
        $stmt_update->bindParam(':descripcion_estudio', $descripcion_estudio, PDO::PARAM_STR);
        $stmt_update->bindParam(':id_recuadro', $id_recuadro, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            $feedback_mensaje = "Recuadro actualizado exitosamente";
            $feedback_tipo = "exito";
        } else {
            $feedback_mensaje = "Error al actualizar recuadro: " . implode(" ", $stmt_update->errorInfo());
            $feedback_tipo = "error";
        }
    } catch (PDOException $e) {
        error_log("Error de base de datos al actualizar recuadro: " . $e->getMessage());
        $feedback_mensaje = "Error de base de datos: " . $e->getMessage();
        $feedback_tipo = "error";
    }
}

$recuadros = [];

try {
    $sql = "SELECT * FROM recuadros WHERE id_imagen = :id_imagen";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_imagen', $id_imagen_url, PDO::PARAM_INT);
    $stmt->execute();
    $recuadros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error al obtener recuadros: " . $e->getMessage());
}

$mensaje_bienvenida = "Territorio " . $id_imagen_url;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($mensaje_bienvenida); ?></title>
    <link rel="stylesheet" href="../assets/styles/predicacion/territorio_asignado.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <div id="image-container-wrapper">
            <div id="image-container">
                <img id="main-image" src="<?php echo $nombre_archivo_imagen_solo ?? 'N/A'; ?>" alt="Imagen de Territorio">
                <canvas id="drawing-canvas"></canvas>
                <svg id="svg-overlay" width="0" height="0">
                    <?php foreach ($recuadros as $recuadro): ?>
                        <?php
                            $color = 'rgba(128, 128, 128, 0.5)';
                            if ($recuadro['esta_casa'] == 1) $color = 'rgba(76, 175, 80, 0.5)';
                            if ($recuadro['no_visitar'] == 1) $color = 'rgba(244, 67, 54, 0.5)';
                            
                            $data_attributes = 
                                'data-id-recuadro="' . $recuadro['id_recuadro'] . '" ' .
                                'data-id-propiedad="' . htmlspecialchars($recuadro['id_propiedad']) . '" ' .
                                'data-numero-propiedad="' . htmlspecialchars($recuadro['numero_propiedad']) . '" ' .
                                'data-descripcion-casa="' . htmlspecialchars($recuadro['descripcion_casa']) . '" ' .
                                'data-esta-casa="' . $recuadro['esta_casa'] . '" ' .
                                'data-no-visitar="' . $recuadro['no_visitar'] . '" ' .
                                'data-es-estudio="' . $recuadro['es_estudio'] . '" ' .
                                'data-descripcion-estudio="' . htmlspecialchars($recuadro['descripcion_estudio']) . '"';

                            if (isset($recuadro['cor3']) && $recuadro['cor3'] !== null) {
                                $data_points = "{$recuadro['x_pos']},{$recuadro['y_pos']}";
                                for ($i = 3; $i <= 62; $i += 2) {
                                    $x_key = 'cor' . $i;
                                    $y_key = 'cor' . ($i + 1);
                                    if (isset($recuadro[$x_key]) && $recuadro[$x_key] !== null && isset($recuadro[$y_key]) && $recuadro[$y_key] !== null) {
                                        $data_points .= " {$recuadro[$x_key]},{$recuadro[$y_key]}";
                                    }
                                }
                                echo '<polygon class="saved-shape" points="" data-original-points="' . $data_points . '" fill="' . $color . '" stroke="black" stroke-width="1" ' . $data_attributes . ' data-shape-type="polygon" />';
                            } else {
                                echo '<rect class="saved-shape" x="0" y="0" width="0" height="0" fill="' . $color . '" stroke="black" stroke-width="1" ' . $data_attributes . ' data-shape-type="rect" ' .
                                     'data-original-x="' . $recuadro['x_pos'] . '" ' .
                                     'data-original-y="' . $recuadro['y_pos'] . '" ' .
                                     'data-original-width="' . $recuadro['ancho'] . '" ' .
                                     'data-original-height="' . $recuadro['alto'] . '" />';
                            }
                        ?>
                    <?php endforeach; ?>
                </svg>
            </div>
        </div>
        
        <div id="modal-overlay"></div>

            <form id="info-panel" method="POST" action="territorio_asignado.php?id_imagen=<?php echo $id_imagen_url; ?>">
            <button type="button" id="btn-cerrar-panel">&times;</button>
            <span id="info-id-recuadro-display"></span>
            
            <h2>Casa <span id="numero-propiedad-display">...</span></h2>
            
            <div id="scrollable-content">
                <input type="hidden" name="actualizar_recuadro" value="1">
            <input type="hidden" id="info-id-recuadro-input" name="id_recuadro">

            <div id="info-details">

                <p><strong>Número de la casa:</strong></p>
                <input type="text" id="info-numero-propiedad" name="numero_propiedad" placeholder="Ej: A-12">
                
                <p id="descripcion_casa"><strong>Descripción de la casa:</strong></p>
                <input type="text" id="info-descripcion-casa" name="descripcion_casa">
                
                <div class="checkbox-group-single">
                <p><strong>¿Atendió?</strong></p>
                    <div class="options-container" style="display: flex; gap: 15px;">
                        
                        <label class="radio-option" id="option-yes">
                            <input type="radio" name="esta_casa" value="1" id="info-esta-casa" class="custom-radio">
                            <label for="info-esta-casa" class="check">
                                <svg width="22px" height="22px" viewBox="0 0 18 18">
                                    <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                    <polyline points="1 9 7 14 15 4"></polyline>
                                </svg>
                            </label>
                            <span>Sí</span>
                        </label>
                        
                        
                        <label class="radio-option" id="option-no">
                            <input type="radio" name="esta_casa" value="0" id="info-no-atendio" class="custom-radio">
                            <label for="info-no-atendio" class="check">
                                <svg width="22px" height="22px" viewBox="0 0 18 18">
                                    <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                    <polyline points="1 9 7 14 15 4"></polyline>
                                </svg>
                            </label>
                            <span>No</span>
                        </label>
                    </div>
                </div>

                <div class="checkbox-group-single">
                    <label class="radio-option" id="option-no-visitar" for="info-no-visitar">
                        <input type="checkbox" id="info-no-visitar" name="no_visitar" value="1" class="custom-radio">
                        <span class="check">
                            <svg width="22px" height="22px" viewBox="0 0 18 18">
                                <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                <polyline points="1 9 7 14 15 4"></polyline>
                            </svg>
                        </span>
                        <span>No Visitar</span>
                    </label>
                </div>
  
                
                <div class="checkbox-group-single">
                    <label class="radio-option" id="option-es-estudio" for="info-es-estudio">
                        <input type="checkbox" id="info-es-estudio" name="es_estudio" value="1" class="custom-radio">
                        <span class="check">
                            <svg width="22px" height="22px" viewBox="0 0 18 18">
                                <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                <polyline points="1 9 7 14 15 4"></polyline>
                            </svg>
                        </span>
                        <span>¿Es un Estudio o revisita?</span>
                    </label>
                </div>
                
                <div id="contenedor-estudio" style="display: none;">
                    <p><strong>¿De quien es el estudio?</strong></p>
                    <input type="text" id="info-descripcion-estudio" name="descripcion_estudio" placeholder="Nombre de quien le da estudio">
                </div>
                <div style="margin-top: 20px;">
                    <div id="lista-unidades-container"></div>
                </div>

            </div>
            <button type="submit" id="btn-actualizar-recuadro" style="margin-top: 20px;">
                Actualizar
            </button>
            
            <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
                <button onclick= "añadirUnidad()" type="button" id="btn-anadir-unidad" style="margin-top: 20px;">
                    Añadir timbre, etc...
                </button>
            <?php endif; ?>

            </div>
            
            </form>
                

            <div id="add-recuadro-form" class="<?= ($_SESSION['rol_usuario'] !== 'anciano') ? 'hidden' : '' ?>">

                <h2>Añadir Nueva Forma</h2>
                
                <div class="form-group">
                    <label for="input-numero-propiedad">Número Propiedad:</label>
                    <input type="text" id="input-numero-propiedad" placeholder="Ej: A-1">
                </div>
                <div class="form-group">
                    <label for="input-descripcion-casa">Descripción de la Casa:</label>
                    <textarea id="input-descripcion-casa" placeholder="Breve descripción de la casa"></textarea>
                </div>

                <div class="form-group">
                                        <div id="display-coordenadas" class="coordinates-display">Haga clic en "Iniciar Dibujo" y marque los puntos en la imagen.</div>
                </div>

                <button id="btn-iniciar-dibujo">Iniciar Dibujo</button>
                <button id="btn-finalizar-dibujo" style="display: none; background-color: #3182CE;">Finalizar Dibujo</button>
                <button id="btn-cancelar-dibujo" style="display: none; background-color: #f44336;">Cancelar</button>
                <button id="btn-guardar-forma">Guardar</button>
                <button id="btn-limpiar-formulario" style="background-color: #FFC107;">Limpiar Formulario</button>
            </div>

            <div id="significado_colores">
                <h2>Significado de los Colores</h2>
                <div class="color-meaning-item">
                    <div class="color-box color-gray"></div>
                    <span>No en casa</span>
                </div>
                <div class="color-meaning-item">
                    <div class="color-box color-green"></div>
                    <span>Si en casa</span>
                </div>
                <div class="color-meaning-item">
                    <div class="color-box color-red"></div>
                    <span>No visitar</span>
                </div>

                <a href="<?php echo $nombre_archivo_imagen_solo ?? '#'; ?>" 
                class="download-btn" 
                data-aos="zoom-in" 
                data-aos-delay="200" 
                download>
                <img src="../assets/img/download_icon.png" alt="" style="height: 20px; vertical-align: middle; margin-right: 8px;">
                Descargar Territorio <?php echo $id_imagen_url; ?>
                </a>
            </div>

        <form id="coordenadas-form" action="territorio_asignado.php?id_imagen=<?php echo $id_imagen_url; ?>" method="POST" style="display: none;">
            <input type="hidden" name="guardar_recuadro" value="1">

            <input type="hidden" name="numero_propiedad" id="form-numero-propiedad">
            <input type="hidden" name="descripcion_casa" id="form-descripcion-casa">
            
            <input type="hidden" name="ancho" id="form-ancho">
            <input type="hidden" name="alto" id="form-alto">
            
            <input type="hidden" name="x_pos" id="form-x-pos">
            <input type="hidden" name="y_pos" id="form-y-pos">
            <?php for ($i = 3; $i <= 62; $i++): ?>
                <input type="hidden" name="cor<?php echo $i; ?>" id="form-cor<?php echo $i; ?>">
            <?php endfor; ?>
        </form>
        

        <h2 id="mapa-territorio">Mapa del territorio</h2>
                


        <div id="image-container-wrapper">
            <div id="image-container">
                <img id="main-image" src="../assets/img/mapa_territorio.jpg" alt="Imagen de Territorio">
               
            </div>
        </div>
    </main>

    <script>
    function añadirUnidad() {
       
        const contenedorUnidad = document.createElement('div');
        contenedorUnidad.id = 'contenedor-nueva-unidad';
        
        contenedorUnidad.innerHTML = `
            <div class="form-container">
                <div id="form-nueva-unidad">
                    <h3>Añadir timbre, etc...</h3>
                    <button type="button" id="btn-cerrar-unidad">&times;</button>
                </div>
                
                <div id="form-nueva-unidad-contenido">
                    <p><strong>¿Que es?:</strong></p>
                    <input type="text" id="tipo-unidad" name="tipo_unidad_nueva" placeholder="Departamento, timbre, etc">
                    
                    <p><strong>Descripción:</strong></p>
                    <input type="text" id="descripcion-tipo" name="descripcion_tipo_nueva" required placeholder="Timbre 1 de arriba a abajo...">
                    
                    <div class="button-group">
                        <button type="button" id="btn-guardar-unidad" class="btn btn-primary">Guardar Unidad</button>
                        <button type="button" id="btn-cancelar-unidad" class="btn btn-secondary">Cancelar</button>
                    </div>
                </div>
            </div>
        `;

        const formularioExistente = document.getElementById('info-panel');
        const botonOtro = document.querySelector('button[onclick="añadirUnidad()"]');
        
        botonOtro.parentNode.insertBefore(contenedorUnidad, botonOtro.nextSibling);

        document.getElementById('btn-cerrar-unidad').addEventListener('click', cerrarFormularioUnidad);
        document.getElementById('btn-cancelar-unidad').addEventListener('click', cerrarFormularioUnidad);
        document.getElementById('btn-guardar-unidad').addEventListener('click', guardarNuevaUnidad);
    }

    function cerrarFormularioUnidad() {
        const contenedor = document.getElementById('contenedor-nueva-unidad');
        if (contenedor) {
            contenedor.remove();
        }
    }

    async function guardarNuevaUnidad() {
        const idRecuadro = document.getElementById('info-id-recuadro-input').value;
        if (!idRecuadro) {
            alert('Error: No hay una propiedad seleccionada para asociar la unidad.');
            return;
        }

        const tipoUnidad = document.getElementById('tipo-unidad').value;
        const descripcionTipo = document.getElementById('descripcion-tipo').value;

        if (!tipoUnidad) {
            alert('Por favor, completa todos los campos obligatorios.');
            return;
        }

        const formData = new FormData();
        formData.append('guardar_unidad', '1');
        formData.append('id_recuadro', idRecuadro);
        formData.append('tipo_unidad', tipoUnidad);
        formData.append('descripcion_tipo', descripcionTipo);

        try {
            const response = await fetch('territorio_asignado.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert('Unidad guardada exitosamente.');
                cerrarFormularioUnidad();
                await obtenerYMostrarUnidades(idRecuadro);
            } else {
                alert('Error al guardar la unidad: ' + result.error);
            }
        } catch (error) {
            console.error('Error en la petición para guardar unidad:', error);
            alert('Ocurrió un error de conexión al intentar guardar la unidad.');
        }
    }

    async function obtenerYMostrarUnidades(idRecuadro) {
        const container = document.getElementById('lista-unidades-container');
        if (!container) {
            console.error('El contenedor de unidades no existe en el DOM.');
            return;
        }
        container.innerHTML = '<p>Cargando unidades...</p>';

        try {
            const response = await fetch(`territorio_asignado.php?obtener_unidades=1&id_recuadro=${idRecuadro}`);
            const data = await response.json();
            container.innerHTML = '';

            if (data.success && data.unidades.length > 0) {
                const titulo = document.createElement('h3');
                titulo.textContent = 'Departamentos, timbres, etc...';
                titulo.style = 'text-align: center; color: #3f51b5; font-size: 1.8rem; padding-bottom: 10px; margin-bottom: 20px;';
                container.appendChild(titulo);

                data.unidades.forEach(unidad => {
                    const unidadDiv = document.createElement('div');
                    unidadDiv.classList.add('unidad-card');
                    unidadDiv.dataset.unidadId = unidad.id_unidad;

                    // Determinar estado inicial de los controles
                    const estaCasaSiChecked = unidad.esta_casa_unidad == 1 ? 'checked' : '';
                    const estaCasaNoChecked = unidad.esta_casa_unidad == 0 ? 'checked' : '';
                    const noVisitarChecked = unidad.no_visitar_unidad == 1 ? 'checked' : '';
                    const esEstudioChecked = unidad.es_estudio_unidad == 1 ? 'checked' : '';
                    const mostrarEstudio = unidad.es_estudio_unidad == 1 ? 'block' : 'none';

                    unidadDiv.innerHTML = `
                        <p class="unidad-titulo"><strong>Esto es un: ${unidad.nombre_unidad || 'Unidad'}</strong></p> 
                        <p><strong>Descripcion: </strong></p> 
                        <input type="text" class="unidad-descripcion" value="${unidad.descripcion_unidad || ''}">

                        <div class="checkbox-group-single" style="margin-bottom: 20px !important;">
                            <p><strong>¿Atendió?</strong></p>
                            <div class="options-container" style="display: flex; gap: 15px;">
                                <label class="radio-option">
                                    <input type="radio" name="esta_casa_${unidad.id_unidad}" value="1" 
                                        class="custom-radio unidad-radio" ${estaCasaSiChecked}>
                                    <span class="check">
                                        <svg width="22px" height="22px" viewBox="0 0 18 18">
                                            <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                            <polyline points="1 9 7 14 15 4"></polyline>
                                        </svg>
                                    </span>
                                    <span>Sí</span>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="esta_casa_${unidad.id_unidad}" value="0" 
                                        class="custom-radio unidad-radio" ${estaCasaNoChecked}>
                                    <span class="check">
                                        <svg width="22px" height="22px" viewBox="0 0 18 18">
                                            <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                            <polyline points="1 9 7 14 15 4"></polyline>
                                        </svg>
                                    </span>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>

                        <div class="checkbox-group-single" style="margin-bottom: 20px !important;">
                            <label class="radio-option">
                                <input type="checkbox" class="custom-radio unidad-checkbox" 
                                    data-type="no-visitar" value="1" ${noVisitarChecked}>
                                <span class="check">
                                    <svg width="22px" height="22px" viewBox="0 0 18 18">
                                        <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                        <polyline points="1 9 7 14 15 4"></polyline>
                                    </svg>
                                </span>
                                <span>No Visitar</span>
                            </label>
                        </div>

                        <div class="checkbox-group-single" style="margin-bottom: 20px !important;">
                            <label class="radio-option">
                                <input type="checkbox" class="custom-radio unidad-checkbox" 
                                    data-type="es-estudio" value="1" ${esEstudioChecked}
                                    onchange="toggleEstudio(this, ${unidad.id_unidad})">
                                <span class="check">
                                    <svg width="22px" height="22px" viewBox="0 0 18 18">
                                        <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                        <polyline points="1 9 7 14 15 4"></polyline>
                                    </svg>
                                </span>
                                <span>¿Es un Estudio o revisita?</span>
                            </label>
                        </div>

                        <div class="contenedor-estudio-unidad" id="estudio-${unidad.id_unidad}" 
                            style="display: ${mostrarEstudio};">
                            <p><strong>¿De quien es el estudio?</strong></p>
                            <input type="text" class="unidad-estudio-input" 
                                value="${unidad.descripcion_estudio_unidad || ''}">
                        </div>
                        <button type="button" class="btn-actualizar-unidad" 
                            onclick="actualizarUnidad(${unidad.id_unidad})">
                            Actualizar
                        </button>
                    `;
                    container.appendChild(unidadDiv);
                });
            } else {
                container.innerHTML = '';
            }
        } catch (error) {
            console.error('Error al obtener la lista de unidades:', error);
            container.innerHTML = '<p style="color: red;">Error al cargar las unidades.</p>';
        }
    }

    function toggleEstudio(checkbox, idUnidad) {
        const contenedor = document.getElementById(`estudio-${idUnidad}`);
        contenedor.style.display = checkbox.checked ? 'block' : 'none';
    }

    async function actualizarUnidad(idUnidad) {
        const unidadCard = document.querySelector(`.unidad-card[data-unidad-id="${idUnidad}"]`);
        if (!unidadCard) {
            alert('Error: No se encontró la unidad');
            return;
        }

        // Obtener valores
        const descripcion = unidadCard.querySelector('.unidad-descripcion').value || '';
        const estaCasa = unidadCard.querySelector(`input[name="esta_casa_${idUnidad}"]:checked`)?.value || 0;
        
        const noVisitar = unidadCard.querySelector('.unidad-checkbox[data-type="no-visitar"]').checked ? 1 : 0;
        const esEstudio = unidadCard.querySelector('.unidad-checkbox[data-type="es-estudio"]').checked ? 1 : 0;
        
        const estudioInput = unidadCard.querySelector('.unidad-estudio-input');
        const estudioDesc = estudioInput ? estudioInput.value || '' : '';

        const formData = new FormData();
        formData.append('actualizar_unidad', '1');
        formData.append('id_unidad', idUnidad);
        formData.append('descripcion_unidad', descripcion);
        formData.append('esta_casa_unidad', estaCasa);
        formData.append('no_visitar_unidad', noVisitar); 
        formData.append('es_estudio_unidad', esEstudio);  
        formData.append('descripcion_estudio_unidad', estudioDesc);

        try {
            const response = await fetch('territorio_asignado.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                // Recargar unidades
                const idRecuadro = document.getElementById('info-id-recuadro-input')?.value;
                if(idRecuadro) obtenerYMostrarUnidades(idRecuadro);
            } else {
                throw new Error(result.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error en actualizarUnidad:', error);
            alert(`Error al actualizar: ${error.message}`);
        }
    }
        window.actualizarUnidad = actualizarUnidad;

    document.addEventListener('DOMContentLoaded', () => {
        let currentSelectedPolygon = null;
        const AppState = { modoDibujo: false, puntos: [] };
        const mainImage = document.getElementById('main-image');
        const drawingCanvas = document.getElementById('drawing-canvas');
        const svgOverlay = document.getElementById('svg-overlay');
        const displayCoordenadas = document.getElementById('display-coordenadas');
        const btnIniciarDibujo = document.getElementById('btn-iniciar-dibujo');
        const btnFinalizarDibujo = document.getElementById('btn-finalizar-dibujo');
        const btnCancelarDibujo = document.getElementById('btn-cancelar-dibujo');
        const btnGuardar = document.getElementById('btn-guardar-forma');
        const btnLimpiar = document.getElementById('btn-limpiar-formulario');
        
        function recalcularPosicionesReescalado() {
            if (!mainImage.complete || !mainImage.naturalWidth || mainImage.naturalWidth === 0) return;

            const rect = mainImage.getBoundingClientRect();
            drawingCanvas.width = rect.width;
            drawingCanvas.height = rect.height;
            svgOverlay.setAttribute('width', rect.width);
            svgOverlay.setAttribute('height', rect.height);
            drawingCanvas.style.top = svgOverlay.style.top = `${mainImage.offsetTop}px`;
            drawingCanvas.style.left = svgOverlay.style.left = `${mainImage.offsetLeft}px`;

            const escalaX = mainImage.clientWidth / mainImage.naturalWidth;
            const escalaY = mainImage.clientHeight / mainImage.naturalHeight;

            document.querySelectorAll('.saved-shape').forEach(shape => {
                const type = shape.getAttribute('data-shape-type');
                
                if (type === 'polygon') {
                    const originalPoints = shape.getAttribute('data-original-points').split(' ').filter(p => p);
                    const scaledPoints = originalPoints.map(p => {
                        const [x, y] = p.split(',');
                        return `${parseFloat(x) * escalaX},${parseFloat(y) * escalaY}`;
                    }).join(' ');
                    shape.setAttribute('points', scaledPoints);
                } else if (type === 'rect') {
                    shape.setAttribute('x', parseFloat(shape.dataset.originalX) * escalaX);
                    shape.setAttribute('y', parseFloat(shape.dataset.originalY) * escalaY);
                    shape.setAttribute('width', parseFloat(shape.dataset.originalWidth) * escalaX);
                    shape.setAttribute('height', parseFloat(shape.dataset.originalHeight) * escalaY);
                }
            });

            if (AppState.modoDibujo) {
                dibujarPoligonoTemporal();
            }
        }

        function toggleModoDibujo(activar) {
            AppState.modoDibujo = activar;
            mainImage.style.cursor = activar ? 'crosshair' : '';
            btnIniciarDibujo.style.display = activar ? 'none' : 'inline-block';
            btnFinalizarDibujo.style.display = activar ? 'inline-block' : 'none';
            btnCancelarDibujo.style.display = activar ? 'inline-block' : 'none';
            btnGuardar.disabled = activar;

            if (!activar) {
                AppState.puntos = [];
                limpiarCanvas();
            }
        }

        function anadirPunto(event) {
            if (!AppState.modoDibujo) return;
            if (AppState.puntos.length >= 31) {
                alert('Se ha alcanzado el máximo de 31 puntos.');
                return;
            }

            const rect = mainImage.getBoundingClientRect();
            const escalaX_inv = mainImage.naturalWidth / mainImage.clientWidth;
            const escalaY_inv = mainImage.naturalHeight / mainImage.clientHeight;
            const x_display = event.clientX - rect.left;
            const y_display = event.clientY - rect.top;
            const x_original = Math.round(x_display * escalaX_inv);
            const y_original = Math.round(y_display * escalaY_inv);

            AppState.puntos.push({ x: x_original, y: y_original });
            displayCoordenadas.textContent = `Puntos: ${AppState.puntos.length}. Haz clic para añadir más o finaliza el dibujo.`;
            dibujarPoligonoTemporal();
        }

        function dibujarPoligonoTemporal() {
            const ctx = drawingCanvas.getContext('2d');
            limpiarCanvas();
            if (AppState.puntos.length === 0) return;

            const escalaX = mainImage.clientWidth / mainImage.naturalWidth;
            const escalaY = mainImage.clientHeight / mainImage.naturalHeight;
            ctx.strokeStyle = '#ff0000';
            ctx.fillStyle = 'rgba(255, 0, 0, 0.3)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            ctx.moveTo(AppState.puntos[0].x * escalaX, AppState.puntos[0].y * escalaY);
            for (let i = 1; i < AppState.puntos.length; i++) {
                ctx.lineTo(AppState.puntos[i].x * escalaX, AppState.puntos[i].y * escalaY);
            }
            if (AppState.puntos.length > 2) {
                ctx.closePath();
                ctx.fill();
            }
            ctx.stroke();
        }

        function limpiarCanvas() {
            const ctx = drawingCanvas.getContext('2d');
            ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
        }

        function finalizarDibujo() {
            if (AppState.puntos.length < 3) {
                alert('Se necesitan al menos 3 puntos para definir una forma.');
                return;
            }
            poblarFormularioOculto();
            displayCoordenadas.textContent = `Forma definida con ${AppState.puntos.length} puntos. Lista para guardar`;
            toggleModoDibujo(false);
        }
        
        function poblarFormularioOculto() {
            document.getElementById('form-x-pos').value = '';
            document.getElementById('form-y-pos').value = '';
            for (let i = 3; i <= 62; i++) {
                document.getElementById(`form-cor${i}`).value = '';
            }

            if (AppState.puntos.length === 0) return;

            document.getElementById('form-x-pos').value = AppState.puntos[0].x;
            document.getElementById('form-y-pos').value = AppState.puntos[0].y;
            
            let formIndex = 3;
            for (let i = 1; i < AppState.puntos.length; i++) {
                document.getElementById(`form-cor${formIndex++}`).value = AppState.puntos[i].x;
                document.getElementById(`form-cor${formIndex++}`).value = AppState.puntos[i].y;
            }

            const { ancho, alto } = calcularBoundingBox();
            document.getElementById('form-ancho').value = ancho;
            document.getElementById('form-alto').value = alto;
        }

        function calcularBoundingBox() {
            if (AppState.puntos.length === 0) return { ancho: 0, alto: 0 };
            let minX = AppState.puntos[0].x, maxX = AppState.puntos[0].x;
            let minY = AppState.puntos[0].y, maxY = AppState.puntos[0].y;
            AppState.puntos.forEach(p => {
                minX = Math.min(minX, p.x); maxX = Math.max(maxX, p.x);
                minY = Math.min(minY, p.y); maxY = Math.max(maxY, p.y);
            });
            return { ancho: Math.round(maxX - minX), alto: Math.round(maxY - minY) };
        }

        function guardarForma() {
            if (!document.getElementById('form-x-pos').value) {
                alert('Primero debe definir una forma en la imagen usando "Iniciar Dibujo" y "Finalizar Dibujo".');
                return;
            }
        
            document.getElementById('form-numero-propiedad').value = document.getElementById('input-numero-propiedad').value;
            document.getElementById('form-descripcion-casa').value = document.getElementById('input-descripcion-casa').value;
            
            document.getElementById('coordenadas-form').submit();
            alert('Se guardo la casa');
        }

        function limpiarFormularioCompleto() {
            const form = document.getElementById('add-recuadro-form');
            form.querySelector('#input-numero-propiedad').value = '';
            form.querySelector('#input-descripcion-casa').value = '';
            
            limpiarCanvas();
            AppState.puntos = [];
            poblarFormularioOculto();
            displayCoordenadas.textContent = 'Haga clic en "Iniciar Dibujo" y marque los puntos en la imagen.';
            if (AppState.modoDibujo) {
                toggleModoDibujo(false);
            }
        }

        function mostrarPanelInfo() {
            document.getElementById('modal-overlay').style.display = 'block';
            document.getElementById('info-panel').style.display = 'block';
        }

        function ocultarPanelInfo() {
            document.getElementById('modal-overlay').style.display = 'none';
            document.getElementById('info-panel').style.display = 'none';
        }

        // Event delegation para unidades
        document.getElementById('lista-unidades-container').addEventListener('change', function(e) {
            const target = e.target;
            const unidadCard = target.closest('.unidad-card');
            if (!unidadCard) return;
            
            // Manejar estudio
            if (target.classList.contains('unidad-checkbox') && target.dataset.type === 'es-estudio') {
                const contenedor = unidadCard.querySelector('.contenedor-estudio-unidad');
                if (contenedor) {
                    contenedor.style.display = target.checked ? 'block' : 'none';
                }
            }
        });

        async function obtenerDetallesRecuadro(idRecuadro) {
            try {
                const response = await fetch(`territorio_asignado.php?obtener_detalles=1&id_recuadro=${idRecuadro}`);
                const data = await response.json();

                const contenedorEstudio = document.getElementById('contenedor-estudio');
                if (parseInt(data.detalles.es_estudio) === 1) {
                    contenedorEstudio.style.display = 'block';
                } else {
                    contenedorEstudio.style.display = 'none';
                }
            
                if (data.success) {
                    document.getElementById('info-id-recuadro-display').textContent = idRecuadro;
                    document.getElementById('info-id-recuadro-input').value = idRecuadro;
                    
                    document.getElementById('info-numero-propiedad').value = data.detalles.numero_propiedad || '';
                    document.getElementById('info-descripcion-casa').value = data.detalles.descripcion_casa || '';
                    document.getElementById('info-descripcion-estudio').value = data.detalles.descripcion_estudio || '';

                    const estaCasaValue = parseInt(data.detalles.esta_casa);
                    document.getElementById('info-esta-casa').checked = (estaCasaValue === 1);
                    document.getElementById('info-no-atendio').checked = (estaCasaValue === 0);
                    document.getElementById('info-no-visitar').checked = (parseInt(data.detalles.no_visitar) === 1);
                    document.getElementById('info-es-estudio').checked = (parseInt(data.detalles.es_estudio) === 1);

                    document.getElementById('numero-propiedad-display').textContent = data.detalles.numero_propiedad || '';
                    
                    mostrarPanelInfo();
                    await obtenerYMostrarUnidades(idRecuadro);

                } else {
                    alert('Error al obtener detalles del recuadro');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al obtener detalles del recuadro');
            }
        }

        if (mainImage.complete && mainImage.naturalHeight > 0) {
            recalcularPosicionesReescalado();
        } else {
            mainImage.addEventListener('load', recalcularPosicionesReescalado);
        }
        window.addEventListener('resize', recalcularPosicionesReescalado);
        btnIniciarDibujo.addEventListener('click', () => toggleModoDibujo(true));
        btnFinalizarDibujo.addEventListener('click', finalizarDibujo);
        btnCancelarDibujo.addEventListener('click', () => {
            toggleModoDibujo(false);
            displayCoordenadas.textContent = 'Dibujo cancelado. Puede iniciar uno nuevo.';
        });
        btnGuardar.addEventListener('click', guardarForma);
        btnLimpiar.addEventListener('click', limpiarFormularioCompleto);
        mainImage.addEventListener('click', anadirPunto);

        svgOverlay.addEventListener('click', (e) => {
            if (e.target.classList.contains('saved-shape')) {
                const forma = e.target;
                const idRecuadro = forma.dataset.idRecuadro;
                currentSelectedPolygon = forma;
                
                document.querySelectorAll('.saved-shape').forEach(shape => {
                    shape.style.stroke = 'black';
                    shape.style.strokeWidth = '1px';
                });
                forma.style.stroke = 'black';
                forma.style.strokeWidth = '2px';
                
                obtenerDetallesRecuadro(idRecuadro);
            }
        });

        document.getElementById('btn-cerrar-panel').addEventListener('click', ocultarPanelInfo);
        document.getElementById('modal-overlay').addEventListener('click', ocultarPanelInfo);

        document.getElementById('info-panel').addEventListener('change', function(e) {
            if (e.target.id === 'info-es-estudio') {
                const estudioContainer = document.getElementById('contenedor-estudio');
                estudioContainer.style.display = e.target.checked ? 'block' : 'none';
                
                if (!e.target.checked) {
                    document.getElementById('info-descripcion-estudio').value = '';
                }
            }
        });

        document.querySelectorAll('input[name="esta_casa"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === "1") {
                    document.getElementById('form-actualizar-esta-casa').value = "1";
                } else {
                    document.getElementById('form-actualizar-esta-casa').value = "0";
                }
            });
        });


        
    });
</script>
</body>
</html>
