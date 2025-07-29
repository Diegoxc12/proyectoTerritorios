<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$mensaje_bienvenida = $mensaje_bienvenida ?? "<span class=\"typewriter thick\"></span>";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header y Menú de Navegación</title>
    <style>
            :root {
        --color-primary: #2B6CB0; 
        --color-secondary: #3182CE;
        --color-text-dark: #2D3748; 
        --color-text-light: #4A5568;
        --color-background-light: #F7FAFC;
        --color-card-background: #FFFFFF;
        --color-border: #E2E8F0;
        --color-shadow: rgba(0, 0, 0, 0.12); 
        --color-menu-bg: #0F1435; 
        --color-menu-border: #6C7EF4; 
        
        /* Variables para responsive */
        --nav-width-mobile: 280px;
        --nav-width-tablet: 300px;
        --nav-width-desktop: 300px;
        --header-height: 60px;
    }

    * {
        box-sizing: border-box;
    }
     

    body {
        margin: 0;
        padding: 0;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        background-color: var(--color-background-light);
        color: var(--color-text-light);
        overflow-x: hidden;
    }

    header {
        position: fixed;
        top: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 95%;
        height: var(--header-height);
        background-color: var(--color-menu-bg);
        border-bottom: 1px solid #252e44;
        display: flex;
        align-items: center;
        padding: 0 clamp(10px, 3vw, 20px);
        z-index: 980;
        transition: left 0.3s ease, width 0.3s ease;
        box-shadow: 0 2px 8px var(--color-shadow);
        border-radius: 15px;
        padding-left: 20px;
        padding-right: 20px;
    }


    .button-content {
        position: relative;
        z-index: 1;
        color: white;
        text-decoration: none;
    }

    #logo_header {
        width: clamp(25px, 5vw, 30px);
        height: auto;
        cursor: pointer;
        display: block;
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }

    #logo_header:active {
        transform: scale(0.95);
    }

    #header_bienvenida {
        display: flex;
        align-items: end;
        gap: clamp(8px, 2vw, 12px);
        font-size: clamp(16px, 3.5vw, 18px);
        font-weight: 500;
        margin: 0;
        color: #ffffff;
        flex-grow: 1;
        justify-content: end;
    }

    #logoJW_header {
        width: clamp(25px, 5vw, 30px);
        height: auto;
        flex-shrink: 0;
    }

    /* OVERLAY */
    #menu_overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(15, 20, 53, 0.6);
        z-index: 990;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(4px);
    }

    #menu_overlay.show {
        display: block;
        opacity: 1;
    }

    /* NAV - Menú de navegación */
    nav {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--nav-width-mobile);
        height: 100vh;
        background-color: var(--color-menu-bg);
        padding: clamp(10px, 3vw, 15px);
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        z-index: 999;
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        opacity: 0;
        pointer-events: none;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
    }

    nav.show {
        transform: translateX(0);
        opacity: 1;
        pointer-events: auto;
    }

    nav h2#menu_bienvenida {
        font-weight: 400;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: clamp(8px, 2vw, 10px);
        margin-top: 5px;
        margin-bottom: clamp(20px, 5vw, 30px);
        color: #fff;
        padding-bottom: 10px;
        border-bottom: solid 1px #6C7EF4;
        flex-shrink: 0;
    }

    #logo_menu {
        width: 35px;
        height: auto;
        flex-shrink: 0;
    }

    nav ul {
        list-style: none;
        padding: 10px;
        padding-top: 15px;
        margin: 0;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: clamp(20px, 5vw, 30px);
    }

    nav ul li a {
        text-decoration: none;
        color: #fff;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: clamp(8px, 2vw, 10px) clamp(12px, 3vw, 15px);
        border-radius: 5px;
        transition: background-color 0.3s ease;
        font-size: 1.2rem;
        word-wrap: break-word;
        hyphens: auto;
    }

    nav ul li a:hover {
        background-color: rgba(108, 126, 244, 0.2);
    }

    /* Estilos para el menú desplegable */
    .dropdown {
        position: relative;
    }

    .dropdown-content {
        display: none;
        padding: 5px 0;
        border-radius: 5px;
        margin-top: 10px;
        margin-left: clamp(10px, 3vw, 15px);
        list-style: none;
    }

    .dropdown.active .dropdown-content {
        display: block;
    }

    .dropdown-content ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .dropdown-content a {
        padding: clamp(6px, 2vw, 8px) clamp(12px, 3vw, 15px);
        font-size: 1rem;
        font-weight: normal;
        border-radius: 0;
        justify-content: flex-start;
        transition: background-color 0.2s ease;
        word-wrap: break-word;
        hyphens: auto;
    }

    .dropdown-content a:hover {
        background-color: rgba(108, 126, 244, 0.2);
        transform: none;
    }

    /* Icono de flecha */
    .dropdown > .dropdown-toggle::after {
        content: '\25BC';
        font-size: clamp(8px, 2vw, 10px);
        transition: transform 0.3s ease;
        margin-left: clamp(8px, 2vw, 10px);
        flex-shrink: 0;
    }

    .dropdown.active > .dropdown-toggle::after {
        transform: rotate(180deg);
    }

    /* SECCIÓN DE BIENVENIDA CON EFECTO DE ESCRITURA */
    #bienvenida-header {
        text-align: center;
        font-weight: lighter;
        color: #000000;
        padding-top: calc(var(--header-height) + 20px);
        padding-bottom: clamp(20px, 5vw, 30px);
        padding-left: clamp(15px, 3vw, 20px);
        padding-right: clamp(15px, 3vw, 20px);
        border-bottom: solid 1px #E6E5E5;
        transition: margin-left 0.3s ease;
    }

    #bienvenida-header p {
        color:rgb(100, 100, 100);
        padding-top: clamp(8px, 2vw, 10px);
        margin-left: auto;
        margin-right: auto;
        width: min(90%, 800px);
        font-size: 1.7rem;
        line-height: 1.5;
        font-weight: 500;
    }

    #titulo {
        font-size: clamp(32px, 8vw, 48px);
        margin: clamp(10px, 2vw, 20px) 0;
        line-height: 1.2;
        word-wrap: break-word;
        hyphens: auto;
        margin-top: 20px;
    }

    #punto {
        color: white;
    }

    .typewriter::after {
        animation: blink 1s linear infinite;
        font-weight: bold;
    }

    @keyframes blink {
        0%, 50% { opacity: 1; }
        51%, 100% { opacity: 0; }
    }

    /* --- Media Queries --- */

    /* Pantallas muy pequeñas (móviles en vertical) */
    @media (max-width: 360px) {
        nav {
            width: 85vw;
            max-width: 280px;
        }
        
        #bienvenida-header {
            padding-top: calc(var(--header-height) + 15px);
        }
        
        #titulo {
            font-size: clamp(24px, 7vw, 32px);
            padding-top: 20px;
        }
        
        #bienvenida-header p {
            width: 95%;
            font-size: 1.5rem;
        }
    }

    /* Móviles pequeños */
    @media (max-width: 480px) {
        nav {
            width: 80vw;
            max-width: var(--nav-width-mobile);
        }
        
        #bienvenida-header {
            padding-top: calc(var(--header-height) + 20px);
        }
        
        #titulo {
            font-size: 2.5rem;
            padding-top: 25px;
        }
        
        #bienvenida-header p {
            width: 92%;
            font-size: 1.5rem;
        }
    }

    /* Móviles grandes y tablets pequeñas */
    @media (min-width: 481px) and (max-width: 767px) {
        nav {
            width: var(--nav-width-mobile);
        }
        
        #titulo {
            font-size: clamp(36px, 7vw, 48px);
        }
        
        #bienvenida-header p {
            width: 85%;
            font-size: clamp(20px, 4vw, 24px);
        }
    }

    /* Tablets */
    @media (min-width: 768px) and (max-width: 1023px) {
        nav {
            width: var(--nav-width-tablet);
        }
        
        #titulo {
            font-size: clamp(40px, 6vw, 52px);
        }
        
        #bienvenida-header p {
            width: 75%;
            font-size: clamp(22px, 3.5vw, 26px);
        }
    }

    /* Desktop y pantallas grandes */
    @media (min-width: 1024px) {
        header {
            display: none;
        }

        nav {
            position: fixed;
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
            width: var(--nav-width-desktop);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        #bienvenida-header {
            margin-left: var(--nav-width-desktop);
            padding-top: clamp(40px, 5vw, 60px);
        }
        
        #titulo {
            font-size: clamp(48px, 5vw, 64px);
        }
        
        #bienvenida-header p {
            width: min(80%, 900px);
            font-size: clamp(24px, 2.5vw, 28px);
        }
    }

    /* Pantallas muy grandes */
    @media (min-width: 1440px) {
        nav {
            width: 320px;
        }
        
        #bienvenida-header {
            margin-left: 320px;
        }
        
        #titulo {
            font-size: clamp(56px, 4vw, 72px);
        }
        
        #bienvenida-header p {
            width: min(70%, 1000px);
            font-size: clamp(26px, 2vw, 30px);
        }
    }

    /* Pantallas 4K y superiores */
    @media (min-width: 2560px) {
        nav {
            width: 350px;
        }
        
        #bienvenida-header {
            margin-left: 350px;
        }
        
        #titulo {
            font-size: clamp(64px, 3vw, 80px);
        }
        
        #bienvenida-header p {
            width: min(60%, 1200px);
            font-size: clamp(28px, 1.5vw, 32px);
        }
    }

    /* Orientación landscape en móviles */
    @media (max-width: 896px) and (orientation: landscape) {
        nav {
            width: 60vw;
            max-width: 320px;
        }
        
        #bienvenida-header {
            padding-top: calc(var(--header-height) + 10px);
            padding-bottom: 20px;
        }
        
        #titulo {
            font-size: clamp(24px, 5vw, 36px);
        }
        
        #bienvenida-header p {
            font-size: clamp(14px, 3vw, 20px);
        }
    }

    /* Accesibilidad y mejoras adicionales */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* Mejoras para pantallas táctiles */
    @media (hover: none) and (pointer: coarse) {
        nav ul li a,
        .dropdown-content a {
            padding: clamp(12px, 3vw, 15px);
            min-height: 44px;
            display: flex;
            align-items: center;
        }
        

    }

    /* Soporte para fold screens */
    @media (max-width: 320px) {
        nav {
            width: 90vw;
            max-width: 280px;
        }
        
        #bienvenida-header p {
            width: 98%;
            font-size: clamp(14px, 4vw, 18px);
        }
        
        #titulo {
            font-size: clamp(20px, 6vw, 28px);
        }
    }
    </style>
</head>
<body>
    <div id="menu_overlay"></div>

    <header>
        <img id="logo_header" src="../assets/img/Menu_icon1.png" alt="Logo menú">
        <h2 id="header_bienvenida">
            Totoracocha
        </h2>             
    </header>

    <div id="bienvenida-header">
        <h1 id="titulo">Totoranorte.com</h1>
        <p>
           <span id="punto">.</span> <?= $mensaje_bienvenida ?>
        </p>
    </div>

    <nav>
        <h2 id="menu_bienvenida">
            Menu
        </h2>
        <ul>
            <li><a href="../dashboard.php">Inicio</a></li>

            <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'publicador'): ?>
                <li>
                    <a href="../campañas/ver_campaña.php">Campañas</a>
                </li>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" >Campañas</a>
                <div class="dropdown-content">
                    <ul>
                        <li><a href="../campañas/ver_campaña.php">Ver Campañas</a></li>
                        <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
                            <li><a href="../campañas/agregar_campaña.php">Agregar Campañas</a></li>
                            <li><a href="../campañas/editar_campaña.php">Editar Campañas</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">Hermanos</a>
                <div class="dropdown-content">
                    <ul>
                        <li><a href="../usuarios/ver_hermanos.php">Ver hermanos</a></li>
                        <li><a href="../usuarios/agregar_hermanos.php">Agregar hermano</a></li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">Anuncios y Eventos</a>
                <div class="dropdown-content">
                    <ul>
                        <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
                            <li><a href="../anuncios_eventos/agregar_anuncios.php">Agregar Anuncio</a></li>
                            <li><a href="../anuncios_eventos/agregar_evento.php">Agregar Evento</a></li>
                            <li><a href="../anuncios_eventos/ver_anuncios.php">Editar anuncios y eventos</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <li>
                <a href="../predicacion/grupos_predicacion.php">Grupos de Predicación</a>
            </li>

            <li>
                <a href="../reuniones/reuniones.php">Reuniones</a>
            </li>
            
            <li>
                <a href="../predicacion/territorios.php">Territorios</a>
            </li>

            <li><a href="../logout.php">Salir</a></li>
        </ul>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('logo_header');
            const nav = document.querySelector('nav');
            const overlay = document.getElementById('menu_overlay');
            // const contenido = document.getElementById('contenido'); // Si tienes un elemento 'contenido' para aplicar blur

            function abrirMenu() {
                nav.classList.add('show');
                overlay.classList.add('show');
                // if (contenido) contenido.classList.add('blur');
                document.body.style.overflow = 'hidden'; // Evita el scroll del fondo
            }

            function cerrarMenu() {
                nav.classList.remove('show');
                overlay.classList.remove('show');
                // if (contenido) contenido.classList.remove('blur');
                document.body.style.overflow = ''; // Restaura el scroll del fondo

                // Cierra todos los submenús activos al cerrar el menú principal
                document.querySelectorAll('.dropdown.active').forEach(openDropdown => {
                    openDropdown.classList.remove('active');
                });
            }

            if (menuToggle && nav && overlay) {
                menuToggle.addEventListener('click', (e) => {
                    e.stopPropagation(); // Evita que el clic se propague al documento
                    if (!nav.classList.contains('show')) {
                        abrirMenu();
                    } else {
                        cerrarMenu();
                    }
                });

                overlay.addEventListener('click', () => {
                    cerrarMenu();
                });

                // Cierra el menú si se hace clic en un enlace del menú que no es un dropdown-toggle
                nav.querySelectorAll('a').forEach(link => {
                    if (!link.classList.contains('dropdown-toggle')) {
                        link.addEventListener('click', () => {
                            cerrarMenu();
                        });
                    }
                });
            }

            // Lógica para los menús desplegables
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault(); // Previene la acción por defecto del enlace '#'
                    e.stopPropagation(); // Evita que el clic se propague al documento y cierre otros dropdowns

                    const parentDropdown = this.closest('.dropdown');
                    
                    // Cierra otros desplegables si están abiertos
                    document.querySelectorAll('.dropdown.active').forEach(openDropdown => {
                        if (openDropdown !== parentDropdown) {
                            openDropdown.classList.remove('active');
                        }
                    });

                    // Alterna la clase 'active' en el padre del desplegable
                    parentDropdown.classList.toggle('active');
                });
            });

            // Cierra los menús desplegables si se hace clic fuera de ellos (siempre)
            document.addEventListener('click', function(e) {
                dropdownToggles.forEach(toggle => {
                    const parentDropdown = toggle.closest('.dropdown');
                    // Si el clic no fue dentro de este dropdown y este dropdown está activo
                    if (parentDropdown && parentDropdown.classList.contains('active') && !parentDropdown.contains(e.target)) {
                        parentDropdown.classList.remove('active');
                    }
                });
            });
            
            // Typewriter effect para el mensaje de bienvenida
            const typewriterElement = document.querySelector('.typewriter');
            if (typewriterElement) {
                const texts = [
                    "Anuncios",
                    "Eventos",
                    "Grupos de predicación",
                    "Territorios",
                    "Campañas"
                ];
                
                let currentTextIndex = 0;
                let currentCharIndex = 0;
                let isDeleting = false;
                let isWaiting = false;
                
                const typeSpeed = 75;         // Velocidad de escritura (ms)
                const deleteSpeed = 50;       // Velocidad de borrado (ms)
                const waitTime = 1000;        // Tiempo de espera entre textos (ms)
                const waitAfterComplete = 1500; // Tiempo después de completar texto
                
                function typeEffect() {
                    const currentText = texts[currentTextIndex];
                    
                    if (isWaiting) {
                        setTimeout(() => {
                            isWaiting = false;
                            typeEffect();
                        }, waitTime);
                        return;
                    }
                    
                    if (!isDeleting) {
                        // Escribiendo
                        typewriterElement.textContent = currentText.slice(0, currentCharIndex + 1);
                        currentCharIndex++;
                        
                        if (currentCharIndex === currentText.length) {
                            // Terminó de escribir, esperar antes de borrar
                            isWaiting = true;
                            setTimeout(() => {
                                isWaiting = false;
                                isDeleting = true;
                                typeEffect();
                            }, waitAfterComplete);
                            return;
                        }
                        
                        setTimeout(typeEffect, typeSpeed);
                    } else {
                        // Borrando
                        typewriterElement.textContent = currentText.slice(0, currentCharIndex - 1);
                        currentCharIndex--;
                        
                        if (currentCharIndex === 0) {
                            // Terminó de borrar, ir al siguiente texto
                            isDeleting = false;
                            currentTextIndex = (currentTextIndex + 1) % texts.length;
                            isWaiting = true;
                        }
                        
                        setTimeout(typeEffect, deleteSpeed);
                    }
                }
                
                typeEffect(); 
            }
        });
    </script>
</body>
</html>

