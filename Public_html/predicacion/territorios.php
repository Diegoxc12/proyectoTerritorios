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
    <link rel="stylesheet" href="../assets/styles/predicacion/territorios.css">
</head>

<body>
    <div id="menu_overlay"></div>

    <?php include('../includes/header_menu.php'); ?>



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