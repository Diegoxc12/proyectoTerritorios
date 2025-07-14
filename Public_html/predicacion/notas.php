<?php
session_start();

date_default_timezone_set('America/Guayaquil');

include('../includes/conexion.php'); 

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
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

    for ($i = 3; $i <= 22; $i++) {
        $key = 'cor' . $i;
        $coordenadas_post[$key] = !empty($_POST[$key]) ? $_POST[$key] : null;
    }

    try {
        //Insertar los datsisan
        $sql = "INSERT INTO recuadros (
            id_imagen, numero_propiedad, descripcion_casa, ancho, alto, 
            x_pos, y_pos, cor3, cor4, cor5, cor6, cor7, cor8, cor9, cor10, 
            cor11, cor12, cor13, cor14, cor15, cor16, cor17, cor18, cor19, cor20, 
            cor21, cor22
        ) VALUES (
            :id_imagen, :numero_propiedad, :descripcion_casa,:ancho, :alto,
            :x_pos, :y_pos, :cor3, :cor4, :cor5, :cor6, :cor7, :cor8, :cor9, :cor10, 
            :cor11, :cor12, :cor13, :cor14, :cor15, :cor16, :cor17, :cor18, :cor19, :cor20, 
            :cor21, :cor22
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

$recuadros = [];

//opbtener los recuadros 
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
        <style>
        #image-container { position: relative; display: inline-block; }
        #main-image { display: block; max-width: 100%; height: auto; }
        #drawing-canvas, #svg-overlay { position: absolute; top: 0; left: 0; pointer-events: none; }
        #main-image { pointer-events: auto; }
        .saved-shape { cursor: pointer; pointer-events: all; }
        .saved-shape:hover { stroke-width: 3; stroke: black; }

        main {
            margin: 15px;
            padding-bottom: 40px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 80px);
            justify-content: flex-start;
        }

        @media (min-width: 768px) {
            main {
                margin: 30px;
                padding-bottom: 60px;
                min-height: calc(100vh - 100px);
                max-width: none;
                width: calc(100% - 60px);
            }
            
            main h1 {
                font-size: 2.5rem;
                margin-bottom: 3rem;
            }
            
            .form-container {
                padding: 2.5rem;
                max-width: 900px;
                width: 100%;
            }
            
            form {
                gap: 2rem;
            }
            
            .form-group h2, .form-group h3 {
                font-size: 1.125rem;
            }
            
            /* Layout de dos columnas para algunos campos */
            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1.5rem;
            }
            
            input[type="date"],
            input[type="text"],
            textarea {
                padding: 1rem 1.25rem;
                font-size: 1.05rem;
            }
            
            textarea {
                min-height: 140px;
            }
            
            .checkbox-container {
                padding: 1.25rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 1rem 2rem;
                font-size: 1.05rem;
            }

            button[type="submit"] {
                padding: 1.25rem 2.5rem;
                font-size: 1.125rem;
                align-self: center;
                min-width: 200px;
            }
        }

        /* ==========================================================================
        RESPONSIVE DESIGN - DESKTOP Y PANTALLAS GRANDES
        ========================================================================== */

        @media (min-width: 1024px) {
            /* Ajustes para el menú lateral */
            main {
                margin-left: 355px;
                margin-right: 50px;
                padding-bottom: 60px;
                min-height: calc(100vh - 100px);
                width: calc(100% - 405px);
            }
            
            main h1 {
                font-size: 3rem;
                margin-bottom: 3.5rem;
            }
            
            .form-container {
                padding: 3rem;
                border-radius: var(--radius-xl);
                max-width: 1000px;
                width: 100%;
            }
            
            form {
                gap: 2.5rem;
            }
            
            .form-group h2, .form-group h3 {
                font-size: 1.25rem;
            }
            
            /* Mejores efectos hover en desktop */
            input[type="date"]:hover,
            input[type="text"]:hover,
            textarea:hover {
                border-color: var(--color-secondary);
                box-shadow: var(--shadow-sm);
            }
            
            .checkbox-container:hover {
                background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
            }
            
            /* Botón más prominente */
            button[type="submit"] {
                padding: 1.5rem 3rem;
                font-size: 1.2rem;
                margin-top: 2rem;
            }
            
            /* Efectos de parallax sutiles */
            .form-container::after {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle at center, rgba(108, 126, 244, 0.03) 0%, transparent 70%);
                pointer-events: none;
                z-index: -1;
            }
        }

        /* ==========================================================================
        PANTALLAS EXTRA GRANDES
        ========================================================================== */

        @media (min-width: 1440px) {
            main {
                margin-left: 400px;
                margin-right: 50px;
                width: calc(100% - 450px);
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .form-container {
                padding: 4rem;
                max-width: 1200px;
            }
            
            main h1 {
                font-size: 3.5rem;
            }
        }

        /* ==========================================================================
        PANTALLAS MUY PEQUEÑAS
        ========================================================================== */

        @media (max-width: 480px) {
            main {
                margin: 10px;
                min-height: calc(100vh - 70px);
                width: calc(100% - 20px);
            }
            
            main h1 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .form-container {
                padding: 1.25rem;
                border-radius: var(--radius-lg);
                width: 100%;
            }
            
            form {
                gap: 1.25rem;
            }
            
            .form-group h2, .form-group h3 {
                font-size: 1rem;
            }
            
            input[type="date"],
            input[type="text"],
            textarea {
                padding: 0.75rem;
                font-size: 1rem;
            }
            
            textarea {
                min-height: 100px;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.75rem 1.25rem;
                font-size: 0.95rem;
            }

            button[type="submit"] {
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
                width: 100%;
            }
        }

        /* ==========================================================================
        MEJORAS DE ACCESIBILIDAD
        ========================================================================== */

        /* Focus visible mejorado */
        input:focus-visible,
        textarea:focus-visible,
        button:focus-visible {
            outline: 2px solid var(--color-accent);
            outline-offset: 2px;
        }

        /* Reducir movimiento para usuarios que lo prefieren */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Alto contraste */
        @media (prefers-contrast: high) {
            :root {
                --color-border: #000000;
                --color-text: #000000;
                --color-text-light: #333333;
            }
        }

        /* ==========================================================================
        UTILIDADES ADICIONALES
        ========================================================================== */

        .mobile-only {
            display: block;
        }

        .desktop-only {
            display: none;
        }

        @media (min-width: 1024px) {
            .mobile-only {
                display: none;
            }
            
            .desktop-only {
                display: block;
            }
        }

        /* --- ESTILOS ADICIONALES --- */

        #image-container-wrapper {
            position: relative; /* Necesario para posicionar los recuadros */
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px auto; /* Centrado */
            background-color: #f0f0f0; 
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 10px;
            overflow: hidden; 
            max-width: 90vw; 
            max-height: 80vh; 
        }

        #main-image {
            max-width: 100%; 
            max-height: 75vh; 
            height: auto; 
            display: block;
            border-radius: 4px;
        }

        #significado_colores {
            margin-top: 30px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            border-top: 4px solid  #3182CE;
        }

        #significado_colores h2 {
            font-size: 1.8rem; 
            color: #333;
            margin-bottom: 15px; 
            text-align: center;
            border-bottom: 2px solid #eee; 
            padding-bottom: 10px;
        }

        .color-meaning-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: #555;
        }

        .color-box {
            width: 25px;
            height: 25px;
            border-radius: 50%; 
            margin-right: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1); 
        }

        #info-panel {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            display: none; 
        }

        #info-panel h2 {
            color: #3f51b5; 
            margin-top: 0;
            font-size: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        #info-panel p {
            margin-bottom: 8px;
            font-size: 1rem;
        }

        #info-panel p strong {
            color: #616161;
        }

        #info-panel #info-unidades-container h3 {
            color: #3f51b5;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.3rem;
            border-bottom: 1px dashed #e0e0e0;
            padding-bottom: 5px;
        }

        #info-panel #lista-unidades {
            list-style: none; 
            padding: 0;
            margin: 0;
        }

        #info-panel #lista-unidades li {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 5px;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        #info-panel #lista-unidades li strong {
            color: #424242;
        }

        .recuadro {
            position: absolute;
            border: 2px solid rgba(255, 255, 255, 0.0); 
            cursor: pointer;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            box-sizing: border-box; 
            background-color: rgba(0, 0, 0, 0); 
        }

        .recuadro:hover {
            border-color: rgba(0, 0, 0, 0.5); 
            background-color: rgba(255, 255, 255, 0.2);
        }

        .recuadro.active {
            border-color: #3f51b5; 
            background-color: rgba(63, 81, 181, 0.3); 
        }

        #dashboard-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .image-button {
            padding: 10px 15px;
            background-color: #64B5F6; 
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .image-button:hover {
            background-color: #42A5F5; 
            transform: translateY(-2px);
        }

        .image-button.active-button {
            background-color: #2196F3; 
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
            border: 2px solid #1976D2; 
        }
        
        /* Estilos para el formulario de agregar recuadro */
        #add-recuadro-form {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-top: 30px;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            border-top: 4px solid  #3182CE;
        }

        #add-recuadro-form h2 {
            color: #3f51b5;
            margin-top: 0;
            font-size: 1.8rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        #add-recuadro-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        #add-recuadro-form label {
            font-weight: bold;
            color: #424242;
            font-size: 0.95rem;
        }

        #add-recuadro-form input[type="text"],
        #add-recuadro-form textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            width: calc(100% - 22px); /* Para compensar padding y borde */
        }

        #add-recuadro-form textarea {
            resize: vertical;
            min-height: 80px;
        }

        #add-recuadro-form .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        #add-recuadro-form .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        #add-recuadro-form .checkbox-group label {
            font-weight: normal;
            font-size: 1rem;
            color: #333;
        }

        #add-recuadro-form .coordinates-display {
            background-color: #e8f0fe;
            border: 1px dashed #a7c8f2;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            color: #3f51b5;
            margin-top: 5px;
            word-break: break-all; /* Rompe palabras largas */
        }

        #add-recuadro-form button {
            padding: 12px 20px;
            background-color: #3182CE; /* Verde para acciones principales */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
            margin-top: 15px;
        }

        #add-recuadro-form button:hover {
            transform: translateY(-1px);
        }

        #btn-asignar-coordenadas {
            background-color: #2196F3; /* Azul para asignar coordenadas */
            margin-bottom: 10px;
        }

        #btn-asignar-coordenadas:hover {
            background-color: #1976D2;
        }

        .highlight-mode {
            cursor: crosshair !important; /* Cambia el cursor cuando se está asignando */
        }

        .highlight-mode #main-image {
            outline: 3px dashed #FFC107; /* Resalta la imagen cuando está en modo de asignación */
            outline-offset: 5px;
            transition: outline 0.2s ease;
        }

        /* Estilos específicos para recuadros */
        #image-container-wrapper {
            position: relative;
            display: inline-block;
            margin: 20px;
        }
        
        #image-container {
            position: relative;
        }
        
        .recuadro {
            position: absolute;
            border: 2px solid;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .recuadro:hover {
            border-width: 3px;
            z-index: 10;
        }
        
        .color-gray { border-color: rgba(128, 128, 128, 0.3); background-color: rgba(128, 128, 128, 0.3); }
        .color-green { border-color:rgba(76, 175, 79, 0.53) ; background-color:rgba(76, 175, 79, 0.53);}
        .color-red { border-color: #f44336; background-color: rgba(244, 67, 54, 0.3); }
        
        .rectangulo-temporal {
            position: absolute;
            border: 2px dashed #007BFF;
            pointer-events: none;
        }
    </style>
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
                                for ($i = 3; $i <= 22; $i += 2) {
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

        <div id="info-panel">
            <h2>Detalles de la Forma</h2>
            </div>

        <div id="add-recuadro-form">
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
                <label>Coordenadas:</label>
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
                <span>Recuadro sin asignar</span>
            </div>
            <div class="color-meaning-item">
                <div class="color-box color-green"></div>
                <span>Casa visitada</span>
            </div>
            <div class="color-meaning-item">
                <div class="color-box color-red"></div>
                <span>No visitar</span>
            </div>
        </div>

        <form id="coordenadas-form" action="territorio_asignado.php?id_imagen=<?php echo $id_imagen_url; ?>" method="POST" style="display: none;">
            <input type="hidden" name="guardar_recuadro" value="1">

            <input type="hidden" name="numero_propiedad" id="form-numero-propiedad">
            <input type="hidden" name="descripcion_casa" id="form-descripcion-casa">
            
            <input type="hidden" name="ancho" id="form-ancho">
            <input type="hidden" name="alto" id="form-alto">
            
            <input type="hidden" name="x_pos" id="form-x-pos">
            <input type="hidden" name="y_pos" id="form-y-pos">
            <?php for ($i = 3; $i <= 22; $i++): ?>
                <input type="hidden" name="cor<?php echo $i; ?>" id="form-cor<?php echo $i; ?>">
            <?php endfor; ?>
        </form>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        const AppState = {
            modoDibujo: false,
            puntos: [],
        };

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
            if (AppState.puntos.length >= 11) {
                alert('Se ha alcanzado el máximo de 11 puntos.');
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
            displayCoordenadas.textContent = `Forma definida con ${AppState.puntos.length} puntos. Lista para guardar.`;
            toggleModoDibujo(false);
        }
        
        function poblarFormularioOculto() {
            document.getElementById('form-x-pos').value = '';
            document.getElementById('form-y-pos').value = '';
            for (let i = 3; i <= 22; i++) {
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
                document.getElementById('info-id-propiedad').textContent = forma.dataset.idPropiedad;
                document.getElementById('info-numero-propiedad').textContent = forma.dataset.numeroPropiedad;
                //... Llenar los demás campos del panel de información
            }
        });
    });
    </script>
</body>
</html>



<style>
        #image-container { position: relative; display: inline-block; }
        #main-image { display: block; max-width: 100%; height: auto; }
        #drawing-canvas, #svg-overlay { position: absolute; top: 0; left: 0; pointer-events: none; }
        #main-image { pointer-events: auto; }
        .saved-shape { cursor: pointer; pointer-events: all; }
        .saved-shape:hover { stroke-width: 3; stroke: black; }

        main {
            margin: 15px;
            padding-bottom: 40px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 80px);
            justify-content: flex-start;
        }

        @media (min-width: 768px) {
            main {
                margin: 30px;
                padding-bottom: 60px;
                min-height: calc(100vh - 100px);
                max-width: none;
                width: calc(100% - 60px);
            }
            
            main h1 {
                font-size: 2.5rem;
                margin-bottom: 3rem;
            }
            
            .form-container {
                padding: 2.5rem;
                max-width: 900px;
                width: 100%;
            }
            
            form {
                gap: 2rem;
            }
            
            .form-group h2, .form-group h3 {
                font-size: 1.125rem;
            }
            
            /* Layout de dos columnas para algunos campos */
            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1.5rem;
            }
            
            input[type="date"],
            input[type="text"],
            textarea {
                padding: 1rem 1.25rem;
                font-size: 1.05rem;
            }
            
            textarea {
                min-height: 140px;
            }
            
            .checkbox-container {
                padding: 1.25rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 1rem 2rem;
                font-size: 1.05rem;
            }

            button[type="submit"] {
                padding: 1.25rem 2.5rem;
                font-size: 1.125rem;
                align-self: center;
                min-width: 200px;
            }
        }

        /* ==========================================================================
        RESPONSIVE DESIGN - DESKTOP Y PANTALLAS GRANDES
        ========================================================================== */

        @media (min-width: 1024px) {
            /* Ajustes para el menú lateral */
            main {
                margin-left: 355px;
                margin-right: 50px;
                padding-bottom: 60px;
                min-height: calc(100vh - 100px);
                width: calc(100% - 405px);
            }
            
            main h1 {
                font-size: 3rem;
                margin-bottom: 3.5rem;
            }
            
            .form-container {
                padding: 3rem;
                border-radius: var(--radius-xl);
                max-width: 1000px;
                width: 100%;
            }
            
            form {
                gap: 2.5rem;
            }
            
            .form-group h2, .form-group h3 {
                font-size: 1.25rem;
            }
            
            /* Mejores efectos hover en desktop */
            input[type="date"]:hover,
            input[type="text"]:hover,
            textarea:hover {
                border-color: var(--color-secondary);
                box-shadow: var(--shadow-sm);
            }
            
            .checkbox-container:hover {
                background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
            }
            
            /* Botón más prominente */
            button[type="submit"] {
                padding: 1.5rem 3rem;
                font-size: 1.2rem;
                margin-top: 2rem;
            }
            
            /* Efectos de parallax sutiles */
            .form-container::after {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle at center, rgba(108, 126, 244, 0.03) 0%, transparent 70%);
                pointer-events: none;
                z-index: -1;
            }
        }

        /* ==========================================================================
        PANTALLAS EXTRA GRANDES
        ========================================================================== */

        @media (min-width: 1440px) {
            main {
                margin-left: 400px;
                margin-right: 50px;
                width: calc(100% - 450px);
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .form-container {
                padding: 4rem;
                max-width: 1200px;
            }
            
            main h1 {
                font-size: 3.5rem;
            }
        }

        /* ==========================================================================
        PANTALLAS MUY PEQUEÑAS
        ========================================================================== */

        @media (max-width: 480px) {
            main {
                margin: 10px;
                min-height: calc(100vh - 70px);
                width: calc(100% - 20px);
            }
            
            main h1 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .form-container {
                padding: 1.25rem;
                border-radius: var(--radius-lg);
                width: 100%;
            }
            
            form {
                gap: 1.25rem;
            }
            
            .form-group h2, .form-group h3 {
                font-size: 1rem;
            }
            
            input[type="date"],
            input[type="text"],
            textarea {
                padding: 0.75rem;
                font-size: 1rem;
            }
            
            textarea {
                min-height: 100px;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.75rem 1.25rem;
                font-size: 0.95rem;
            }

            button[type="submit"] {
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
                width: 100%;
            }
        }

        /* ==========================================================================
        MEJORAS DE ACCESIBILIDAD
        ========================================================================== */

        /* Focus visible mejorado */
        input:focus-visible,
        textarea:focus-visible,
        button:focus-visible {
            outline: 2px solid var(--color-accent);
            outline-offset: 2px;
        }

        /* Reducir movimiento para usuarios que lo prefieren */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Alto contraste */
        @media (prefers-contrast: high) {
            :root {
                --color-border: #000000;
                --color-text: #000000;
                --color-text-light: #333333;
            }
        }

        /* ==========================================================================
        UTILIDADES ADICIONALES
        ========================================================================== */

        .mobile-only {
            display: block;
        }

        .desktop-only {
            display: none;
        }

        @media (min-width: 1024px) {
            .mobile-only {
                display: none;
            }
            
            .desktop-only {
                display: block;
            }
        }

        /* --- ESTILOS ADICIONALES --- */

        #image-container-wrapper {
            position: relative; /* Necesario para posicionar los recuadros */
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px auto; /* Centrado */
            background-color: #f0f0f0; 
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 10px;
            overflow: hidden; 
            max-width: 90vw; 
            max-height: 80vh; 
        }

        #main-image {
            max-width: 100%; 
            max-height: 75vh; 
            height: auto; 
            display: block;
            border-radius: 4px;
        }

        #significado_colores {
            margin-top: 30px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            border-top: 4px solid  #3182CE;
        }

        #significado_colores h2 {
            font-size: 1.8rem; 
            color: #333;
            margin-bottom: 15px; 
            text-align: center;
            border-bottom: 2px solid #eee; 
            padding-bottom: 10px;
        }

        .color-meaning-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: #555;
        }

        .color-box {
            width: 25px;
            height: 25px;
            border-radius: 50%; 
            margin-right: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1); 
        }

        #info-panel {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
            max-height: 90vh;
            overflow-y: auto;
            display: none; /* Mantener oculto por defecto */
        }
            #modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        #btn-cerrar-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #555;
        }
            
        #info-panel h2 {
            color: #3f51b5; 
            margin-top: 0;
            font-size: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        #info-panel p {
            margin-bottom: 8px;
            font-size: 1rem;
        }

        #info-panel p strong {
            color: #616161;
        }

        #info-panel #info-unidades-container h3 {
            color: #3f51b5;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.3rem;
            border-bottom: 1px dashed #e0e0e0;
            padding-bottom: 5px;
        }

        #info-panel #lista-unidades {
            list-style: none; 
            padding: 0;
            margin: 0;
        }

        #info-panel #lista-unidades li {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 5px;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        #info-panel #lista-unidades li strong {
            color: #424242;
        }

        .recuadro {
            position: absolute;
            border: 2px solid rgba(255, 255, 255, 0.0); 
            cursor: pointer;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            box-sizing: border-box; 
            background-color: rgba(0, 0, 0, 0); 
        }

        .recuadro:hover {
            border-color: rgba(0, 0, 0, 0.5); 
            background-color: rgba(255, 255, 255, 0.2);
        }

        .recuadro.active {
            border-color: #3f51b5; 
            background-color: rgba(63, 81, 181, 0.3); 
        }

        #dashboard-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .image-button {
            padding: 10px 15px;
            background-color: #64B5F6; 
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .image-button:hover {
            background-color: #42A5F5; 
            transform: translateY(-2px);
        }

        .image-button.active-button {
            background-color: #2196F3; 
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
            border: 2px solid #1976D2; 
        }
        
        /* Estilos para el formulario de agregar recuadro */
        #add-recuadro-form {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-top: 30px;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            border-top: 4px solid  #3182CE;
        }

        #add-recuadro-form h2 {
            color: #3f51b5;
            margin-top: 0;
            font-size: 1.8rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        #add-recuadro-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        #add-recuadro-form label {
            font-weight: bold;
            color: #424242;
            font-size: 0.95rem;
        }

        #add-recuadro-form input[type="text"],
        #add-recuadro-form textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            width: calc(100% - 22px); /* Para compensar padding y borde */
        }

        #add-recuadro-form textarea {
            resize: vertical;
            min-height: 80px;
        }

        #add-recuadro-form .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        #add-recuadro-form .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        #add-recuadro-form .checkbox-group label {
            font-weight: normal;
            font-size: 1rem;
            color: #333;
        }

        #add-recuadro-form .coordinates-display {
            background-color: #e8f0fe;
            border: 1px dashed #a7c8f2;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            color: #3f51b5;
            margin-top: 5px;
            word-break: break-all; /* Rompe palabras largas */
        }

        #add-recuadro-form button {
            padding: 12px 20px;
            background-color: #3182CE; /* Verde para acciones principales */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
            margin-top: 15px;
        }

        #add-recuadro-form button:hover {
            transform: translateY(-1px);
        }

        #btn-asignar-coordenadas {
            background-color: #2196F3; /* Azul para asignar coordenadas */
            margin-bottom: 10px;
        }

        #btn-asignar-coordenadas:hover {
            background-color: #1976D2;
        }

        .highlight-mode {
            cursor: crosshair !important; /* Cambia el cursor cuando se está asignando */
        }

        .highlight-mode #main-image {
            outline: 3px dashed #FFC107; /* Resalta la imagen cuando está en modo de asignación */
            outline-offset: 5px;
            transition: outline 0.2s ease;
        }

        /* Estilos específicos para recuadros */
        
        #image-container {
            position: relative;
        }
        
        .recuadro {
            position: absolute;
            border: 2px solid;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .recuadro:hover {
            border-width: 3px;
            z-index: 10;
        }
        
        .color-gray { border-color: rgba(128, 128, 128, 0.3); background-color: rgba(128, 128, 128, 0.3); }
        .color-green { border-color:rgba(76, 175, 79, 0.53) ; background-color:rgba(76, 175, 79, 0.53);}
        .color-red { border-color: #f44336; background-color: rgba(244, 67, 54, 0.3); }
        
        .rectangulo-temporal {
            position: absolute;
            border: 2px dashed #007BFF;
            pointer-events: none;
        }
    </style>



