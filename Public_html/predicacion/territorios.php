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
$mensaje_bienvenida = "Territorios";



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Territorios</title>
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
    }

    .btn-grupos {
        background-color: var(--color-card-background);
        border-radius: var(--border-radius);
        box-shadow: 0 6px 20px var(--color-shadow);
        padding: 6%;
        transition: transform var(--transition-duration) ease, box-shadow var(--transition-duration) ease;
        justify-content: space-between;
        border-top: 5px solid var(--color-secondary);
        border-right: none;
        border-bottom: none;
        border-left: none;
        margin-bottom: clamp(30px, 6vw, 50px);
        width: 100%;
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        font-size: 2rem;
        color: var(--color-secondary);
    }

    .btn-grupos:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px var(--color-hover-shadow);
        cursor: pointer;
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

        .lunes {
            padding: 6%;
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

        .btn-grupos {
            padding: 4%;
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

        .lunes {
            padding: 5%;
        }
    }

    /* Pantallas ultra anchas */
    @media (min-width: 1920px) {

        
        .content-grid { /* Si aplicas un grid a las campañas */
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        }

        .lunes {
            padding: 2%;
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

    .download-btn {
        padding: 12px 20px;
        background-color: var(--color-primary);
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color var(--transition-duration) ease, transform var(--transition-duration) ease;
        margin-top: 15px;
        margin-bottom: 20px;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px; /* Espacio entre ícono y texto */
        width: 100%;

    }

    .download-btn:hover {
        background-color: var(--color-secondary);
        transform: translateY(-2px);
    }

    .download-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }

    
</style>
</head>
<body>
    <div id="menu_overlay"></div>

    <?php include('../includes/header_menu.php');?>

    

    <div class="page-container">
        <main>
            <div id="botones">
                <button class="download-btn" data-aos="zoom-in" data-aos-delay="200">
                <img src="../assets/img/download_icon.png" alt="" style="height: 20px; vertical-align: middle; margin-right: 8px;"><a href="./descargar_reporte_t.php" style="text-decoration: none; color: inherit;">Descargar reporte de Territorios</a>
                
                </button>

        </div>
            <div class="territorio-button-container">
        <?php
        for ($i = 1; $i <= 84; $i++) {
            echo '<div class="content-grid">';
            echo '<button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" data-id-imagen="' . $i . '">' . $i . '</button>';

            echo '</div>';
        }
        ?>
            </div>
        </main>
    </div>

    
    
    <script>
        const territorioLinkButtons = document.querySelectorAll('.btn-grupos');
        territorioLinkButtons.forEach(button => {
            button.addEventListener('click', () => {
                const idImagen = button.dataset.idImagen;
                if (idImagen) {
                    window.location.href = `territorio_asignado.php?id_imagen=${idImagen}`;
                } else {
                    console.error('ID de imagen no encontrado en el botón.');
                }
            });
        });
    </script>
    
</body>

</html>

