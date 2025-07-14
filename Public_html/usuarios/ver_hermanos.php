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
$mensaje_bienvenida = "Listado de Hermanos";

$hermanos = [];
$feedback_mensaje = "";

try {
    // Prepara la consulta para obtener todos los hermanos
    $stmt = $conn->prepare("SELECT nombre, segundo_nombre, apellido, segundo_apellido FROM hermanos ORDER BY apellido, nombre");
    $stmt->execute();
    $hermanos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al obtener hermanos: " . $e->getMessage());
    $feedback_mensaje = "Error al cargar el listado de hermanos. Por favor, intente de nuevo más tarde.";
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
    <title>Ver Hermanos</title>
    <style>
        /* Aquí se mantienen los estilos base y se añaden los de tabla */
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
            display: flex;
            flex-direction: column;
            align-items: center; /* Centra horizontalmente el contenido principal */
            justify-content: flex-start;
        }

        .feedback-message {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideInDown 0.5s ease;
            width: 100%;
            max-width: 800px; /* Ajustado para el tamaño de la tabla */
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

        #ver_hermanos_titulo {
            font-size: clamp(1.6rem, 4vw, 2.5rem);
            margin-bottom: 30px;
            color: #2D3748;
            font-weight: 700;
            position: relative;
            padding-bottom: 10px;
            line-height: 1.3;
            align-self: flex-start; /* Alinea el título a la izquierda */
            margin-top: 30px;
            width: 100%; /* Para que la línea se alinee con el contenedor principal */
            max-width: 800px; /* Asegura que el título no exceda el ancho de la tabla */
            text-align: left;
        }

        #ver_hermanos_titulo::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: clamp(50px, 10vw, 70px);
            height: 4px;
            background-color: var(--color-secondary);
            border-radius: 2px;
        }

        /* Estilos para la tabla */
        .table-container {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 1.5rem;
            width: 100%;
            max-width: 800px; /* Ajusta el ancho máximo de la tabla */
            overflow-x: auto; /* Permite scroll horizontal en pantallas pequeñas */
            animation: fadeIn 0.6s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--color-border);
        }

        th {
            background-color: var(--color-primary);
            color: var(--color-white);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.05em;
        }

        tr:nth-child(even) {
            background-color: #F9FAFB; /* Un color más claro para filas pares */
        }

        tr:hover {
            background-color: #F3F4F6;
            cursor: pointer;
        }

        /* Responsive design para la tabla */
        @media (max-width: 768px) {
            .table-container {
                padding: 1rem;
            }
            th, td {
                padding: 10px;
                font-size: 0.85rem;
            }
        }

        @media (min-width: 1024px) {
            main {
                margin-left: 355px; /* Ajusta según el ancho de tu header_menu */
                margin-right: 50px;
                width: calc(100% - 405px);
            }
            .table-container {
                padding: 2.5rem;
                max-width: 900px;
            }
            #ver_hermanos_titulo {
                font-size: 3rem;
                margin-bottom: 3.5rem;
                align-self: center; /* Centra el título en desktop para que la tabla también se vea centrada */
            }
        }
    </style>
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="ver_hermanos_titulo"><?php echo htmlspecialchars($mensaje_bienvenida); ?></h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo strpos($feedback_mensaje, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($hermanos)): ?>
                <p style="text-align: center; color: var(--color-text-light);">No hay hermanos registrados aún.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Segundo Nombre</th>
                            <th>Apellido</th>
                            <th>Segundo Apellido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hermanos as $hermano): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hermano['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($hermano['segundo_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($hermano['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($hermano['segundo_apellido']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>