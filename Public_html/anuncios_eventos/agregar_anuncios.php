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
    <style>
        :root {
            --color-primary: #0F1435;
            --color-secondary: #3182CE;
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
            max-width: 1200px;
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

        /* Botón de envío */
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
            margin-top: 1rem;
            box-shadow: var(--shadow-md);
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            background-color: var(--color-secondary);
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
            
            .form-group h2 {
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
            
            .form-group h2 {
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
            
            .form-group h2 {
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

        #agregar_anuncio {
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

            #agregar_anuncio::after {
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