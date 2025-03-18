@extends('layouts.admin')
@section('content')
<div class="container-fluid p-0">
    <!-- Encabezado Panel verde -->
    <div style="background-color: #00bf63;" class="text-white p-3 mb-4">
        <h1 class="display-4 text-center m-0">Gestión de Reservas</h1>
    </div>

    <!-- Barra de opciones (botones) -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-auto">
                <button id="btnOriginal" class="btn btn-sm btn-primary me-2">
                    Todas las Reservas
                </button>
                <button id="btnComprobanteDuplicado" class="btn btn-sm btn-secondary me-2">
                    # Comprobante Duplicado
                </button>
                <button id="btnPedidoDuplicado" class="btn btn-sm btn-secondary me-2">
                    Pedido Duplicado
                </button>
                <button id="btnCartonesEliminados" class="btn btn-sm btn-secondary">
                    Cartones Eliminados
                </button>
            </div>
        </div>
    </div>

    <!-- Sección de filtros (persistente) -->
    <div class="container mb-4" id="filtrosContainer">
        <div class="card bg-dark text-white">
            <div class="card-header bg-secondary">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" id="nombre" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-3">
                        <label for="celular" class="form-label">Celular:</label>
                        <input type="text" id="celular" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-3">
                        <label for="serie" class="form-label">Serie:</label>
                        <input type="text" id="serie" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-12 mt-3 d-flex justify-content-between">
                        <button type="button" id="btnFiltrar" class="btn btn-success">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <button type="button" id="btnLimpiar" class="btn btn-warning">
                            <i class="bi bi-x-circle"></i> Limpiar filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container mb-4">
    <div class="row">
        <div class="col-12 d-flex justify-content-between">
            <a href="{{ route('bingos.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
            <button type="button" id="btnBorrarClientes" class="btn btn-danger">
                <i class="bi bi-trash"></i> Borrar Clientes
            </button>
        </div>
    </div>
</div>

<!-- Modal de confirmación para borrar clientes -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header bg-danger">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>ADVERTENCIA:</strong> Esta acción es irreversible.
                </div>
                <p>Estás a punto de eliminar <strong>TODOS</strong> los registros de la tabla clientes.</p>
                <p>Esta acción no se puede deshacer y resultará en la pérdida permanente de todos los datos de clientes.</p>
                
                <!-- Campo de confirmación para mayor seguridad -->
                <div class="form-group mt-3">
                    <label for="confirmText">Para confirmar, escribe "BORRAR TODOS LOS CLIENTES" en el campo de abajo:</label>
                    <input type="text" class="form-control bg-dark text-white border-danger mt-2" id="confirmText" placeholder="Escribe aquí para confirmar">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteClientsForm" action="{{ route('admin.borrarClientes') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="confirmDeleteBtn" class="btn btn-danger" disabled>
                        <i class="bi bi-trash"></i> Eliminar Todos los Clientes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- Contenedor para la tabla (se actualizará dinámicamente) -->
    <div class="container" id="tableContent">
        {{-- Incluimos inicialmente la tabla original --}}
        @include('admin.table', ['reservas' => $reservas])
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentTable = null;
    
    // Debug info for production
    console.log('DOM loaded');
    console.log('All buttons:', Array.from(document.querySelectorAll('button')).map(b => b.id));
    console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Available' : 'Not available');
    console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'Not available');
    console.log('DataTables:', typeof $.fn.DataTable !== 'undefined' ? 'Available' : 'Not available');
    
    // Inicializar DataTable directamente en la tabla existente
    function initializeDataTable() {
        // Primero, asegurarse de destruir cualquier instancia previa
        if ($.fn.DataTable.isDataTable('.table')) {
            $('.table').DataTable().destroy();
        }
        
        try {
            // Inicializar nueva instancia de DataTable
            currentTable = $('.table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                    emptyTable: "No hay datos disponibles",
                    zeroRecords: "No hay resultados que concuerden con tu filtro"
                },
                order: [[0, 'desc']], // Ordenar por ID de forma descendente
                responsive: true,
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                drawCallback: function(settings) {
                    // Si hay un error, lo manejamos aquí
                    if (settings.bDestroying) return;
                    
                    // Comprobar si hay datos
                    if (this.api().data().length === 0) {
                        // Si no hay datos, mostrar mensaje personalizado
                        if (!document.querySelector('.dataTables_empty')) {
                            const tbody = document.querySelector('.table tbody');
                            if (tbody) {
                                const tr = document.createElement('tr');
                                const td = document.createElement('td');
                                td.className = 'dataTables_empty';
                                td.textContent = "No hay resultados que concuerden con tu filtro";
                                td.setAttribute('colspan', '100%'); // Usar 100% para cubrir todas las columnas
                                tr.appendChild(td);
                                tbody.innerHTML = '';
                                tbody.appendChild(tr);
                            }
                        }
                    }
                },
                initComplete: function() {
                    // Añadir clases personalizadas para el tema oscuro
                    $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').addClass('text-white');
                    $('.dataTables_wrapper .form-control').addClass('bg-dark text-white border-secondary');
                    $('.dataTables_wrapper .page-link').addClass('bg-dark text-white border-secondary');
                    
                    console.log('DataTable inicializado correctamente');
                }
            });
        } catch (error) {
            console.error('Error al inicializar DataTable:', error);
            // En caso de error, mostrar mensaje personalizado
            showNoResultsMessage();
        }
        
        // Configurar eventos para elementos dentro de la tabla
        setupTableEvents();
    }
    
    // Función para mostrar mensaje de "No hay resultados" si DataTable falla
    function showNoResultsMessage() {
        const table = document.querySelector('.table');
        if (table) {
            const tbody = table.querySelector('tbody');
            if (tbody) {
                // Contar el número de columnas en la tabla
                const headerCells = table.querySelectorAll('thead th');
                const colCount = headerCells.length || 1;
                
                // Crear una fila con mensaje
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.setAttribute('colspan', colCount);
                td.className = 'text-center py-3';
                td.textContent = "No hay resultados que concuerden con tu filtro";
                tr.appendChild(td);
                
                // Reemplazar contenido de tbody
                tbody.innerHTML = '';
                tbody.appendChild(tr);
            }
        }
    }
    
    // Función para configurar eventos en la tabla
    function setupTableEvents() {
        console.log('Configurando eventos de tabla');
        
        // Configurar eventos para botones de editar series
        document.querySelectorAll('.edit-series').forEach(button => {
            console.log('Encontrado botón de editar series:', button);
            button.removeEventListener('click', handleEditSeries); // Eliminar listeners anteriores para evitar duplicados
            button.addEventListener('click', handleEditSeries);
        });
        
        // Configurar eventos para formularios de aprobación/rechazo
        document.querySelectorAll('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]').forEach(form => {
            form.removeEventListener('submit', handleFormSubmit); // Eliminar listeners anteriores
            form.addEventListener('submit', handleFormSubmit);
        });
        
        // Verificación adicional para botones de editar series
        if (document.querySelectorAll('.edit-series').length === 0) {
            console.warn('No se encontraron botones de editar series. Intentando con selector alternativo.');
            // Intentar con selectores alternativos que podrían estar en uso
            document.querySelectorAll('[data-action="edit-series"], .btn-edit-series, button[data-bs-target="#editSeriesModal"]').forEach(button => {
                console.log('Encontrado botón alternativo:', button);
                button.removeEventListener('click', handleEditSeries);
                button.addEventListener('click', handleEditSeries);
            });
        }
    }
    
    // Función para reinicializar botones de editar
    function reinitializeEditButtons() {
        console.log('Reinicializando botones de editar series');
        
        // Buscar todos los posibles botones de editar series
        const editButtons = document.querySelectorAll('.edit-series, [data-action="edit-series"], .btn-edit-series, button[data-bs-target="#editSeriesModal"]');
        
        console.log(`Encontrados ${editButtons.length} botones de editar`);
        
        editButtons.forEach(button => {
            // Eliminar eventos anteriores para evitar duplicación
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Añadir el evento de clic
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Botón de editar series clickeado');
                handleEditSeries.call(this);
            });
        });
    }
    
    // Manejador para el evento de editar series
    function handleEditSeries() {
        console.log('Ejecutando handleEditSeries');
        
        const modal = document.getElementById('editSeriesModal');
        
        if (!modal) {
            console.error('No se encontró el modal de editar series');
            alert('Error: No se encontró el modal para editar series.');
            return;
        }
        
        const seriesData = this.getAttribute('data-series');
        let series = [];

        try {
            series = JSON.parse(seriesData);
        } catch (e) {
            console.error('Error al parsear series:', e);
            // Si las series no están en formato JSON, intentar convertirlas desde string
            if (typeof seriesData === 'string') {
                series = seriesData.split(',').map(item => item.trim());
            }
        }

        const reservaId = this.getAttribute('data-id');
        const bingoId = this.getAttribute('data-bingo-id');
        const cantidad = parseInt(this.getAttribute('data-cantidad'));
        const total = parseInt(this.getAttribute('data-total'));
        const bingoPrice = parseInt(this.getAttribute('data-bingo-precio'));

        console.log('Datos obtenidos:', { reservaId, bingoId, cantidad, total, bingoPrice });

        // Completar datos del formulario
        const reservaIdInput = document.getElementById('reserva_id');
        if (reservaIdInput) reservaIdInput.value = reservaId;
        
        const bingoIdInput = document.getElementById('bingo_id');
        if (bingoIdInput) bingoIdInput.value = bingoId;
        
        const clientNameElem = document.getElementById('clientName');
        if (clientNameElem) clientNameElem.textContent = this.getAttribute('data-nombre');
        
        const newQuantityInput = document.getElementById('newQuantity');
        if (newQuantityInput) {
            newQuantityInput.value = cantidad;
            newQuantityInput.setAttribute('max', Array.isArray(series) ? series.length : 1);
        }
        
        const currentTotalElem = document.getElementById('currentTotal');
        if (currentTotalElem) currentTotalElem.textContent = new Intl.NumberFormat('es-CL').format(total);

        // Establecer URL del formulario
        const form = document.getElementById('editSeriesForm');
        if (form) form.action = `/admin/reservas/${reservaId}/update-series`;

        // Mostrar series actuales y crear checkboxes
        const currentSeriesDiv = document.getElementById('currentSeries');
        const seriesCheckboxesDiv = document.getElementById('seriesCheckboxes');

        if (!currentSeriesDiv || !seriesCheckboxesDiv) {
            console.error('No se encontraron los contenedores para las series');
            return;
        }

        // Limpiar contenido previo
        currentSeriesDiv.innerHTML = '';
        seriesCheckboxesDiv.innerHTML = '';

        // Mostrar y crear checkboxes para cada serie
        if (Array.isArray(series) && series.length > 0) {
            const seriesList = document.createElement('ul');
            seriesList.className = 'list-group';

            series.forEach((serie, index) => {
                // Crear elemento de lista
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item bg-dark text-white border-light';
                listItem.textContent = `Serie ${serie}`;
                seriesList.appendChild(listItem);

                // Crear checkbox
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-2';

                const checkDiv = document.createElement('div');
                checkDiv.className = 'form-check';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.id = `serie_${index}`;
                checkbox.name = 'selected_series[]';
                checkbox.value = serie;
                checkbox.className = 'form-check-input';
                checkbox.checked = true;

                const label = document.createElement('label');
                label.htmlFor = `serie_${index}`;
                label.className = 'form-check-label';
                label.textContent = `Serie ${serie}`;

                checkDiv.appendChild(checkbox);
                checkDiv.appendChild(label);
                col.appendChild(checkDiv);
                seriesCheckboxesDiv.appendChild(col);
            });

            currentSeriesDiv.appendChild(seriesList);
        } else {
            currentSeriesDiv.textContent = 'No hay series disponibles';
        }

        // Manejar cambio en la cantidad de cartones
        if (newQuantityInput) {
            newQuantityInput.removeEventListener('change', handleQuantityChange);
            newQuantityInput.addEventListener('change', handleQuantityChange);
        }

        function handleQuantityChange() {
            const newQuantity = parseInt(this.value);
            
            // Actualizar el total estimado
            const newTotal = newQuantity * bingoPrice;
            const currentTotalElement = document.getElementById('currentTotal');
            if (currentTotalElement) {
                currentTotalElement.textContent = new Intl.NumberFormat('es-CL').format(newTotal);
            }

            // Actualizar contador
            updateSelectedCounter();
        }

        // Función para actualizar contador de seleccionados
        function updateSelectedCounter() {
            const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
            const newQuantityElement = document.getElementById('newQuantity');
            if (!newQuantityElement) return;
            
            const newQuantity = parseInt(newQuantityElement.value);
            let checkedCount = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) checkedCount++;
            });

            // Verificar si se están seleccionando más series de las permitidas
            if (checkedCount > newQuantity) {
                // Desmarcar los últimos checkboxes seleccionados para que coincida con la cantidad
                let toUncheck = checkedCount - newQuantity;
                for (let i = checkboxes.length - 1; i >= 0 && toUncheck > 0; i--) {
                    if (checkboxes[i].checked) {
                        checkboxes[i].checked = false;
                        toUncheck--;
                    }
                }
            }
        }

        // Añadir listeners a los checkboxes
        document.querySelectorAll('input[name="selected_series[]"]').forEach(checkbox => {
            checkbox.removeEventListener('change', handleCheckboxChange);
            checkbox.addEventListener('change', handleCheckboxChange);
        });

        function handleCheckboxChange() {
            const newQuantityElement = document.getElementById('newQuantity');
            if (!newQuantityElement) return;
            
            const newQuantity = parseInt(newQuantityElement.value);
            const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
            let checkedCount = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) checkedCount++;
            });

            // Si se excede la cantidad permitida, desmarcar este checkbox
            if (checkedCount > newQuantity && this.checked) {
                this.checked = false;
                alert(`Solo puedes seleccionar ${newQuantity} series.`);
            }
        }

        // Inicializar contador
        updateSelectedCounter();

        console.log('Abriendo modal de editar series');
        // Mostrar modal
        if (typeof bootstrap !== 'undefined') {
            // Si Bootstrap está disponible
            try {
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
                console.log('Modal abierto con Bootstrap');
            } catch (error) {
                console.error('Error al abrir modal con Bootstrap:', error);
                abrirModalManualmente(modal);
            }
        } else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            // Si jQuery está disponible
            try {
                $(modal).modal('show');
                console.log('Modal abierto con jQuery');
            } catch (error) {
                console.error('Error al abrir modal con jQuery:', error);
                abrirModalManualmente(modal);
            }
        } else {
            // Mostrar modal manualmente
            abrirModalManualmente(modal);
        }

        // Función para abrir el modal manualmente
        function abrirModalManualmente(modalElement) {
            try {
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
                document.body.classList.add('modal-open');
                
                // Añadir backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                console.log('Modal abierto manualmente');
                
                // Manejar botones de cierre
                modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .close, .btn-secondary').forEach(button => {
                    button.addEventListener('click', function() {
                        modalElement.style.display = 'none';
                        modalElement.classList.remove('show');
                        document.body.classList.remove('modal-open');
                        const existingBackdrop = document.querySelector('.modal-backdrop');
                        if (existingBackdrop) existingBackdrop.remove();
                    });
                });
            } catch (error) {
                console.error('Error al abrir modal manualmente:', error);
                alert('Error al abrir el modal. Por favor, intenta nuevamente.');
            }
        }

        // Manejar clic en el botón de guardar
        const saveButton = document.getElementById('saveSeriesChanges');
        if (saveButton) {
            saveButton.removeEventListener('click', handleSaveClick);
            saveButton.addEventListener('click', handleSaveClick);
        }

        function handleSaveClick() {
            const selectedCheckboxes = document.querySelectorAll('input[name="selected_series[]"]:checked');
            const newQuantityElement = document.getElementById('newQuantity');
            if (!newQuantityElement) return;
            
            const newQuantity = parseInt(newQuantityElement.value);

            if (selectedCheckboxes.length !== newQuantity) {
                alert(`Debes seleccionar exactamente ${newQuantity} series.`);
                return;
            }

            // Enviar formulario
            const editSeriesForm = document.getElementById('editSeriesForm');
            if (editSeriesForm) editSeriesForm.submit();
        }
    }
    
    // Manejador para evento de envío de formularios
    function handleFormSubmit(event) {
        // Encuentra la fila que contiene el formulario
        const row = this.closest('tr');
        // Busca el input editable del número de comprobante en la misma fila
        const input = row.querySelector('.comprobante-input');
        if (input) {
            // Crea un campo oculto para enviar el valor
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'numero_comprobante';
            hiddenInput.value = input.value;
            this.appendChild(hiddenInput);
        }
    }
    
    // Función para aplicar filtros
    function aplicarFiltros() {
        const nombre = document.getElementById('nombre')?.value.trim() || '';
        const celular = document.getElementById('celular')?.value.trim() || '';
        const serie = document.getElementById('serie')?.value.trim() || '';
        
        console.log("Aplicando filtros:", { nombre, celular, serie });
        
        // Filtrar usando DataTable API
        if (currentTable) {
            try {
                // Limpiar filtros actuales
                currentTable.search('').columns().search('').draw();
                
                // Si hay valor de serie, usarlo como búsqueda global
                if (serie) {
                    currentTable.search(serie).draw();
                    return;
                }
                
                // Para los demás filtros, aplicar por columna
                let filtrosAplicados = false;
                
                if (nombre) {
                    // Verificar si la columna existe antes de intentar filtrar
                    if (currentTable.columns(1).nodes().length > 0) {
                        currentTable.columns(1).search(nombre, true, false);
                        filtrosAplicados = true;
                    }
                }
                
                if (celular) {
                    // Verificar si la columna existe antes de intentar filtrar
                    if (currentTable.columns(2).nodes().length > 0) {
                        currentTable.columns(2).search(celular, true, false);
                        filtrosAplicados = true;
                    }
                }
                
                // Dibujar la tabla con los filtros aplicados
                currentTable.draw();
            } catch (error) {
                console.error("Error al aplicar filtros:", error);
                // Si hay un error, mostrar mensaje personalizado
                showNoResultsMessage();
            }
        } else {
            console.error("DataTable no está inicializado correctamente");
            // Si DataTable no está disponible, mostrar mensaje personalizado
            showNoResultsMessage();
        }
    }
    
    // Función para limpiar filtros
    function limpiarFiltros() {
        // Limpiar campos de texto
        document.querySelectorAll('#nombre, #celular, #serie').forEach(input => {
            if (input) input.value = '';
        });
        
        // Limpiar filtros de DataTable
        if (currentTable) {
            try {
                currentTable.search('').columns().search('').draw();
            } catch (error) {
                console.error("Error al limpiar filtros:", error);
                // Si hay error, reinicializar DataTable
                initializeDataTable();
            }
        }
    }
    
    // Función para cargar contenido en el contenedor 'tableContent'
    function loadTableContent(url) {
        // Guardar los valores actuales de los filtros
        const filtros = {
            nombre: document.getElementById('nombre')?.value || '',
            celular: document.getElementById('celular')?.value || '',
            serie: document.getElementById('serie')?.value || ''
        };
        
        // Mostrar indicador de carga
        const tableContainer = document.getElementById('tableContent');
        if (!tableContainer) {
            console.error('No se encontró el contenedor tableContent');
            return;
        }
        
        const loadingHTML = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div><p class="mt-2 text-light">Cargando...</p></div>';
        tableContainer.innerHTML = loadingHTML;
        
        // Destruir DataTable existente si existe
        if (currentTable !== null) {
            try {
                currentTable.destroy();
            } catch (error) {
                console.error("Error al destruir DataTable:", error);
            }
            currentTable = null;
        }
        
        console.log('Cargando contenido de URL:', url);
        
        // Hacer la petición AJAX
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            // Actualizar el contenido
            tableContainer.innerHTML = html;
            
            // Verificar si hay tabla antes de inicializar DataTable
            if (document.querySelector('.table')) {
                // Reinicializar DataTable
                initializeDataTable();
                
                // Reinicializar botones de editar series
                setTimeout(() => {
                    reinitializeEditButtons();
                }, 200);
                
                // Restaurar los valores de los filtros
                const nombreInput = document.getElementById('nombre');
                const celularInput = document.getElementById('celular');
                const serieInput = document.getElementById('serie');
                
                if (nombreInput) nombreInput.value = filtros.nombre;
                if (celularInput) celularInput.value = filtros.celular;
                if (serieInput) serieInput.value = filtros.serie;
                
                // Aplicar filtros si había alguno activo
                if (filtros.nombre || filtros.celular || filtros.serie) {
                    setTimeout(() => {
                        aplicarFiltros();
                    }, 100); // Pequeño retraso para asegurar que DataTable está listo
                }
            } else {
                // Si no hay tabla, mostrar mensaje informativo
                tableContainer.innerHTML = '<div class="alert alert-info">No hay datos disponibles para mostrar.</div>';
            }
            
            // Actualizar botones activos
            updateActiveButtons(url);
        })
        .catch(error => {
            console.error('Error cargando contenido:', error);
            tableContainer.innerHTML = '<div class="alert alert-danger">Error al cargar los datos. Por favor, intenta nuevamente.</div>';
        });
    }
    
    // Hacer la función loadTableContent global para acceder desde el evento window.load
    window.loadTableContent = loadTableContent;
    
    // Actualizar estado activo de los botones basado en la URL
    function updateActiveButtons(url) {
        // Resetear todos los botones a estado no activo
        document.querySelectorAll('#btnOriginal, #btnComprobanteDuplicado, #btnPedidoDuplicado, #btnCartonesEliminados').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        });
        
        // Activar el botón correspondiente según la URL
        if (url.includes('comprobantesDuplicados')) {
            const btn = document.getElementById('btnComprobanteDuplicado');
            if (btn) {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-secondary');
            }
        } else if (url.includes('pedidosDuplicados')) {
            const btn = document.getElementById('btnPedidoDuplicado');
            if (btn) {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-secondary');
            }
        } else if (url.includes('cartonesEliminados')) {
            const btn = document.getElementById('btnCartonesEliminados');
            if (btn) {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-secondary');
            }
        } else {
            const btn = document.getElementById('btnOriginal');
            if (btn) {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-secondary');
            }
        }
    }
    
    // Inicializar DataTable al cargar la página
    initializeDataTable();
    
    // Asegurar que los botones de editar series funcionan después de la inicialización
    setTimeout(() => {
        reinitializeEditButtons();
    }, 500);
    
    // Asignar eventos a los botones para cargar diferentes vistas - Método 1
    document.querySelectorAll('#btnOriginal, #btnComprobanteDuplicado, #btnPedidoDuplicado, #btnCartonesEliminados').forEach(btn => {
        btn.onclick = null; // Limpiar cualquier handler existente
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Button clicked:', this.id);
            
            const routes = {
                'btnOriginal': "{{ route('reservas.index') }}",
                'btnComprobanteDuplicado': "{{ route('admin.comprobantesDuplicados') }}",
                'btnPedidoDuplicado': "{{ route('admin.pedidosDuplicados') }}",
                'btnCartonesEliminados': "{{ route('admin.cartonesEliminados') }}"
            };
            
            if (routes[this.id]) {
                loadTableContent(routes[this.id]);
            }
        });
    });
    
    // Método 2 (respaldo) - asignar eventos individuales
    const btnOriginal = document.getElementById('btnOriginal');
    if (btnOriginal) {
        btnOriginal.addEventListener('click', function() {
            loadTableContent("{{ route('reservas.index') }}");
        });
    }

    const btnComprobanteDuplicado = document.getElementById('btnComprobanteDuplicado');
    if (btnComprobanteDuplicado) {
        btnComprobanteDuplicado.addEventListener('click', function() {
            loadTableContent("{{ route('admin.comprobantesDuplicados') }}");
        });
    }

    const btnPedidoDuplicado = document.getElementById('btnPedidoDuplicado');
    if (btnPedidoDuplicado) {
        btnPedidoDuplicado.addEventListener('click', function() {
            loadTableContent("{{ route('admin.pedidosDuplicados') }}");
        });
    }

    const btnCartonesEliminados = document.getElementById('btnCartonesEliminados');
    if (btnCartonesEliminados) {
        btnCartonesEliminados.addEventListener('click', function() {
            loadTableContent("{{ route('admin.cartonesEliminados') }}");
        });
    }
    
    // Asignar eventos a los botones de filtro
    const btnFiltrar = document.getElementById('btnFiltrar');
    if (btnFiltrar) {
        btnFiltrar.addEventListener('click', aplicarFiltros);
    }
    
    const btnLimpiar = document.getElementById('btnLimpiar');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', limpiarFiltros);
    }
    
    // Permitir filtrar con Enter en los campos de texto
    document.querySelectorAll('#nombre, #celular, #serie').forEach(input => {
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    aplicarFiltros();
                }
            });
        }
    });
    
    // Configurar el botón de borrar clientes y el modal de confirmación
    const btnBorrarClientes = document.getElementById('btnBorrarClientes');
    if (btnBorrarClientes) {
        btnBorrarClientes.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Delete button clicked');
            
            // Encontrar el elemento modal
            const modalElement = document.getElementById('confirmDeleteModal');
            
            if (!modalElement) {
                console.error('Modal element not found in DOM');
                alert('Error: Modal de confirmación no encontrado.');
                return;
            }
            
            // Intentar múltiples métodos para abrir el modal
            try {
                // Método 1: Usando la clase bootstrap.Modal si está disponible
                if (typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    console.log('Modal abierto con bootstrap.Modal');
                }
                // Método 2: Usando jQuery si está disponible
                else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                    $(modalElement).modal('show');
                    console.log('Modal abierto con jQuery');
                }
                // Método 3: Manipulación directa del DOM
                else {
                    modalElement.classList.add('show');
                    modalElement.style.display = 'block';
                    document.body.classList.add('modal-open');
                    
                    // Crear backdrop si no existe
                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                    console.log('Modal abierto con manipulación DOM directa');
                    
                    // Manejar botones de cierre dentro del modal
                    const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .close, .btn-secondary');
                    closeButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            modalElement.style.display = 'none';
                            modalElement.classList.remove('show');
                            document.body.classList.remove('modal-open');
                            const existingBackdrop = document.querySelector('.modal-backdrop');
                            if (modalElement.style.display = 'none';
                            modalElement.classList.remove('show');
                            document.body.classList.remove('modal-open');
                            const existingBackdrop = document.querySelector('.modal-backdrop');
                            if (existingBackdrop) {
                                existingBackdrop.remove();
                            }
                        });
                    });
                }
            } catch (error) {
                console.error('Error al abrir el modal:', error);
                alert('Error al abrir el modal de confirmación. Por favor, intenta nuevamente.');
            }
        });
    } else {
        console.error('Delete button not found with ID btnBorrarClientes');
        // Listar todos los botones para ayudar a identificar el correcto
        console.log('Available buttons:', 
            Array.from(document.querySelectorAll('button'))
                .map(b => ({ id: b.id, text: b.textContent, classes: b.className }))
        );
    }
    
    // Configurar la validación del texto de confirmación
    const confirmText = document.getElementById('confirmText');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (confirmText && confirmDeleteBtn) {
        confirmText.addEventListener('input', function() {
            confirmDeleteBtn.disabled = (this.value !== 'BORRAR TODOS LOS CLIENTES');
        });
        
        // Inicializar estado del botón
        confirmDeleteBtn.disabled = true;
    }
    
    // Configurar el formulario de eliminación
    const deleteClientsForm = document.getElementById('deleteClientsForm');
    if (deleteClientsForm) {
        deleteClientsForm.addEventListener('submit', function(event) {
            // Última verificación antes de enviar
            if (!confirmText || confirmText.value !== 'BORRAR TODOS LOS CLIENTES') {
                event.preventDefault();
                alert('Por favor, confirma la acción escribiendo el texto exacto.');
                return false;
            }
            
            // Si todo está correcto, mostrar indicador de carga
            if (confirmDeleteBtn) {
                confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...';
                confirmDeleteBtn.disabled = true;
            }
            
            return true;
        });
    }
    
    // CSS correcciones para la interfaz
    const style = document.createElement('style');
    style.textContent = `
        /* Force horizontal button layout */
        .container .row .col-auto {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }
        
        /* Ensure proper button styles */
        .btn-primary, .btn-secondary {
            display: inline-block !important;
            margin-right: 5px !important;
        }
        
        /* Isolate your container from parent styles */
        .container-fluid.p-0 {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        /* Fix for filter container */
        #filtrosContainer {
            width: 100% !important;
        }
    `;
    document.head.appendChild(style);
});

// Ejecutar después de cargar todo para asegurar funcionamiento en producción
window.addEventListener('load', function() {
    console.log('Window fully loaded - checking UI components');
    
    // Verificar si los botones de editar series están funcionando
    setTimeout(() => {
        if (typeof reinitializeEditButtons === 'function') {
            reinitializeEditButtons();
        } else {
            console.warn('La función reinitializeEditButtons no está disponible en el ámbito global');
            // Reinicializar botones manualmente
            document.querySelectorAll('.edit-series, [data-action="edit-series"], .btn-edit-series, button[data-bs-target="#editSeriesModal"]').forEach(button => {
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Botón de editar series clickeado (desde window.load)');
                    
                    // Intentar acceder a la función handleEditSeries
                    if (typeof handleEditSeries === 'function') {
                        handleEditSeries.call(this);
                    } else {
                        console.error('La función handleEditSeries no está disponible');
                        alert('Error: No se puede editar las series en este momento.');
                    }
                });
            });
        }
    }, 1500);
    
    // Verificar si los botones principales están correctamente configurados
    setTimeout(function() {
        ['btnOriginal', 'btnComprobanteDuplicado', 'btnPedidoDuplicado', 'btnCartonesEliminados'].forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                console.log(`Botón ${btnId} existe`);
                // Asegurar que tiene un controlador de eventos
                const hasClickHandler = btn.onclick || btn._clickListeners;
                if (!hasClickHandler) {
                    console.log(`Añadiendo controlador de eventos a ${btnId}`);
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log(`Clic en botón ${btnId}`);
                        
                        // Definir rutas
                        const routes = {
                            'btnOriginal': "{{ route('reservas.index') }}",
                            'btnComprobanteDuplicado': "{{ route('admin.comprobantesDuplicados') }}",
                            'btnPedidoDuplicado': "{{ route('admin.pedidosDuplicados') }}",
                            'btnCartonesEliminados': "{{ route('admin.cartonesEliminados') }}"
                        };
                        
                        // Cargar contenido si existe la ruta
                        if (routes[btnId] && typeof window.loadTableContent === 'function') {
                            window.loadTableContent(routes[btnId]);
                        } else if (routes[btnId]) {
                            // Si la función no está disponible en el ámbito global
                            location.href = routes[btnId];
                        }
                    });
                }
            } else {
                console.warn(`Botón ${btnId} no encontrado`);
            }
        });
    }, 1000); // Esperar 1 segundo para asegurar que la página está completamente cargada
});
    </script>
@endsection