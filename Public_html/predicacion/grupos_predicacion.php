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
$mensaje_bienvenida = "Grupos de predicacion";

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
    $mensaje_error_campanas = "No se pudieron cargar los grupos de predicacion intente de nuevo" . $e->getMessage();
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
    <title>Grupos de predi</title>
    <link rel="stylesheet" href="../assets/styles/predicacion/grupos_predicacion.css">
</head>
<body>
    <div id="menu_overlay"></div>

    <?php
    include('../includes/header_menu.php');
    ?>

    <div class="page-container">
        <main>
            <section id="campanas" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
                <h2 id="campanas_titulo">Grupos de predicacion: </h2>
                <?php if (!empty($mensaje_error_campanas)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($mensaje_error_campanas); ?></p>
                <?php endif; ?>

                <div class="content-grid">
                    <button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" onclick="location.href='./grupo_lunes.php'">
                        Lunes
                    </button>
                    <button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" onclick="location.href='./grupo_martes.php'">
                        Martes
                    </button>
                    <button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" onclick="location.href='./grupo_miercoles.php'">
                        Miercoles
                    </button>
                    <button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" onclick="location.href='./grupo_jueves.php'">
                        Jueves
                    </button>
                    <button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" onclick="location.href='./grupo_viernes.php'">
                        Viernes
                    </button>
                    <button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" onclick="location.href='./grupo_sabado.php'">
                        Sabado
                    </button>
                    <button class="btn-grupos" data-aos="fade-up" data-aos-delay="200" onclick="location.href='./grupo_domingo.php'">
                        Domingo
                    </button>
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