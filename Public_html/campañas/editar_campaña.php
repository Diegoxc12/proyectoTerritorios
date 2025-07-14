<?php
session_start();

// Incluye tu archivo de conexión a la base de datos
include('../includes/conexion.php');

// Verificación antes de enviar cualquier salida al navegador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$rol_usuario = $_SESSION['rol_usuario'];

$campanas = [];
$mensaje_error_campanas = "";
$mensaje_bienvenida = "Editar Campañas";

try {
    $stmt = $conn->prepare("
        SELECT id_campaña, fecha_campaña, lugar, titulo_campaña, descripcion_campaña
        FROM campañas
        WHERE visible = 1 AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())
        ORDER BY fecha_campaña DESC
    ");
    $stmt->execute();
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdf_folder = __DIR__ . '/arreglos_transporte/';

    foreach ($campanas as &$campana) { 
        $nombre_pdf_esperado = str_replace(' ', '_', $campana['titulo_campaña']) . '.pdf';
        $ruta_completa_pdf = $pdf_folder . $nombre_pdf_esperado;

        if (file_exists($ruta_completa_pdf)) {

            $campana['pdf_descarga_url'] = 'arreglos_transporte/' . rawurlencode($nombre_pdf_esperado); 
            $campana['pdf_disponible'] = true;
        } else {
            $campana['pdf_descarga_url'] = '#';
            $campana['pdf_disponible'] = false;
        }
    }
    unset($campana); 

} catch (PDOException $e) {
    error_log("Error al obtener campañas: " . $e->getMessage());
    $mensaje_error_campanas = "No se pudieron cargar las campañas. Por favor, intente de nuevo más tarde. Detalles: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/styles/ver_campanas.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <title>Totoranorte - Campañas</title>
    <style>
    /* Variables CSS para una gestión de colores más fácil */
    :root {
        --color-primary: #2B6CB0; /* Un azul medio para elementos principales */
        --color-secondary: #3182CE; /* Un azul más claro para acentos */
        --color-text-dark: #2D3748; /* Texto oscuro para títulos */
        --color-text-light: #4A5568; /* Texto más claro para descripciones */
        --color-background-light: #F7FAFC; /* Fondo claro */
        --color-card-background: #FFFFFF; /* Fondo de tarjetas */
        --color-border: #E2E8F0; /* Color de borde sutil */
        --color-shadow: rgba(0, 0, 0, 0.08); /* Sombra suave */
        --color-hover-shadow: rgba(0, 0, 0, 0.15); /* Sombra al pasar el ratón */
        
        /* Variables para responsive */
        --nav-width-desktop: 355px; /* Ancho del menú para desktop */
        --main-padding: 20px;
        --card-padding: 25px;
        --border-radius: 12px;
        --transition-duration: 0.3s;
    }

    /* Estilos generales y reseteo suave */
    * {
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.6;
        color: var(--color-text-light);
    }

    body {
        margin: 0;
        padding: 0;
        background-color: var(--color-background-light);
        overflow-x: hidden;
    }

    /* Contenedor principal de la página (para el contenido excluyendo el menú) */
    .page-container {
        display: flex;
        min-height: 100vh;
        width: 100%;
        transition: margin-left var(--transition-duration) ease;
        /* Por defecto, no tiene margen para pantallas pequeñas */
        margin-left: 0; 
    }

    /* Estilo para los títulos de sección */
    h2#campanas_titulo {
        text-align: start;
        color: var(--color-text-dark);
        margin-bottom: clamp(30px, 6vw, 50px);
        font-size: clamp(1.6rem, 4vw, 2.5rem);
        font-weight: 700;
        position: relative;
        padding-bottom: 10px;
        line-height: 1.3;
    }

    h2#campanas_titulo::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: clamp(50px, 10vw, 70px);
        height: 4px;
        background-color: var(--color-secondary);
        border-radius: 2px;
    }

    /* Estilo para cada tarjeta de campaña */
    .campana {
        background-color: var(--color-card-background);
        border-radius: var(--border-radius);
        box-shadow: 0 6px 20px var(--color-shadow);
        padding: var(--card-padding);
        transition: transform var(--transition-duration) ease, box-shadow var(--transition-duration) ease;
        justify-content: space-between;
        border-top: 5px solid var(--color-secondary);
        margin-bottom: clamp(30px, 6vw, 50px);
        cursor: pointer;
    }

    .campana:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px var(--color-hover-shadow);
    }

    /* Estilos para los elementos dentro de cada campaña */
    .campana .fecha_campana {
        font-size: clamp(0.85rem, 2vw, 0.95rem);
        color: var(--color-secondary);
        margin-bottom: 8px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .campana .lugar_campana {
        font-size: clamp(0.9rem, 2.5vw, 1.05rem);
        color: var(--color-text-light);
        margin-bottom: 10px;
        font-weight: 500;
    }

    .campana .titulo_campana {
        font-size: clamp(1.2rem, 3vw, 1.6rem);
        color: var(--color-text-dark);
        margin-bottom: 10px;
        font-weight: 700;
        line-height: 1.3;
    }

    .campana .descripcion_campana {
        font-size: clamp(0.9rem, 2.5vw, 1.05rem);
        color: var(--color-text-light);
        line-height: 1.6;
        margin-bottom: 0;
        flex-grow: 1;
    }

    /* Mensajes de error o vacío */
    p.error-message, p.empty-message {
        text-align: center;
        color: #e74c3c;
        font-size: clamp(0.9rem, 2.5vw, 1rem);
        margin-top: 20px;
        padding: 15px;
        background-color: #fce8e8;
        border-radius: 8px;
        border: 1px solid #e74c3c;
    }

    p.empty-message {
        color: var(--color-text-light);
        background-color: var(--color-background-light);
        border: 1px solid var(--color-border);
    }

    /* Opcional: Estilos para el overlay del menú si lo usas */
    #menu_overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
        opacity: 0;
        transition: opacity var(--transition-duration) ease;
    }

    #menu_overlay.visible {
        display: block;
        opacity: 1;
    }

    /* MAIN content */
    main {
        flex-grow: 1;
        width: 100%;
        max-width: 1200px !important; /* Ancho máximo para el contenido */
        margin: 0 auto; /* Permite centrar el contenido dentro del espacio disponible */
        padding: var(--main-padding);
        box-sizing: border-box;
    }

    /* Estilos para el botón de descarga */
    .download-btn {
        display: inline-block;
        background-color: var(--color-primary);
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background-color var(--transition-duration) ease, transform var(--transition-duration) ease;
        margin-top: 15px; /* Espacio superior para separar del texto */
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
    }

    .download-btn:hover {
        background-color: var(--color-secondary);
        transform: translateY(-2px);
    }

    .download-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }

    /*
    --- Media Queries para Responsividad ---
    */

    /* Pantallas muy pequeñas (optimización extra) */
    @media (max-width: 360px) {
        :root {
            --main-padding: 12px;
            --card-padding: 15px;
        }
        
        main {
            padding: var(--main-padding);
        }

        .campana {
            padding: var(--card-padding);
        }
    }

    /* Móviles pequeños */
    @media (max-width: 480px) {
        :root {
            --main-padding: 15px;
            --card-padding: 18px;
        }
        
        main {
            padding: var(--main-padding);
        }

        .campana {
            padding: var(--card-padding);
        }

        h2#campanas_titulo {
            font-size: 1.8em; /* Mantenemos este valor específico para este breakpoint */
        }
        .content-grid { /* Si aplicas un grid a las campañas */
            grid-template-columns: 1fr;
        }
    }

    /* Móviles grandes */
    @media (min-width: 481px) and (max-width: 767px) {
        :root {
            --main-padding: 18px;
            --card-padding: 22px;
        }
        
        main {
            padding: var(--main-padding);
        }

        .content-grid { /* Si aplicas un grid a las campañas */
            gap: 25px;
        }

        .campana {
            padding: var(--card-padding);
        }
    }

    /* Tablet y pantallas medianas */
    @media (min-width: 768px) and (max-width: 1023px) {
        :root {
            --main-padding: 25px;
            --card-padding: 28px;
        }
        
        main {
            padding: var(--main-padding);
            margin: 20px auto;
        }

        .content-grid { /* Si aplicas un grid a las campañas */
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .campana {
            padding: var(--card-padding);
        }

        h2#campanas_titulo {
            font-size: 2em; /* Mantenemos este valor específico para este breakpoint */
            margin-bottom: 30px; /* Mantenemos este valor específico para este breakpoint */
        }
    }

    /* Desktop y pantallas grandes */
    @media (min-width: 1024px) and (min-height: 600px) {
        :root {
            --main-padding: 30px;
            --card-padding: 30px;
        }
        
        /* Menú siempre visible a la izquierda. ASUME que tienes un elemento <nav> o similar para tu menú */
        nav { 
            position: fixed;
            top: 0;
            left: 0;
            width: var(--nav-width-desktop); /* Usa el ancho definido en la variable */
            height: 100%;
            background-color: #f8f9fa; /* Ejemplo de color de fondo del menú */
            box-shadow: 2px 0 10px var(--color-shadow);
            z-index: 999; /* Asegura que el menú esté por encima del contenido */
            /* Agrega estilos adicionales para tu menú aquí */
        }

        /* Ajustar el contenedor principal para que empiece después del nav */
        .page-container {
            margin-left: var(--nav-width-desktop); /* Desplaza el contenido a la derecha del menú */
            width: calc(100% - var(--nav-width-desktop)); /* Ajusta el ancho para el espacio restante */
            /* Quita display: flex; si main ya lo maneja bien con margin: 0 auto; */
        }

        main {
            padding: var(--main-padding);
            margin: 30px auto; /* Esto centrará el 'main' dentro del espacio disponible del 'page-container' */
            max-width: calc(1200px - var(--nav-width-desktop)); /* Ajusta el max-width si es necesario */
        }

        .content-grid { /* Si aplicas un grid a las campañas */
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 35px;
        }

        .campana {
            padding: var(--card-padding);
        }

        /* Ocultar overlay en desktop */
        #menu_overlay {
            display: none !important;
        }
    }

    /* Pantallas muy grandes */
    @media (min-width: 1440px) {
        :root {
            --main-padding: 40px;
            --card-padding: 35px;
        }
        

        .content-grid { /* Si aplicas un grid a las campañas */
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
        }

        .campana {
            padding: var(--card-padding);
        }
    }

    /* Pantallas ultra anchas */
    @media (min-width: 1920px) {

        
        .content-grid { /* Si aplicas un grid a las campañas */
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        }
    }

    /* Orientación landscape en móviles */
    @media (max-width: 896px) and (orientation: landscape) {
        /* Mantén tus estilos específicos aquí si los tienes */
    }

    /* Accesibilidad */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* Mejoras para pantallas táctiles */
    @media (hover: none) and (pointer: coarse) {
        .campana:hover {
            transform: none;
        }
        
        .campana:active {
            transform: scale(0.98);
        }
    }
</style>
</head>
<body>
    <div id="menu_overlay"></div>

    <?php
    include('../includes/header_menu.php');
    ?>

    <div class="page-container">
        <main>
            <section id="campanas" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
                <h2 id="campanas_titulo">Presione en una campaña para editar: </h2>
                <?php if (!empty($mensaje_error_campanas)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($mensaje_error_campanas); ?></p>
                <?php endif; ?>

                <div class="content-grid">
                    <?php if (count($campanas) > 0 ): ?>
                        <?php foreach ($campanas as $campana): ?>
                            <div class="campana" 
                                 data-aos="fade-up" 
                                 data-aos-delay="100"
                                 data-id="<?php echo htmlspecialchars($campana['id_campaña']); ?>"
                                 onclick="editarCampana(<?php echo htmlspecialchars($campana['id_campaña']); ?>)">
                                <h3 class="fecha_campana">Fecha PARA IR: <?php
                                        setlocale(LC_TIME, 'es_ES', 'es_ES.utf8', 'esp', 'spanish');
                                        echo htmlspecialchars(strftime('%d %B %Y', strtotime($campana['fecha_campaña'])));
                                    ?></h3>
                                <p class="lugar_campana">Lugar de la campaña: <?php echo htmlspecialchars($campana['lugar']); ?></p>
                                <h2 class="titulo_campana"><?php echo htmlspecialchars($campana['titulo_campaña']); ?></h2>
                                <p class="descripcion_campana"><?php echo nl2br(htmlspecialchars($campana['descripcion_campaña'])); ?></p>
                                
                                <?php if ($campana['pdf_disponible']): ?>
                                    <button class="download-btn">
                                        <a href="<?php echo htmlspecialchars($campana['pdf_descarga_url']); ?>" class="button-content" download>Descargar arreglos de transporte</a>
                                    </button>
                                <?php else: ?>
                                    
                                    <button class="download-btn" disabled title="No hay archivo de arreglos disponible para esta campaña.">
                                        No hay arreglos disponibles
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">No hay campañas disponibles.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 800,
            easing: 'ease-out'
        });
        
        function editarCampana(idCampana) {
            window.location.href = 'editar_campaña_individual.php?id=' + idCampana;
        }

        function editarCampanaPost(idCampana) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'editar_campana.php';
            
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id_campana';
            input.value = idCampana;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <script src="../assets/js/menu.js"></script>
</body>
</html>