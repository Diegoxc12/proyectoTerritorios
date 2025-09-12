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
$mensaje_bienvenida = "Campañas de Totoranorte";

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
    <link rel="stylesheet" href="../assets/styles/campañas/ver_campaña.css">
</head>
<body>
    <div id="menu_overlay"></div>

    <?php
    include('../includes/header_menu.php');
    ?>

    <div class="page-container">
        <main>
            <section id="campanas" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
                <h2 id="campanas_titulo">Proximas campañas de predicacion: </h2>
                <?php if (!empty($mensaje_error_campanas)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($mensaje_error_campanas); ?></p>
                <?php endif; ?>

                <div class="content-grid">
                    <?php if (count($campanas) > 0 ): ?>
                        <?php foreach ($campanas as $campana): ?>
                            <div class="campana" data-aos="fade-up" data-aos-delay="100">
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
    </script>
    <script src="../assets/js/menu.js"></script>
</body>
</html>