<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$mensaje_bienvenida = $mensaje_bienvenida ?? "<span class=\"typewriter thick\"></span>";
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header y Menú de Navegación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/styles/header_menu.css">
</head>

<body>
    <div id="menu_overlay"></div>

    <header>
        <img id="logo_header" src="../assets/img/menu_icon1.png" alt="Logo menú">
        <h2 id="header_bienvenida">
            Totoracocha Norte
        </h2>
    </header>

    <div id="bienvenida-header">
        <h1 id="titulo">Totoranorte.com</h1>
        <p>
            <span id="punto">.</span> <?= $mensaje_bienvenida ?>
        </p>
    </div>

    <nav>
        <h2 id="menu_bienvenida">Menu</h2>
        <ul>
            <li class="<?= $pagina_actual == 'dashboard.php' ? 'active' : '' ?>">
                <a href="../dashboard.php">
                    <i class="fas fa-home"></i> Inicio
                </a>
            </li>

            <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'publicador'): ?>
                <li class="<?= $pagina_actual == 'ver_campaña.php' ? 'active' : '' ?>">
                    <a href="../campañas/ver_campaña.php">
                        <i class="fas fa-bullhorn"></i> Campañas
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isset($_SESSION['rol_usuario']) && $_SESSION['rol_usuario'] === 'anciano'): ?>
                <li class="dropdown <?= in_array($pagina_actual, ['ver_campaña.php', 'agregar_campaña.php', 'editar_campaña.php']) ? 'active' : '' ?>">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-bullhorn"></i> Campañas
                    </a>
                    <div class="dropdown-content">
                        <ul>
                            <li><a href="../campañas/ver_campaña.php"><i class="fas fa-eye"></i> Ver Campañas</a></li>
                            <li><a href="../campañas/agregar_campaña.php"><i class="fas fa-plus"></i> Agregar Campañas</a></li>
                            <li><a href="../campañas/editar_campaña.php"><i class="fas fa-edit"></i> Editar Campañas</a></li>
                        </ul>
                    </div>
                </li>

                <li class="dropdown <?= in_array($pagina_actual, ['ver_hermanos.php', 'agregar_hermanos.php']) ? 'active' : '' ?>">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-users"></i> Hermanos
                    </a>
                    <div class="dropdown-content">
                        <ul>
                            <li><a href="../usuarios/ver_hermanos.php"><i class="fas fa-eye"></i> Ver Hermanos</a></li>
                            <li><a href="../usuarios/agregar_hermanos.php"><i class="fas fa-user-plus"></i> Agregar Hermano</a></li>
                        </ul>
                    </div>
                </li>

                <li class="dropdown <?= in_array($pagina_actual, ['agregar_anuncios.php', 'agregar_evento.php', 'ver_anuncios.php']) ? 'active' : '' ?>">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-calendar-alt"></i> Anuncios y Eventos
                    </a>
                    <div class="dropdown-content">
                        <ul>
                            <li><a href="../anuncios_eventos/agregar_anuncios.php"><i class="fas fa-plus-circle"></i> Agregar Anuncio</a></li>
                            <li><a href="../anuncios_eventos/agregar_evento.php"><i class="fas fa-calendar-plus"></i> Agregar Evento</a></li>
                            <li><a href="../anuncios_eventos/ver_anuncios.php"><i class="fas fa-edit"></i> Editar Anuncios y Eventos</a></li>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>

            <li class="<?= $pagina_actual == 'grupos_predicacion.php' ? 'active' : '' ?>">
                <a href="../predicacion/grupos_predicacion.php">
                    <i class="fas fa-users"></i> Grupos de Predicación
                </a>
            </li>

            <li class="<?= $pagina_actual == 'reuniones.php' ? 'active' : '' ?>">
                <a href="../reuniones/reuniones.php">
                    <i class="fas fa-handshake"></i> Reuniones
                </a>
            </li>

            <li class="<?= $pagina_actual == 'territorios.php' ? 'active' : '' ?>">
                <a href="../predicacion/territorios.php">
                    <i class="fas fa-map-marked-alt"></i> Territorios
                </a>
            </li>

            <li>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </li>
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

                const typeSpeed = 75; // Velocidad de escritura (ms)
                const deleteSpeed = 50; // Velocidad de borrado (ms)
                const waitTime = 1000; // Tiempo de espera entre textos (ms)
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