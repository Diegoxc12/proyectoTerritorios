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
$mensaje_bienvenida = "Agregar Campañas";

$feedback_mensaje = "";
$feedback_tipo = ""; 

$fecha_campana = '';
$lugar = '';
$titulo_campana = '';
$descripcion_campana = '';
$fecha_expiracion_checked = false; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {
    header('Content-Type: application/json'); 
    $response = array('success' => false, 'message' => 'Error desconocido al procesar PDF.');

    // Verificar si se recibió el archivo sin errores
    if ($_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) {
        $tempFilePath = $_FILES['pdfFile']['tmp_name']; 

        $originalFileName = $_FILES['pdfFile']['name']; 
        
        $destinationFolder = __DIR__ . '/arreglos_transporte/'; 
        
        // Asegurarse de que la carpeta de destino exista, si no, crearla
        if (!is_dir($destinationFolder)) {
            if (!mkdir($destinationFolder, 0755, true)) { // Permisos 0755 son más seguros para directorios
                $response['message'] = 'No se pudo crear la carpeta de destino para el PDF.';
                echo json_encode($response);
                exit; // Termina la ejecución si no se puede crear la carpeta
            }
        }

        // Ruta completa donde se guardaría el archivo
        $finalFilePath = $destinationFolder . basename($originalFileName);

        if (file_exists($finalFilePath)) {
            $response['success'] = false;
            $response['message'] = 'Ya existe un archivo con el nombre "' . htmlspecialchars(basename($originalFileName)) . '". Por favor, ingrese otro nombre.';
        } else {
            if (move_uploaded_file($tempFilePath, $finalFilePath)) {
                $response['success'] = true;
                $response['message'] = 'PDF guardado con éxito en el servidor.';
                $response['filePath'] = 'arreglos_transporte/' . basename($originalFileName); 
            } else {
                $response['message'] = 'Error al mover el archivo PDF subido.';
            }
        }

    } else {
        // Manejar errores de subida de PHP
        switch ($_FILES['pdfFile']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $response['message'] = 'El archivo PDF es demasiado grande.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $response['message'] = 'El archivo PDF fue subido parcialmente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $response['message'] = 'No se seleccionó ningún archivo PDF.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $response['message'] = 'Falta una carpeta temporal para la subida de PDF.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $response['message'] = 'Fallo al escribir el archivo PDF en el disco.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $response['message'] = 'Una extensión de PHP detuvo la subida del archivo PDF.';
                break;
            default:
                $response['message'] = 'Error desconocido en la subida del archivo PDF.';
                break;
        }
    }

    echo json_encode($response);
    exit; // Importante: Termina la ejecución aquí para que no se procese el resto del HTML/PHP
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha_campana = $_POST['fecha_campana'] ?? '';
    $lugar = trim($_POST['lugar'] ?? '');
    $titulo_campana = trim($_POST['titulo_campana'] ?? '');
    $descripcion_campana = trim($_POST['descripcion_campana'] ?? '');
    $fecha_expiracion_checked = isset($_POST['fecha_expiracion_checkbox']);

    $fecha_expiracion_valor = null;
    if ($fecha_expiracion_checked) {
        $fecha_expiracion_valor = date('Y-m-d H:i:s', strtotime('+2 weeks'));
    }

    if (empty($fecha_campana) || empty($lugar) || empty($titulo_campana) || empty($descripcion_campana)) {
        $feedback_mensaje = "Error: Todos los campos de la campaña son obligatorios.";
        $feedback_tipo = "error";
    } else {
        try {
            $conn->beginTransaction();

            $stmt_campana = $conn->prepare("INSERT INTO campañas (fecha_campaña, lugar, titulo_campaña, descripcion_campaña, fecha_expiracion) VALUES (?, ?, ?, ?, ?)");
            $stmt_campana->execute([$fecha_campana, $lugar, $titulo_campana, $descripcion_campana, $fecha_expiracion_valor]);
            $id_campana = $conn->lastInsertId();

            if (isset($_POST['carros']) && is_array($_POST['carros'])) {
                foreach ($_POST['carros'] as $carro_data) {
                    $nombre_carro = trim($carro_data['nombre_carro'] ?? '');

                    if (!empty($nombre_carro)) {
                        $stmt_carro = $conn->prepare("INSERT INTO carros (id_campaña, nombre_carro) VALUES (?, ?)");
                        $stmt_carro->execute([$id_campana, $nombre_carro]);
                        $id_carro = $conn->lastInsertId();

                        if (isset($carro_data['personas']) && is_array($carro_data['personas'])) {
                            foreach ($carro_data['personas'] as $persona_data) {
                                $nombre_persona = trim($persona_data['nombre'] ?? '');
                                if (!empty($nombre_persona)) {
                                    $stmt_persona = $conn->prepare("INSERT INTO personas (id_carro, nombre) VALUES (?, ?)");
                                    $stmt_persona->execute([$id_carro, $nombre_persona]);
                                }
                            }
                        }
                    }
                }
            }

            $conn->commit();
            $feedback_mensaje = "¡Campaña creada exitosamente!";
            $feedback_tipo = "success";

            $fecha_campana = '';
            $lugar = '';
            $titulo_campana = '';
            $descripcion_campana = '';
            $fecha_expiracion_checked = false;

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Error al crear campaña: " . $e->getMessage());
            $feedback_mensaje = "Error al crear la campaña. Por favor, intente de nuevo más tarde. (Detalle: " . $e->getMessage() . ")";
            $feedback_tipo = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Agregar Campaña</title>
    <link rel="stylesheet" href="../assets/styles/campañas/agregar_campaña.css">
</head>
<body>
    <?php include('../includes/header_menu.php'); ?>

    <main>
        <h2 id="agregar_campana_titulo">Agregar Campaña</h2>

        <?php if (!empty($feedback_mensaje)): ?>
            <div class="feedback-message <?php echo $feedback_tipo; ?>">
                <?php echo htmlspecialchars($feedback_mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form id="campaignForm" action="agregar_campaña.php" method="POST">
                <div class="form-group">
                    <h2>Fecha de la Campaña</h2>
                    <input type="date"
                           id="fecha_campana"
                           name="fecha_campana"
                           value="<?php echo htmlspecialchars($fecha_campana ?? date('Y-m-d')); ?>"
                           required>
                </div>

                <div class="form-group">
                    <h2>Lugar de la Campaña</h2>
                    <input type="text"
                           id="lugar"
                           name="lugar"
                           placeholder="Escribe el lugar de la campaña"
                           value="<?php echo htmlspecialchars($lugar ?? ''); ?>"
                           required
                           maxlength="255">
                </div>

                <div class="form-group">
                    <h2>Título de la Campaña</h2>
                    <input type="text"
                           id="titulo_campana"
                           name="titulo_campana"
                           placeholder="Campaña hacia..."
                           value="<?php echo htmlspecialchars($titulo_campana ?? ''); ?>"
                           required
                           maxlength="255">
                </div>

                <div class="form-group">
                    <h2>Descripción de la Campaña</h2>
                    <textarea id="descripcion_campana"
                              name="descripcion_campana"
                              rows="5"
                              placeholder="Describe los detalles de la campaña..."
                              required><?php echo htmlspecialchars($descripcion_campana ?? ''); ?></textarea>
                </div>

                <div class="checkbox-container">
                    <label class="radio-option" id="option-fecha-expiracion" for="fecha_expiracion_checkbox">
                        <input type="checkbox"
                            id="fecha_expiracion_checkbox"
                            name="fecha_expiracion_checkbox"
                            class="custom-radio"
                            <?php echo ($fecha_expiracion_checked || (!isset($_POST['fecha_expiracion_checkbox']) && empty($_POST))) ? 'checked' : ''; ?>>
                        <span class="check">
                            <svg width="22px" height="22px" viewBox="0 0 18 18">
                                <path d="M 1 9 L 1 9 c 0 -5 3 -8 8 -8 L 9 1 C 14 1 17 5 17 9 L 17 9 c 0 4 -4 8 -8 8 L 9 17 C 5 17 1 14 1 9 L 1 9 Z"></path>
                                <polyline points="1 9 7 14 15 4"></polyline>
                            </svg>
                        </span>
                        <span>Eliminar automáticamente</span>
                    </label>
                </div>


                <hr class="section-divider">
                <h2 class="section-header">Agregar Arreglos de Transporte</h2>

                <div id="carros-container">
                </div>

                <button type="button" id="add-carro-btn" class="btn-secondary">Agregar Carro</button>

                <button type="button" class="btn-secondary" id="generarPdfBtn">
                    Generar PDF de arreglos
                </button>
                
                <button type="submit" class="btn-primary">
                    Agregar Campaña
                </button>

                
            </form>
        </div>
    </main>
    
    <script type="text/javascript" src="../assets/js/jspdf.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carrosContainer = document.getElementById('carros-container');
            const addCarroBtn = document.getElementById('add-carro-btn');
            const generarPdfBtn = document.getElementById('generarPdfBtn');
            let carroIndex = 0;

            function addCarro() {
                const carroDiv = document.createElement('div');
                carroDiv.classList.add('carro-item');
                carroDiv.dataset.carroIndex = carroIndex;

                carroDiv.innerHTML = `
                    <div class="form-group">
                        <h3>Nombre del Carro</h3>
                        <input type="text"
                               name="carros[${carroIndex}][nombre_carro]"
                               placeholder="Nombre del dueño del carro"
                               required>
                    </div>
                    <div class="personas-container" id="personas-container-${carroIndex}">
                        <h4>Pasajeros</h4>
                        <div class="persona-items">
                            </div>
                        <button type="button" class="btn-secondary add-persona-btn" data-carro-index="${carroIndex}">Agregar Persona</button>
                    </div>
                    <button type="button" class="btn-remove remove-carro-btn">Eliminar Carro</button>
                `;
                
                carrosContainer.appendChild(carroDiv);

                addPersona(carroIndex);
                addPersona(carroIndex);
                addPersona(carroIndex);
                addPersona(carroIndex);
                addPersona(carroIndex);

                carroIndex++;

                carroDiv.querySelector('.add-persona-btn').addEventListener('click', function() {
                    const currentCarroIndex = this.dataset.carroIndex;
                    addPersona(currentCarroIndex);
                });

                carroDiv.querySelector('.remove-carro-btn').addEventListener('click', function() {
                    carroDiv.remove();
                    updateInputNames(); 
                });

                updateInputNames();
            }

            function addPersona(currentCarroIndex) {
                const personasContainer = document.getElementById(`personas-container-${currentCarroIndex}`).querySelector('.persona-items');
                const personaIndex = personasContainer.children.length; 

                const personaDiv = document.createElement('div');
                personaDiv.classList.add('persona-item');
                personaDiv.innerHTML = `
                    <input type="text"
                           name="carros[${currentCarroIndex}][personas][${personaIndex}][nombre]"
                           placeholder="Nombre del pasajero"
                           required>
                    <button type="button" class="btn-remove remove-persona-btn">X</button>
                `;
                personasContainer.appendChild(personaDiv);

                personaDiv.querySelector('.remove-persona-btn').addEventListener('click', function() {
                    personaDiv.remove();
                    updateInputNames(); 
                });

                updateInputNames();
            }

            function updateInputNames() {
                const allCarros = carrosContainer.querySelectorAll('.carro-item');
                allCarros.forEach((carroDiv, cIndex) => {
                    carroDiv.dataset.carroIndex = cIndex;
                    carroDiv.querySelector('input[name*="[nombre_carro]"]').name = `carros[${cIndex}][nombre_carro]`;
                    carroDiv.querySelector('.add-persona-btn').dataset.carroIndex = cIndex;

                    const personas = carroDiv.querySelectorAll('.persona-item');
                    personas.forEach((personaDiv, pIndex) => {
                        personaDiv.querySelector('input[name*="[personas]"]').name = `carros[${cIndex}][personas][${pIndex}][nombre]`;
                    });
                });
            }

            addCarroBtn.addEventListener('click', addCarro);

            addCarro(); // Para tener un carro inicial

            generarPdfBtn.addEventListener('click', function() {
                var doc = new jsPDF();
                // Obtener el título del input para el nombre del archivo
                var tituloInput = document.getElementById('titulo_campana');
                var titulo = tituloInput.value || 'Título de Campaña';
                var y = 20;

                function drawSelectiveRoundedRect(x, y, width, height, radius, fillColor, roundTop, roundBottom) {
                    var r = radius || 3;
                    if (fillColor) {
                        doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
                    }
                    if (roundTop && roundBottom) {
                        doc.rect(x + r, y, width - 2*r, height, 'F');
                        doc.rect(x, y + r, width, height - 2*r, 'F');
                        doc.circle(x + r, y + r, r, 'F');
                        doc.circle(x + width - r, y + r, r, 'F');
                        doc.circle(x + r, y + height - r, r, 'F');
                        doc.circle(x + width - r, y + height - r, r, 'F');
                    } else if (roundTop) {
                        doc.rect(x, y + r, width, height - r, 'F');
                        doc.rect(x + r, y, width - 2*r, r, 'F');
                        doc.circle(x + r, y + r, r, 'F');
                        doc.circle(x + width - r, y + r, r, 'F');
                    } else if (roundBottom) {
                        doc.rect(x, y, width, height - r, 'F');
                        doc.rect(x + r, y + height - r, width - 2*r, r, 'F');
                        doc.circle(x + r, y + height - r, r, 'F');
                        doc.circle(x + width - r, y + height - r, r, 'F');
                    } else {
                        doc.rect(x, y, width, height, 'F');
                    }
                }

                doc.setFontSize(25);
                doc.setFontStyle('bold');
                doc.setTextColor(0, 0, 0);
                var pageWidth = doc.internal.pageSize.width;
                var textWidth = doc.getStringUnitWidth(titulo) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                var centerX = (pageWidth - textWidth) / 2;
                doc.text(titulo, centerX, y);

                y += 10;
                doc.setTextColor(17, 42, 92);
                doc.setLineWidth(0.5);
                doc.setDrawColor(0, 0, 0);
                doc.line(20, y, pageWidth - 20, y);
                y += 20;

                var subtitulo = "Arreglos de transporte";
                doc.setFontSize(18);
                doc.setFontStyle('bold');

                var subtituloWidth = doc.getStringUnitWidth(subtitulo) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                var subtituloX = (pageWidth - subtituloWidth) / 2;
                doc.text(subtitulo, subtituloX, y);

                var marginLeft = 15;
                var marginRight = 15;
                var availableWidth = pageWidth - marginLeft - marginRight;
                var tableWidth = 55;
                var tableSpacing = 5;
                var totalTablesWidth = (tableWidth * 3) + (tableSpacing * 2);
                
                var x1 = marginLeft + (availableWidth - totalTablesWidth) / 2;
                var x2 = x1 + tableWidth + tableSpacing;
                var x3 = x2 + tableWidth + tableSpacing;
                
                var currentX = x1;
                y += 15;
                var rowHeight = 8;
                var textPadding = 2;
                var maxTablesPerRow = 3;
                var tableCounter = 0;
                var maxRowHeight = 0;
                
                var carros = document.querySelectorAll('[id^="personas-container-"]');

                function splitTextToFitWidth(text, maxWidth, fontSize) {
                    doc.setFontSize(fontSize || 10);
                    var lines = doc.splitTextToSize(text, maxWidth);
                    return lines;
                }

                carros.forEach(function (carroContainer, index) {
                    var inputNombreCarro = carroContainer.parentElement.querySelector('input[name^="carros["][name$="[nombre_carro]"]');
                    var nombreCarro = inputNombreCarro ? inputNombreCarro.value : 'Nombre de carro';
                    var personasInputs = carroContainer.querySelectorAll('input[name^="carros["][name*="[personas]"][name$="[nombre]"]');

                    var tableY = y;
                    var textMaxWidth = tableWidth - 8;
                    
                    var carroLines = splitTextToFitWidth(nombreCarro, textMaxWidth, 9);
                    
                    var personasLines = [];
                    personasInputs.forEach(function (input, idx) {
                        var nombrePersona = input.value || 'Nombre de persona';
                        var personaLines = splitTextToFitWidth(nombrePersona, textMaxWidth, 9);
                        for (var i = 0; i < personaLines.length; i++) {
                            personasLines.push(personaLines[i]);
                        }
                    });

                    var totalLines = carroLines.length + personasLines.length;
                    var tableHeight = Math.max(totalLines * rowHeight + (textPadding * 2) + (personasLines.length * textPadding), rowHeight * 3);
                    
                    if (tableY + tableHeight > 270) {
                        doc.addPage();
                        y = 20;
                        tableY = y;
                        currentX = x1;
                        tableCounter = 0;
                        maxRowHeight = 0;
                    }

                    var borderRadius = 4;

                    var headerHeight = carroLines.length * rowHeight + (textPadding * 2);
                    var hasPersonas = personasLines.length > 0;
                    
                    drawSelectiveRoundedRect(currentX, tableY, tableWidth, headerHeight, borderRadius, [17, 42, 92], true, !hasPersonas);

                    if (hasPersonas) {
                        var contentHeight = personasLines.length * rowHeight + (textPadding * 2) + (personasLines.length * textPadding);
                        var contentY = tableY + headerHeight;
                        drawSelectiveRoundedRect(currentX, contentY, tableWidth, contentHeight, borderRadius, [78, 102, 155], false, true);
                    }

                    doc.setFontSize(12);
                    doc.setFontStyle('bold');
                    doc.setTextColor(255, 255, 255);
                    var currentTextY = tableY + textPadding + (rowHeight / 2) + 1;
                    
                    for (var i = 0; i < carroLines.length; i++) {
                        var lineWidth = doc.getStringUnitWidth(carroLines[i]) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                        var centeredX = currentX + (tableWidth - lineWidth) / 2;
                        doc.text(carroLines[i], centeredX, currentTextY);
                        currentTextY += rowHeight;
                    }

                    if (hasPersonas) {
                        doc.setDrawColor(255, 255, 255);
                        doc.setLineWidth(0.4);
                        doc.line(currentX, tableY + headerHeight, currentX + tableWidth, tableY + headerHeight);
                    }

                    doc.setFontSize(11);
                    doc.setFontStyle('normal');
                    doc.setTextColor(255, 255, 255);
                    currentTextY += textPadding;
                    
                    for (var j = 0; j < personasLines.length; j++) {
                        currentTextY += textPadding;
                        
                        var lineWidth = doc.getStringUnitWidth(personasLines[j]) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                        var centeredX = currentX + (tableWidth - lineWidth) / 2;
                        doc.text(personasLines[j], centeredX, currentTextY);
                        currentTextY += rowHeight;
                        
                        if (j < personasLines.length - 1) {
                            doc.setDrawColor(255, 255, 255);
                            doc.setLineWidth(0.3);
                            doc.line(currentX + 4, currentTextY - (rowHeight / 2), currentX + tableWidth - 4, currentTextY - (rowHeight / 2));
                        }
                    }

                    maxRowHeight = Math.max(maxRowHeight, tableHeight);

                    tableCounter++;
                    if (tableCounter % maxTablesPerRow === 0) {
                        y += maxRowHeight + 10;
                        currentX = x1;
                        maxRowHeight = 0;
                    } else {
                        if (tableCounter % maxTablesPerRow === 1) {
                            currentX = x2;
                        } else {
                            currentX = x3;
                        }
                    }
                });

                var pdfBlob = doc.output('blob');
                var nombreArchivo = titulo.replace(/ /g, '_') + ".pdf";

                var formData = new FormData();
                formData.append('pdfFile', pdfBlob, nombreArchivo);
                formData.append('fileName', nombreArchivo);

                fetch(window.location.href, { 
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.headers.get('content-type')?.includes('application/json')) {
                        console.warn('Respuesta no JSON recibida, puede ser una redirección o HTML inesperado.');
                        return response.text().then(text => { throw new Error('Respuesta inesperada: ' + text); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert("El archivo '" + nombreArchivo + "' ha sido guardado en la ruta del proyecto y descargado.");
                        doc.save(nombreArchivo); 
                    } else {
                        alert(data.message);
                        console.error("Error del servidor:", data.message);

                        if (data.message.includes('Ya existe un archivo con este nombre')) {
                            tituloInput.focus();
                            tituloInput.select(); 
                        }
                    }
                })
                .catch(error => {
                    console.error('Error de red o al enviar el PDF:', error);
                    alert("Hubo un error de comunicación con el servidor al intentar guardar el PDF.");
                });
            });
        });
    </script>
    <link rel="stylesheet" href="../assets/css/agregar_campana.css">
</body>
</html>