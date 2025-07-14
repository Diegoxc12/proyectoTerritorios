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
$mensaje_bienvenida = "Agregar Campañas";

$feedback_mensaje = "";
$feedback_tipo = ""; 

$fecha_campana = '';
$lugar = '';
$titulo_campana = '';
$descripcion_campana = '';
$fecha_expiracion_checked = false; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {
    header('Content-Type: application/json'); 
    $response = array('success' => false, 'message' => 'Error desconocido al procesar PDF.');

    // Verificar si se recibió el archivo sin errores
    if ($_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) {
        $tempFilePath = $_FILES['pdfFile']['tmp_name']; 

        $originalFileName = $_FILES['pdfFile']['name']; 
        
        $destinationFolder = __DIR__ . '/arreglos_transporte/'; 
        
        // Asegurarse de que la carpeta de destino exista, si no, crearla
        if (!is_dir($destinationFolder)) {
            if (!mkdir($destinationFolder, 0755, true)) { // Permisos 0755 son más seguros para directorios
                $response['message'] = 'No se pudo crear la carpeta de destino para el PDF.';
                echo json_encode($response);
                exit; // Termina la ejecución si no se puede crear la carpeta
            }
        }

        // Ruta completa donde se guardaría el archivo
        $finalFilePath = $destinationFolder . basename($originalFileName);

        if (file_exists($finalFilePath)) {
            $response['success'] = false;
            $response['message'] = 'Ya existe un archivo con el nombre "' . htmlspecialchars(basename($originalFileName)) . '". Por favor, ingrese otro nombre.';
        } else {
            if (move_uploaded_file($tempFilePath, $finalFilePath)) {
                $response['success'] = true;
                $response['message'] = 'PDF guardado con éxito en el servidor.';
                $response['filePath'] = 'arreglos_transporte/' . basename($originalFileName); 
            } else {
                $response['message'] = 'Error al mover el archivo PDF subido.';
            }
        }

    } else {
        // Manejar errores de subida de PHP
        switch ($_FILES['pdfFile']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $response['message'] = 'El archivo PDF es demasiado grande.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $response['message'] = 'El archivo PDF fue subido parcialmente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $response['message'] = 'No se seleccionó ningún archivo PDF.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $response['message'] = 'Falta una carpeta temporal para la subida de PDF.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $response['message'] = 'Fallo al escribir el archivo PDF en el disco.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $response['message'] = 'Una extensión de PHP detuvo la subida del archivo PDF.';
                break;
            default:
                $response['message'] = 'Error desconocido en la subida del archivo PDF.';
                break;
        }
    }

    echo json_encode($response);
    exit; // Importante: Termina la ejecución aquí para que no se procese el resto del HTML/PHP
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha_campana = $_POST['fecha_campana'] ?? '';
    $lugar = trim($_POST['lugar'] ?? '');
    $titulo_campana = trim($_POST['titulo_campana'] ?? '');
    $descripcion_campana = trim($_POST['descripcion_campana'] ?? '');
    $fecha_expiracion_checked = isset($_POST['fecha_expiracion_checkbox']);

    $fecha_expiracion_valor = null;
    if ($fecha_expiracion_checked) {
        $fecha_expiracion_valor = date('Y-m-d H:i:s', strtotime('+2 weeks'));
    }

    if (empty($fecha_campana) || empty($lugar) || empty($titulo_campana) || empty($descripcion_campana)) {
        $feedback_mensaje = "Error: Todos los campos de la campaña son obligatorios.";
        $feedback_tipo = "error";
    } else {
        try {
            $conn->beginTransaction();

            $stmt_campana = $conn->prepare("INSERT INTO campañas (fecha_campaña, lugar, titulo_campaña, descripcion_campaña, fecha_expiracion) VALUES (?, ?, ?, ?, ?)");
            $stmt_campana->execute([$fecha_campana, $lugar, $titulo_campana, $descripcion_campana, $fecha_expiracion_valor]);
            $id_campana = $conn->lastInsertId();

            if (isset($_POST['carros']) && is_array($_POST['carros'])) {
                foreach ($_POST['carros'] as $carro_data) {
                    $nombre_carro = trim($carro_data['nombre_carro'] ?? '');

                    if (!empty($nombre_carro)) {
                        $stmt_carro = $conn->prepare("INSERT INTO carros (id_campaña, nombre_carro) VALUES (?, ?)");
                        $stmt_carro->execute([$id_campana, $nombre_carro]);
                        $id_carro = $conn->lastInsertId();

                        if (isset($carro_data['personas']) && is_array($carro_data['personas'])) {
                            foreach ($carro_data['personas'] as $persona_data) {
                                $nombre_persona = trim($persona_data['nombre'] ?? '');
                                if (!empty($nombre_persona)) {
                                    $stmt_persona = $conn->prepare("INSERT INTO personas (id_carro, nombre) VALUES (?, ?)");
                                    $stmt_persona->execute([$id_carro, $nombre_persona]);
                                }
                            }
                        }
                    }
                }
            }

            $conn->commit();
            $feedback_mensaje = "¡Campaña creada exitosamente!";
            $feedback_tipo = "success";

            $fecha_campana = '';
            $lugar = '';
            $titulo_campana = '';
            $descripcion_campana = '';
            $fecha_expiracion_checked = false;

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Error al crear campaña: " . $e->getMessage());
            $feedback_mensaje = "Error al crear la campaña. Por favor, intente de nuevo más tarde. (Detalle: " . $e->getMessage() . ")";
            $feedback_tipo = "error";
        }
    }
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
    <title>Agregar Campaña</title>
    <style>
        :root {
            --color-primary: #0F1435;
            --color-secondary:rgb(49, 130, 206);
            --color-accent: #4F46E5;
            --color-success: #10B981;
            --color-error: #EF4444;
            --color-warning: #F59E0B;
            --color-text: #1F2937;
            --color-text-light: #6B7280;
            --color-bg: #F9FAFB;
            --color-white: #FFFFFF;
            --color-border: #E5E7EB;
            --color-border-focus: #3B82F6;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 6px 6px 10px 10px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-bg);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: var(--color-text);
            background-color:rgb(142, 178, 255); /* Color de fondo más claro */
        }

        main {
            margin: 15px;
            padding-bottom: 40px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 80px);
            justify-content: flex-start;
        }
        .feedback-message {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideInDown 0.5s ease;
            width: 100%;
            max-width: 1000px;
            text-align: center;
        }

        .feedback-message.success {
            background-color: #ECFDF5;
            border: 1px solid #10B981;
            color: #047857;
        }

        .feedback-message.error {
            background-color: #FEF2F2;
            border: 1px solid #EF4444;
            color: #DC2626;
        }

        /* Contenedor del formulario */
        .form-container {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            border-top: 5px solid var(--color-secondary);
        }

        /* Estilos del formulario */
        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Grupos de campos */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group h2, .form-group h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group h2::before, .form-group h3::before {
            content: '';
            width: 4px;
            height: 20px;
            background-color: #0F1435;
            border-radius: 2px;
        }

        /* Estilos para inputs */
        input[type="date"],
        input[type="text"],
        textarea {
            padding: 0.875rem 1rem;
            border: 2px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--color-white);
            color: var(--color-text);
            font-family: inherit;
        }

        input[type="date"]:focus,
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--color-border-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }

        /* Textarea específico */
        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        /* Placeholder styles */
        input::placeholder,
        textarea::placeholder {
            color: var(--color-text-light);
            opacity: 1;
        }

        /* Checkbox container */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
            border-radius: var(--radius-md, 0.5rem);
            border: 1px solid var(--color-border, #E5E7EB);
            transition: var(--transition, all 0.3s ease);
            margin-top: 0.5rem;
        }

        .checkbox-container:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 10px 0 rgba(0, 0, 0, 0.32);
        }

        .checkbox-container label {
            font-size: 0.95rem;
            color: var(--color-text, #374151);
            cursor: pointer;
            user-select: none;
            font-weight: 500;
            display: flex; /* Para alinear el checkbox con el texto */
            align-items: center;
            gap: 0.75rem;
            width: 100%;
        }

        .checkbox-container input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: var(--color-accent, #3B82F6);
            cursor: pointer;
            flex-shrink: 0; /* Evita que el checkbox se encoja */
        }

        /* Botones generales */
        .btn-primary, .btn-secondary {
            border: none;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);

            align-self: flex-start; /* Alinea los botones a la izquierda por defecto */
        }

        .btn-primary {
            background-color: #0F1435;
            color: var(--color-white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background-color: var(--color-white);
            color: var(--color-primary);
            border: 1px solid var(--color-primary);
        }

        .btn-secondary:hover {
            background-color: var(--color-primary);
            color: var(--color-white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        button[type="submit"] {
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;

            box-shadow: var(--shadow-md);
            width: 100%; /* Asegura que el botón de submit sea ancho completo */
            
        }

        button[type="submit"]:hover {
            background-color: var(--color-secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            transition: left 0.5s;
        }

        button[type="submit"]:hover::before {
            left: 100%;
        }

        /* Divisor */
        .section-divider {
            border: 0;
            border-top: 2px dashed var(--color-border);
            width: 100%;
        }

        .section-header {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
        }

        .section-header::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background-color: var(--color-secondary);
            margin: 10px auto 0;
            border-radius: 2px;
        }

        /* Estilos para los carros y personas */
        .carro-item {
            background-color: #F8FAFC;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .carro-item:hover {
            border-color: var(--color-secondary);
            transform: translateY(-1px);
        }

        .carro-item .form-group {
            margin-bottom: 1rem;
        }

        .personas-container {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            padding-left: 1rem;
            border-left: 3px solid var(--color-secondary);
        }

        .persona-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .persona-item input {
            flex-grow: 1;
        }

        .btn-remove {
            background-color: var(--color-error);
            color: var(--color-white);
            border: none;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-remove:hover {
            background-color: #D32F2F;
        }

        /* Animaciones */
        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            animation: fadeIn 0.6s ease;
        }

        /* Estados de validación */
        .form-group.error input,
        .form-group.error textarea {
            border-color: var(--color-error);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .form-group.success input,
        .form-group.success textarea {
            border-color: var(--color-success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        /* ==========================================================================
        RESPONSIVE DESIGN - TABLETS
        ========================================================================== */

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
            
            /* Botón más prominente */
            button[type="submit"] {
                padding: 1.5rem 3rem;
                font-size: 1.2rem;
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
                font-size: 0.95rem;
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

        /* Mostrar/ocultar elementos según el tamaño */
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

        #agregar_campana_titulo {
            font-size: clamp(1.6rem, 4vw, 2.5rem);
            margin-bottom: 30px;
            color:  #2D3748;
            font-weight: 700;
            position: relative; /* Para la línea decorativa */
            padding-bottom: 10px;
            line-height: 1.3;
            align-items: start;
            margin-top: 30px;
        }

        #agregar_campana_titulo::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: clamp(50px, 10vw, 70px); /* Línea corta debajo del título */
            height: 4px;
            background-color: var(--color-secondary);
            border-radius: 2px;
        }
.custom-radio {
            display: none;
        }
        .check {
    cursor: pointer;
    position: relative;
    width: 22px;
    height: 22px;
    -webkit-tap-highlight-color: transparent;
    transform: translate3d(0, 0, 0);
}

.check:before {
    content: "";
    position: absolute;
    top: -15px;
    left: -15px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.check svg {
    position: relative;
    z-index: 1;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke: #c8ccd4;
    stroke-width: 1.5;
    transform: translate3d(0, 0, 0);
    transition: all 0.2s ease;
}

.check svg path {
    stroke-dasharray: 60;
    stroke-dashoffset: 0;
}

.check svg polyline {
    stroke-dasharray: 22;
    stroke-dashoffset: 66;
}

.check:hover:before {
    opacity: 1;
}

.check:hover svg {
    stroke: #3498db;
}

/* ✅ Aplica a todos los inputs con .custom-radio que estén checked */
input.custom-radio:checked + .check svg {
    stroke: #2ecc71;
}

input.custom-radio:checked + .check svg path {
    stroke-dashoffset: 60;
    transition: all 0.3s linear;
}

input.custom-radio:checked + .check svg polyline {
    stroke-dashoffset: 42;
    transition: all 0.2s linear;
    transition-delay: 0.15s;
}

/* Estilo visual cuando se selecciona el contenedor (opcional) */
.radio-option.selected {
    background-color: rgba(46, 204, 113, 0.15);
    border: 1px solid rgba(46, 204, 113, 0.3);
}
    </style>
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="agregar_campana_titulo">Agregar Campaña</h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo $feedback_tipo; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form id="campaignForm" action="agregar_campaña.php" method="POST">
                <div class="form-group">
                    <h2>Fecha de la Campaña</h2>
                    <input type="date"
                           id="fecha_campana"
                           name="fecha_campana"
                           value="<?php echo htmlspecialchars($fecha_campana ?? date('Y-m-d')); ?>"
                           required>
                </div>

                <div class="form-group">
                    <h2>Lugar de la Campaña</h2>
                    <input type="text"
                           id="lugar"
                           name="lugar"
                           placeholder="Escribe el lugar de la campaña"
                           value="<?php echo htmlspecialchars($lugar ?? ''); ?>"
                           required
                           maxlength="255">
                </div>

                <div class="form-group">
                    <h2>Título de la Campaña</h2>
                    <input type="text"
                           id="titulo_campana"
                           name="titulo_campana"
                           placeholder="Campaña hacia..."
                           value="<?php echo htmlspecialchars($titulo_campana ?? ''); ?>"
                           required
                           maxlength="255">
                </div>

                <div class="form-group">
                    <h2>Descripción de la Campaña</h2>
                    <textarea id="descripcion_campana"
                              name="descripcion_campana"
                              rows="5"
                              placeholder="Describe los detalles de la campaña..."
                              required><?php echo htmlspecialchars($descripcion_campana ?? ''); ?></textarea>
                </div>

                <div class="checkbox-container">
                    <label class="radio-option" id="option-fecha-expiracion" for="fecha_expiracion_checkbox">
                        <input type="checkbox"
                            id="fecha_expiracion_checkbox"
                            name="fecha_expiracion_checkbox"
                            class="custom-radio"
                            <?php echo ($fecha_expiracion_checked || (!isset($_POST['fecha_expiracion_checkbox']) && empty($_POST))) ? 'checked' : ''; ?>>
                        <span class="check">
                            <svg width="22px" height="22px" viewBox="0 0 18 18">
                                <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                <polyline points="1 9 7 14 15 4"></polyline>
                            </svg>
                        </span>
                        <span>Eliminar automáticamente</span>
                    </label>
                </div>


                <hr class="section-divider">
                <h2 class="section-header">Agregar Arreglos de Transporte</h2>

                <div id="carros-container">
                </div>

                <button type="button" id="add-carro-btn" class="btn-secondary">Agregar Carro</button>

                <button type="button" class="btn-secondary" id="generarPdfBtn">
                    Generar PDF de arreglos
                </button>
                
                <button type="submit" class="btn-primary">
                    Agregar Campaña
                </button>

                
            </form>
        </div>
    </main>
    
    <script type="text/javascript" src="../assets/js/jspdf.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carrosContainer = document.getElementById('carros-container');
            const addCarroBtn = document.getElementById('add-carro-btn');
            const generarPdfBtn = document.getElementById('generarPdfBtn');
            let carroIndex = 0;

            function addCarro() {
                const carroDiv = document.createElement('div');
                carroDiv.classList.add('carro-item');
                carroDiv.dataset.carroIndex = carroIndex;

                carroDiv.innerHTML = `
                    <div class="form-group">
                        <h3>Nombre del Carro</h3>
                        <input type="text"
                               name="carros[${carroIndex}][nombre_carro]"
                               placeholder="Nombre del dueño del carro"
                               required>
                    </div>
                    <div class="personas-container" id="personas-container-${carroIndex}">
                        <h4>Pasajeros</h4>
                        <div class="persona-items">
                            </div>
                        <button type="button" class="btn-secondary add-persona-btn" data-carro-index="${carroIndex}">Agregar Persona</button>
                    </div>
                    <button type="button" class="btn-remove remove-carro-btn">Eliminar Carro</button>
                `;
                
                carrosContainer.appendChild(carroDiv);

                addPersona(carroIndex);
                addPersona(carroIndex);
                addPersona(carroIndex);
                addPersona(carroIndex);
                addPersona(carroIndex);

                carroIndex++;

                carroDiv.querySelector('.add-persona-btn').addEventListener('click', function() {
                    const currentCarroIndex = this.dataset.carroIndex;
                    addPersona(currentCarroIndex);
                });

                carroDiv.querySelector('.remove-carro-btn').addEventListener('click', function() {
                    carroDiv.remove();
                    updateInputNames(); 
                });

                updateInputNames();
            }

            function addPersona(currentCarroIndex) {
                const personasContainer = document.getElementById(`personas-container-${currentCarroIndex}`).querySelector('.persona-items');
                const personaIndex = personasContainer.children.length; 

                const personaDiv = document.createElement('div');
                personaDiv.classList.add('persona-item');
                personaDiv.innerHTML = `
                    <input type="text"
                           name="carros[${currentCarroIndex}][personas][${personaIndex}][nombre]"
                           placeholder="Nombre del pasajero"
                           required>
                    <button type="button" class="btn-remove remove-persona-btn">X</button>
                `;
                personasContainer.appendChild(personaDiv);

                personaDiv.querySelector('.remove-persona-btn').addEventListener('click', function() {
                    personaDiv.remove();
                    updateInputNames(); 
                });

                updateInputNames();
            }

            function updateInputNames() {
                const allCarros = carrosContainer.querySelectorAll('.carro-item');
                allCarros.forEach((carroDiv, cIndex) => {
                    carroDiv.dataset.carroIndex = cIndex;
                    carroDiv.querySelector('input[name*="[nombre_carro]"]').name = `carros[${cIndex}][nombre_carro]`;
                    carroDiv.querySelector('.add-persona-btn').dataset.carroIndex = cIndex;

                    const personas = carroDiv.querySelectorAll('.persona-item');
                    personas.forEach((personaDiv, pIndex) => {
                        personaDiv.querySelector('input[name*="[personas]"]').name = `carros[${cIndex}][personas][${pIndex}][nombre]`;
                    });
                });
            }

            addCarroBtn.addEventListener('click', addCarro);

            addCarro(); // Para tener un carro inicial

            generarPdfBtn.addEventListener('click', function() {
                var doc = new jsPDF();
                // Obtener el título del input para el nombre del archivo
                var tituloInput = document.getElementById('titulo_campana');
                var titulo = tituloInput.value || 'Título de Campaña';
                var y = 20;

                function drawSelectiveRoundedRect(x, y, width, height, radius, fillColor, roundTop, roundBottom) {
                    var r = radius || 3;
                    if (fillColor) {
                        doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
                    }
                    if (roundTop && roundBottom) {
                        doc.rect(x + r, y, width - 2*r, height, 'F');
                        doc.rect(x, y + r, width, height - 2*r, 'F');
                        doc.circle(x + r, y + r, r, 'F');
                        doc.circle(x + width - r, y + r, r, 'F');
                        doc.circle(x + r, y + height - r, r, 'F');
                        doc.circle(x + width - r, y + height - r, r, 'F');
                    } else if (roundTop) {
                        doc.rect(x, y + r, width, height - r, 'F');
                        doc.rect(x + r, y, width - 2*r, r, 'F');
                        doc.circle(x + r, y + r, r, 'F');
                        doc.circle(x + width - r, y + r, r, 'F');
                    } else if (roundBottom) {
                        doc.rect(x, y, width, height - r, 'F');
                        doc.rect(x + r, y + height - r, width - 2*r, r, 'F');
                        doc.circle(x + r, y + height - r, r, 'F');
                        doc.circle(x + width - r, y + height - r, r, 'F');
                    } else {
                        doc.rect(x, y, width, height, 'F');
                    }
                }

                doc.setFontSize(25);
                doc.setFontStyle('bold');
                doc.setTextColor(0, 0, 0);
                var pageWidth = doc.internal.pageSize.width;
                var textWidth = doc.getStringUnitWidth(titulo) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                var centerX = (pageWidth - textWidth) / 2;
                doc.text(titulo, centerX, y);

                y += 10;
                doc.setTextColor(17, 42, 92);
                doc.setLineWidth(0.5);
                doc.setDrawColor(0, 0, 0);
                doc.line(20, y, pageWidth - 20, y);
                y += 20;

                var subtitulo = "Arreglos de transporte";
                doc.setFontSize(18);
                doc.setFontStyle('bold');

                var subtituloWidth = doc.getStringUnitWidth(subtitulo) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                var subtituloX = (pageWidth - subtituloWidth) / 2;
                doc.text(subtitulo, subtituloX, y);

                var marginLeft = 15;
                var marginRight = 15;
                var availableWidth = pageWidth - marginLeft - marginRight;
                var tableWidth = 55;
                var tableSpacing = 5;
                var totalTablesWidth = (tableWidth * 3) + (tableSpacing * 2);
                
                var x1 = marginLeft + (availableWidth - totalTablesWidth) / 2;
                var x2 = x1 + tableWidth + tableSpacing;
                var x3 = x2 + tableWidth + tableSpacing;
                
                var currentX = x1;
                y += 15;
                var rowHeight = 8;
                var textPadding = 2;
                var maxTablesPerRow = 3;
                var tableCounter = 0;
                var maxRowHeight = 0;
                
                var carros = document.querySelectorAll('[id^="personas-container-"]');

                function splitTextToFitWidth(text, maxWidth, fontSize) {
                    doc.setFontSize(fontSize || 10);
                    var lines = doc.splitTextToSize(text, maxWidth);
                    return lines;
                }

                carros.forEach(function (carroContainer, index) {
                    var inputNombreCarro = carroContainer.parentElement.querySelector('input[name^="carros["][name$="[nombre_carro]"]');
                    var nombreCarro = inputNombreCarro ? inputNombreCarro.value : 'Nombre de carro';
                    var personasInputs = carroContainer.querySelectorAll('input[name^="carros["][name*="[personas]"][name$="[nombre]"]');

                    var tableY = y;
                    var textMaxWidth = tableWidth - 8;
                    
                    var carroLines = splitTextToFitWidth(nombreCarro, textMaxWidth, 9);
                    
                    var personasLines = [];
                    personasInputs.forEach(function (input, idx) {
                        var nombrePersona = input.value || 'Nombre de persona';
                        var personaLines = splitTextToFitWidth(nombrePersona, textMaxWidth, 9);
                        for (var i = 0; i < personaLines.length; i++) {
                            personasLines.push(personaLines[i]);
                        }
                    });

                    var totalLines = carroLines.length + personasLines.length;
                    var tableHeight = Math.max(totalLines * rowHeight + (textPadding * 2) + (personasLines.length * textPadding), rowHeight * 3);
                    
                    if (tableY + tableHeight > 270) {
                        doc.addPage();
                        y = 20;
                        tableY = y;
                        currentX = x1;
                        tableCounter = 0;
                        maxRowHeight = 0;
                    }

                    var borderRadius = 4;

                    var headerHeight = carroLines.length * rowHeight + (textPadding * 2);
                    var hasPersonas = personasLines.length > 0;
                    
                    drawSelectiveRoundedRect(currentX, tableY, tableWidth, headerHeight, borderRadius, [17, 42, 92], true, !hasPersonas);

                    if (hasPersonas) {
                        var contentHeight = personasLines.length * rowHeight + (textPadding * 2) + (personasLines.length * textPadding);
                        var contentY = tableY + headerHeight;
                        drawSelectiveRoundedRect(currentX, contentY, tableWidth, contentHeight, borderRadius, [78, 102, 155], false, true);
                    }

                    doc.setFontSize(12);
                    doc.setFontStyle('bold');
                    doc.setTextColor(255, 255, 255);
                    var currentTextY = tableY + textPadding + (rowHeight / 2) + 1;
                    
                    for (var i = 0; i < carroLines.length; i++) {
                        var lineWidth = doc.getStringUnitWidth(carroLines[i]) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                        var centeredX = currentX + (tableWidth - lineWidth) / 2;
                        doc.text(carroLines[i], centeredX, currentTextY);
                        currentTextY += rowHeight;
                    }

                    if (hasPersonas) {
                        doc.setDrawColor(255, 255, 255);
                        doc.setLineWidth(0.4);
                        doc.line(currentX, tableY + headerHeight, currentX + tableWidth, tableY + headerHeight);
                    }

                    doc.setFontSize(11);
                    doc.setFontStyle('normal');
                    doc.setTextColor(255, 255, 255);
                    currentTextY += textPadding;
                    
                    for (var j = 0; j < personasLines.length; j++) {
                        currentTextY += textPadding;
                        
                        var lineWidth = doc.getStringUnitWidth(personasLines[j]) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                        var centeredX = currentX + (tableWidth - lineWidth) / 2;
                        doc.text(personasLines[j], centeredX, currentTextY);
                        currentTextY += rowHeight;
                        
                        if (j < personasLines.length - 1) {
                            doc.setDrawColor(255, 255, 255);
                            doc.setLineWidth(0.3);
                            doc.line(currentX + 4, currentTextY - (rowHeight / 2), currentX + tableWidth - 4, currentTextY - (rowHeight / 2));
                        }
                    }

                    maxRowHeight = Math.max(maxRowHeight, tableHeight);

                    tableCounter++;
                    if (tableCounter % maxTablesPerRow === 0) {
                        y += maxRowHeight + 10;
                        currentX = x1;
                        maxRowHeight = 0;
                    } else {
                        if (tableCounter % maxTablesPerRow === 1) {
                            currentX = x2;
                        } else {
                            currentX = x3;
                        }
                    }
                });

                var pdfBlob = doc.output('blob');
                var nombreArchivo = titulo.replace(/ /g, '_') + ".pdf";

                var formData = new FormData();
                formData.append('pdfFile', pdfBlob, nombreArchivo);
                formData.append('fileName', nombreArchivo);

                fetch(window.location.href, { 
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.headers.get('content-type')?.includes('application/json')) {
                        console.warn('Respuesta no JSON recibida, puede ser una redirección o HTML inesperado.');
                        return response.text().then(text => { throw new Error('Respuesta inesperada: ' + text); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert("El archivo '" + nombreArchivo + "' ha sido guardado en la ruta del proyecto y descargado.");
                        doc.save(nombreArchivo); 
                    } else {
                        alert(data.message);
                        console.error("Error del servidor:", data.message);

                        if (data.message.includes('Ya existe un archivo con este nombre')) {
                            tituloInput.focus();
                            tituloInput.select(); 
                        }
                    }
                })
                .catch(error => {
                    console.error('Error de red o al enviar el PDF:', error);
                    alert("Hubo un error de comunicación con el servidor al intentar guardar el PDF.");
                });
            });
        });
    </script>
    <link rel="stylesheet" href="../assets/css/agregar_campana.css">
</body>
</html>