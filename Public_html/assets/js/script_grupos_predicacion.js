document.addEventListener('DOMContentLoaded', function() {

    const params = new URLSearchParams(window.location.search);
    const message = params.get('message');
    const type = params.get('type');

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

    if (message && type) {
        const firstFeedbackArea = document.querySelector('.feedback-area-territorios');
        if (firstFeedbackArea) {
            const feedbackDiv = document.createElement('div');
            feedbackDiv.classList.add('feedback-message', `feedback-${type}`);
            feedbackDiv.textContent = message;
            firstFeedbackArea.prepend(feedbackDiv); 

            setTimeout(() => {
                feedbackDiv.remove();
                const url = new URL(window.location.href);
                url.searchParams.delete('message');
                url.searchParams.delete('type');
                window.history.replaceState({}, document.title, url.toString());
            }, 5000);
        }
    }

    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('btn-eliminar')) {
            const btn = event.target;
            const id = btn.getAttribute('data-id');
            const container = btn.closest('.territorio-button-container');
            
            if (confirm("¿Estás seguro de eliminar este territorio?")) {
                const formData = new FormData();
                formData.append('eliminar_id', id);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    alert(data.mensaje);
                    if (data.tipo === 'success') {
                        // Eliminar solo el contenedor específico de este territorio
                        btn.closest('div[style*="display: flex"]').remove();
                        
                        // Verificar si quedan territorios en ESTE contenedor específico
                        if (container.querySelectorAll('.btn-eliminar').length === 0) {
                            container.innerHTML = '<p style="text-align: center;">No hay territorios asignados</p>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al eliminar territorio:', error);
                    alert('Error al eliminar territorio: ' + error.message);
                });
            }
        }
    });
    const allTerritorioForms = document.querySelectorAll('form[id^="territorioForm"]'); 

    allTerritorioForms.forEach(form => {
        const territoriosContainer = form.querySelector('.territorios-container');
        const addTerritorioBtn = form.querySelector('.add-territorio-btn');
        const feedbackArea = form.querySelector('.feedback-area-territorios');
        
        if (!territoriosContainer || !addTerritorioBtn || !feedbackArea) {
            return;
        }

        let territorioIndex = 0; 

        function addTerritorio() {
            const formId = form.id; 
            const newIndex = territorioIndex++;

            const territorioDiv = document.createElement('div');
            territorioDiv.classList.add('territorio-item', 'form-group');
            territorioDiv.dataset.territorioIndex = newIndex;

            territorioDiv.innerHTML = `
                <label for="territorio-${formId}-${newIndex}">Número de Territorio:</label>
                <div class="territorio-input-group">
                    <input type="text"
                           id="territorio-${formId}-${newIndex}"
                           name="territorios[${newIndex}][nombre_territorio]"
                           placeholder="Solo números enteros sin espacios"
                           required>
                    <button type="button" class="btn-remove remove-territorio-btn">X</button>
                </div>
                <label for="fecha-expiracion-${formId}-${newIndex}">Fecha de Expiración:</label>
                <input type="date"
                       id="fecha-expiracion-${formId}-${newIndex}"
                       name="territorios[${newIndex}][fecha_expiracion]"
                       required>
                <label for="tipo-territorio-${formId}-${newIndex}">Tipo de Territorio:</label>
                <input type="text"
                       id="tipo-territorio-${formId}-${newIndex}"
                       name="territorios[${newIndex}][tipo_territorio]"
                       placeholder="Noche, dia etc..">
            `;
            
            territoriosContainer.appendChild(territorioDiv);

            territorioDiv.querySelector('.remove-territorio-btn').addEventListener('click', function() {
                territorioDiv.remove();
                updateTerritorioInputNames(); 
            });
            
            updateTerritorioInputNames();
        }

        function updateTerritorioInputNames() {
            const allTerritorios = territoriosContainer.querySelectorAll('.territorio-item');
            const formId = form.id;
            
            allTerritorios.forEach((territorioDiv, tIndex) => {
                territorioDiv.dataset.territorioIndex = tIndex;
                
                territorioDiv.querySelector('input[name*="[nombre_territorio]"]').name = `territorios[${tIndex}][nombre_territorio]`;
                territorioDiv.querySelector('input[name*="[fecha_expiracion]"]').name = `territorios[${tIndex}][fecha_expiracion]`;
                territorioDiv.querySelector('input[name*="[tipo_territorio]"]').name = `territorios[${tIndex}][tipo_territorio]`;

                territorioDiv.querySelector('label[for*="territorio-"]').htmlFor = `territorio-${formId}-${tIndex}`;
                territorioDiv.querySelector('input[id*="territorio-"]').id = `territorio-${formId}-${tIndex}`;
                territorioDiv.querySelector('label[for*="fecha-expiracion-"]').htmlFor = `fecha-expiracion-${formId}-${tIndex}`;
                territorioDiv.querySelector('input[id*="fecha-expiracion-"]').id = `fecha-expiracion-${formId}-${tIndex}`;
                territorioDiv.querySelector('label[for*="tipo-territorio-"]').htmlFor = `tipo-territorio-${formId}-${tIndex}`;
                territorioDiv.querySelector('input[id*="tipo-territorio-"]').id = `tipo-territorio-${formId}-${tIndex}`; 
            });
        }


        addTerritorioBtn.addEventListener('click', addTerritorio);

        form.addEventListener('submit', function(event) {
            event.preventDefault(); 
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                // Muestra el feedback dentro del área de este formulario
                const feedbackDiv = document.createElement('div');
                feedbackDiv.classList.add('feedback-message', `feedback-${result.tipo}`);
                feedbackDiv.textContent = result.mensaje;
                feedbackArea.innerHTML = ''; 
                feedbackArea.prepend(feedbackDiv); 

                if (result.tipo === 'success') {
                    territoriosContainer.innerHTML = ''; 
                    territorioIndex = 0; 
                    addTerritorio(); 
                }

                setTimeout(() => feedbackDiv.remove(), 5000);
            })
            .catch(error => {
                console.error('Error al enviar el formulario:', error);
                const feedbackDiv = document.createElement('div');
                feedbackDiv.classList.add('feedback-message', 'feedback-error');
                feedbackDiv.textContent = "Ocurrió un error de red o del servidor. Por favor, intente de nuevo.";
                feedbackArea.innerHTML = '';
                feedbackArea.prepend(feedbackDiv);
                setTimeout(() => feedbackDiv.remove(), 5000);
            });
        });

        // Añadir territorio al incicio
        addTerritorio();

    });

    
});