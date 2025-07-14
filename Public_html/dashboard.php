<?php
session_start();

// Incluye tu archivo de conexión a la base de datos
// Asumo que este archivo define y crea la variable $conn
include('./includes/conexion.php');

// Verificación antes de enviar cualquier salida al navegador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$rol_usuario = $_SESSION['rol_usuario'];


// --- Lógica para obtener los anuncios de la base de datos ---
$anuncios = []; // Inicializa un array para guardar los anuncios
$mensaje_error_anuncios = ""; // Para mensajes de error específicos de anuncios

try {
    $stmt = $conn->prepare("
        SELECT fecha_anuncio, titulo_anuncio, descripcion_anuncio
        FROM anuncios
        WHERE visible = 1 AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())
        ORDER BY fecha_anuncio DESC
    ");
    $stmt->execute();
    $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al obtener anuncios del dashboard: " . $e->getMessage());
    $mensaje_error_anuncios = "No se pudieron cargar los anuncios. Por favor, intente de nuevo más tarde.";
}

// --- Lógica para obtener los eventos de la base de datos (NUEVO) ---
$eventos = []; // Inicializa un array para guardar los eventos
$mensaje_error_eventos = ""; // Para mensajes de error específicos de eventos

try {
    $stmt_eventos = $conn->prepare("
        SELECT fecha_evento, titulo_evento, descripcion_evento
        FROM eventos
        WHERE visible = 1 AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())
        ORDER BY fecha_evento DESC
    ");
    $stmt_eventos->execute();
    $eventos = $stmt_eventos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al obtener eventos del dashboard: " . $e->getMessage());
    $mensaje_error_eventos = "No se pudieron cargar los eventos. Por favor, intente de nuevo más tarde.";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/styles/dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <title>Inicio</title>
</head>
<body>
    <div id="menu_overlay"></div>

    <?php 
    // Ahora incluye el nuevo diseño del header_menu.php
    include('./includes/header_menu.php'); 
    ?>

    <div class="page-container">
        <main>
            <div id="botones">
                <button data-aos="zoom-in" data-aos-delay="200"><a href="#anuncios"><img src="./assets/img/anuncio_icon.png" alt="">Anuncios</a></button>
                <button data-aos="zoom-in" data-aos-delay="300"><a href="#eventos"><img src="./assets/img/evento_icon.png" alt="">Eventos</a></button>
            </div>

            <section id="anuncios" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
                <h2 id="anuncios_titulo">Anuncios Recientes</h2>
                <?php if (!empty($mensaje_error_anuncios)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($mensaje_error_anuncios); ?></p>
                <?php endif; ?>

                <div class="content-grid">
                    <?php if (count($anuncios) > 0 ): ?>
                        <?php foreach ($anuncios as $anuncio): ?>
                            <div class="anuncio" data-aos="fade-up" data-aos-delay="100">
                                <h3 class="fecha_anuncio">Anuncio de la semana del <?php echo htmlspecialchars(date('d F Y', strtotime($anuncio['fecha_anuncio']))); ?></h3>
                                <h2 class="titulo_anuncio"><?php echo htmlspecialchars($anuncio['titulo_anuncio']); ?></h2>
                                <p class="descripcion_anuncio"><?php echo nl2br(htmlspecialchars($anuncio['descripcion_anuncio'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">No hay anuncios disponibles en este momento.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section id="eventos" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="500">
                <h2 id="eventos_titulo">Próximos Eventos</h2>
                <?php if (!empty($mensaje_error_eventos)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($mensaje_error_eventos); ?></p>
                <?php endif; ?>

                <div class="content-grid">
                    <?php if (count($eventos) > 0): ?>
                        <?php foreach ($eventos as $evento): ?>
                            <div class="anuncio" data-aos="fade-up" data-aos-delay="100"> 
                                <h3 class="fecha_anuncio">Evento de la semana del <?php echo htmlspecialchars(date('d F Y', strtotime($evento['fecha_evento']))); ?></h3>
                                <h2 class="titulo_evento titulo_anuncio"><?php echo htmlspecialchars($evento['titulo_evento']); ?></h2>
                                <p class="descripcion_evento descripcion_anuncio"><?php echo nl2br(htmlspecialchars($evento['descripcion_evento'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">No hay eventos próximos en este momento.</p>
                    <?php endif; ?>
                </div>
            </section>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true, // Las animaciones solo se ejecutan una vez al hacer scroll
            duration: 800, // Duración de la animación
            easing: 'ease-out' // Tipo de easing para una animación suave
        });
    </script>
    <script src="./assets/js/menu.js"></script> 
</body>
</html>