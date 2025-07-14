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
    <style>
        /* Aquí se mantienen los estilos que tenías en el código anterior,
           adaptados para este nuevo formulario. Si el archivo 
           agregar_anuncio.css ya contiene la mayoría de estos estilos, 
           puedes considerar mover esta parte a un archivo CSS externo 
           para evitar duplicación, por ejemplo, `agregar_hermano.css` 
           o `global_forms.css`. */

        :root {
            --color-primary: #0F1435;
            --color-secondary: #6C7EF4;
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
            box-sizing: border-box;
            color: var(--color-text);
        }

        main {
            margin: 15px;
            padding-bottom: 40px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 80px);
            display: flex; /* Añadido para centrar el contenido si es necesario */
            flex-direction: column; /* Añadido para apilar el título y el formulario */
            align-items: center; /* Añadido para centrar horizontalmente */
            justify-content: flex-start; /* Alinea el contenido al principio verticalmente */
        }
        .feedback-message {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideInDown 0.5s ease;
            width: 100%;
            max-width: 100%;
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
            max-width: 500px; /* Ancho máximo para el formulario */
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
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

        .form-group h2 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group h2::before {
            content: '';
            width: 4px;
            height: 20px;
            background-color: #0F1435;
            border-radius: 2px;
        }

        /* Estilos para inputs */
        input[type="text"] {
            padding: 0.875rem 1rem;
            border: 2px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--color-white);
            color: var(--color-text);
            font-family: inherit;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--color-border-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }

        /* Placeholder styles */
        input::placeholder {
            color: var(--color-text-light);
            opacity: 1;
        }

        /* Botón de envío */
        button[type="submit"] {
            background-color: #0F1435;
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
            margin-top: 1rem;
            box-shadow: var(--shadow-md);
        }

        button[type="submit"]:hover {
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        button[type="submit"]:hover::before {
            left: 100%;
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
        .form-group.error input {
            border-color: var(--color-error);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .form-group.success input {
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
                max-width: 600px; /* Ancho máximo para el formulario en tablets */
                width: 100%;
            }
            
            form {
                gap: 2rem;
            }
            
            .form-group h2 {
                font-size: 1.125rem;
            }
            
            input[type="text"] {
                padding: 1rem 1.25rem;
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
                margin-left: 355px; /* Ajusta este valor si tu menú lateral cambia de ancho */
                margin-right: 50px;
                padding-bottom: 60px;
                min-height: calc(100vh - 100px);
                width: calc(100% - 405px); /* Ajusta este valor en relación al margin-left */
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            main h1 {
                font-size: 3rem;
                margin-bottom: 3.5rem;
            }
            
            .form-container {
                padding: 3rem;
                border-radius: var(--radius-xl);
                max-width: 700px; /* Ancho máximo para el formulario en desktop */
                width: 100%;
            }
            
            form {
                gap: 2.5rem;
            }
            
            .form-group h2 {
                font-size: 1.25rem;
            }
            
            /* Mejores efectos hover en desktop */
            input[type="text"]:hover {
                border-color: var(--color-secondary);
                box-shadow: var(--shadow-sm);
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
            }
            
            .form-container {
                padding: 4rem;
                max-width: 800px;
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
            
            .form-group h2 {
                font-size: 0.95rem;
            }
            
            input[type="text"] {
                padding: 0.75rem;
                font-size: 1rem;
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

        #agregar_hermano {
            font-size: clamp(1.6rem, 4vw, 2.5rem);
            margin-bottom: 30px;
            color: #2D3748;
            font-weight: 700;
            position: relative; /* Para la línea decorativa */
            padding-bottom: 10px;
            line-height: 1.3;
            align-self: flex-start; /* Alinea el título a la izquierda dentro de main */
            margin-top: 30px;
        }

        #agregar_hermano::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: clamp(50px, 10vw, 70px); /* Línea corta debajo del título */
            height: 4px;
            background-color: var(--color-secondary);
            border-radius: 2px;
        }
    </style>
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