<table class="table table-dark table-striped align-middle">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Celular</th>
            <th>Cantidad</th>
            <th>Series</th>
            <th>Bingo</th>
            <th>Total</th>
            <th>Comprobante</th>
            <th># Comprobante</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reservas as $reserva)
        <tr>
            <td>{{ $reserva->id }}</td>
            <td>{{ $reserva->nombre }}</td>
            <td>{{ $reserva->celular }}</td>
            <td>{{ $reserva->cantidad }}</td>
            <td>
    @php
        $seriesData = $reserva->series;
        
        // Verificar si es una cadena JSON y convertirla a array si es necesario
        if (is_string($seriesData) && json_decode($seriesData) !== null) {
            $seriesData = json_decode($seriesData, true);
        }
    @endphp
    
    @if(is_array($seriesData))
        {{ implode(', ', $seriesData) }}
    @else
        {{ $seriesData }}
    @endif
</td>
<td>
                @if($reserva->bingo)
                    {{ $reserva->bingo->nombre }}
                @else
                    <span class="text-warning">Sin asignar</span>
                @endif
            </td>
            <td>${{ number_format($reserva->total, 0, ',', '.') }} Pesos</td>
            <td>
            @if($reserva->comprobante)
                @php
                // Decodifica el JSON; si ya es array, lo usa tal cual
                $comprobantes = is_array($reserva->comprobante) ? $reserva->comprobante : json_decode($reserva->comprobante, true);
                @endphp

                @if(is_array($comprobantes) && count($comprobantes) > 0)
                @foreach($comprobantes as $index => $comprobante)
                <a href="{{ asset('storage/' . $comprobante) }}" target="_blank" class="btn btn-sm btn-light">
                    Ver comprobante {{ $index + 1 }}
                </a>
                @endforeach
                @else
                <span class="text-danger">Sin comprobante</span>
                @endif
                @else
                <span class="text-danger">Sin comprobante</span>
                @endif
            </td>
            <td>
                <input type="text" class="form-control form-control-sm bg-dark text-white border-light" 
                       value="{{ $reserva->numero_comprobante ?? '' }}" readonly>
            </td>
            <td>
                @if($reserva->estado == 'revision')
                    <span class="badge bg-warning text-dark">Revisión</span>
                @elseif($reserva->estado == 'aprobado')
                    <span class="badge bg-success">Aprobado</span>
                @elseif($reserva->estado == 'rechazado')
                    <span class="badge bg-danger">Rechazado</span>
                @else
                    <span class="badge bg-secondary">{{ ucfirst($reserva->estado) }}</span>
                @endif
            </td>
            <td>
                @if($reserva->estado == 'revision' || $reserva->estado == 'aprobado')
                    <button type="button" class="btn btn-sm btn-warning mb-1 edit-series"
                        data-id="{{ $reserva->id }}"
                        data-nombre="{{ $reserva->nombre }}"
                        data-series="{{ is_string($reserva->series) ? $reserva->series : json_encode($reserva->series) }}"
                        data-cantidad="{{ $reserva->cantidad }}"
                        data-total="{{ $reserva->total }}"
                        data-bingo-id="{{ $reserva->bingo_id }}"
                        data-bingo-precio="{{ $reserva->bingo ? $reserva->bingo->precio : 0 }}">
                        <i class="bi bi-pencil-square"></i> Editar Series
                    </button>
                @endif

                @if($reserva->estado == 'revision')
                    <form action="{{ route('reservas.aprobar', $reserva->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-success me-1">Aprobar</button>
                    </form>
                    <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-danger">Rechazar</button>
                    </form>
                @elseif($reserva->estado == 'aprobado')
                    <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-danger">Rechazar</button>
                    </form>
                @elseif($reserva->estado == 'rechazado')
                    <span class="text-white">Rechazado</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="text-center">No hay cartones eliminados.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- Modal para editar series -->
<div class="modal fade" id="editSeriesModal" tabindex="-1" aria-labelledby="editSeriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="editSeriesModalLabel">Editar Series</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSeriesForm" action="" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="reserva_id" id="reserva_id">
                    <input type="hidden" name="bingo_id" id="bingo_id">

                    <div class="mb-3">
                        <label class="form-label">Nombre del cliente:</label>
                        <p id="clientName" class="fs-5"></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Series actuales:</label>
                        <div id="currentSeries" class="mb-3">
                            <!-- Series serán mostradas aquí -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="newQuantity" class="form-label">Nueva cantidad de cartones:</label>
                        <input type="number" class="form-control bg-dark text-white" id="newQuantity" name="new_quantity" min="1">
                        <small class="text-muted">Total actual: <span id="currentTotal"></span> pesos</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Seleccionar series a mantener:</label>
                        <div id="seriesCheckboxes" class="row">
                            <!-- Checkboxes serán generados dinámicamente -->
                        </div>
                    </div>

                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Importante:</strong> Las series que no selecciones quedarán disponibles para nuevas compras. El total a pagar se actualizará automáticamente.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveSeriesChanges">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Agregar script para el filtrado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let reservasTable = null;
    let tipoActual = 'todas';
    let modalInstance = null;
    
    // ===== INICIALIZACIÓN DE DATATABLES =====
    function initializeDataTable() {
        // Seleccionar la tabla principal
        const table = document.querySelector('table');
        
        if (!table) {
            console.error('No se encontró la tabla para inicializar DataTables');
            return;
        }
        
        // Verificar si DataTable ya está inicializado y destruirlo para recrearlo
        if ($.fn.DataTable.isDataTable(table)) {
            $(table).DataTable().destroy();
        }
        
        // Inicializar DataTable
        try {
            reservasTable = $(table).DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                    emptyTable: "No hay resultados que concuerden con tu filtro",
                    zeroRecords: "No hay resultados que concuerden con tu filtro"
                },
                order: [[0, 'desc']], // Ordenar por ID de forma descendente
                responsive: true,
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                initComplete: function() {
                    // Añadir clases personalizadas para el tema oscuro
                    $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').addClass('text-white');
                    $('.dataTables_wrapper .form-control').addClass('bg-dark text-white border-secondary');
                    $('.dataTables_wrapper .page-link').addClass('bg-dark text-white border-secondary');
                    
                    console.log('DataTable inicializado correctamente');
                }
            });
            
            // Importante: Agregar evento para cuando se redibuje la tabla (cambio de página, filtro, etc.)
            reservasTable.on('draw.dt', function() {
                console.log('Tabla redibujada - configurando eventos');
                setupTableEvents();
            });
            
            // Configurar eventos después de la inicialización inicial
            setupTableEvents();
            
            console.log('DataTable inicializado con éxito');
        } catch (error) {
            console.error('Error al inicializar DataTable:', error);
        }
    }
    
    // ===== CONFIGURACIÓN DE EVENTOS =====
    function setupTableEvents() {
        // Usar delegación de eventos para manejar elementos dinámicos
        const tableContainer = document.getElementById('tableContent') || document.body;
        
        // Remover listeners previos para evitar duplicados (solo si es necesario)
        tableContainer.removeEventListener('click', handleTableContainerClick);
        tableContainer.removeEventListener('submit', handleTableContainerSubmit);
        
        // Agregar nuevos listeners con delegación de eventos
        tableContainer.addEventListener('click', handleTableContainerClick);
        tableContainer.addEventListener('submit', handleTableContainerSubmit);
    }
    
        function handleTableContainerClick(e) {
        // Verificar si el clic fue en un botón edit-series o su icono hijo
        const editButton = e.target.closest('.edit-series');
        if (editButton) {
            handleEditSeries.call(editButton, e);
        }
        
        // Verificar si el clic fue en un botón para borrar cliente
        const deleteButton = e.target.closest('.delete-cliente');
        if (deleteButton) {
            handleDeleteCliente.call(deleteButton, e);
        }
        
        // Verificar si el clic fue en un botón para abrir el modal de borrar todos los clientes
        const deleteAllButton = e.target.closest('.delete-all-clients');
        if (deleteAllButton) {
            e.preventDefault();
            openDeleteAllClientsModal();
        }
        
        // Agregar aquí otros elementos que necesiten manejo de clics
    }
    
    function handleTableContainerSubmit(e) {
        // Verificar si el submit fue de un formulario de aprobar/rechazar
        if (e.target.matches('form[action*="aprobar"], form[action*="rechazar"]')) {
            handleFormSubmit.call(e.target, e);
        }
        
        // Verificar si el submit fue de un formulario de eliminación
        if (e.target.matches('form[action*="delete"], form[method="DELETE"], form.delete-form')) {
            const confirmMessage = e.target.getAttribute('data-confirm') || '¿Estás seguro de que deseas eliminar este registro?';
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        }
    }
    
    // ===== MODAL DE EDICIÓN DE SERIES =====
    function handleEditSeries(event) {
        console.log('Abriendo modal de edición de series');
        const modal = document.getElementById('editSeriesModal');
        
        if (!modal) {
            console.error('Error: No se encontró el modal #editSeriesModal en el DOM');
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
        const updateUrl = this.getAttribute('data-update-url');

        // Completar datos del formulario
        document.getElementById('reserva_id').value = reservaId;
        document.getElementById('bingo_id').value = bingoId;
        document.getElementById('clientName').textContent = this.getAttribute('data-nombre');
        document.getElementById('newQuantity').value = cantidad;
        document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);

        // Establecer URL del formulario
        const form = document.getElementById('editSeriesForm');
        if (updateUrl) {
            form.action = updateUrl;
        } else {
            // Si data-update-url no está disponible, usar una ruta por defecto
            form.action = `/admin/reservas/${reservaId}/actualizarSeries`;
        }

        // Mostrar series actuales y crear checkboxes
        const currentSeriesDiv = document.getElementById('currentSeries');
        const seriesCheckboxesDiv = document.getElementById('seriesCheckboxes');

        if (!currentSeriesDiv || !seriesCheckboxesDiv) {
            console.error('Error: No se encontraron los contenedores para las series');
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

        // Configurar evento de cambio de cantidad
        setupQuantityChangeHandler(bingoPrice);
        
        // Configurar eventos para los checkboxes
        setupCheckboxesHandlers();
        
        // Configurar evento para el botón de guardar
        setupSaveButtonHandler();

        // Mostrar modal utilizando Bootstrap 5
        try {
            modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        } catch (error) {
            console.error('Error al mostrar el modal:', error);
            // Intento alternativo si hay un error con Bootstrap
            modal.style.display = 'block';
            modal.classList.add('show');
        }
    }
    
    function setupQuantityChangeHandler(bingoPrice) {
        const newQuantityInput = document.getElementById('newQuantity');
        if (newQuantityInput) {
            // Remover listeners previos para evitar duplicados
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

            // Actualizar contador y validar selecciones
            updateSelectedCounter();
        }
    }
    
    function setupCheckboxesHandlers() {
        // Añadir listeners a los checkboxes
        document.querySelectorAll('input[name="selected_series[]"]').forEach(checkbox => {
            checkbox.removeEventListener('change', handleCheckboxChange);
            checkbox.addEventListener('change', handleCheckboxChange);
        });
    }
    
    function handleCheckboxChange() {
        const newQuantity = parseInt(document.getElementById('newQuantity').value);
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
    
    function updateSelectedCounter() {
        const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
        const newQuantity = parseInt(document.getElementById('newQuantity').value);
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
    
    function setupSaveButtonHandler() {
        const saveButton = document.getElementById('saveSeriesChanges');
        if (saveButton) {
            saveButton.removeEventListener('click', handleSaveClick);
            saveButton.addEventListener('click', handleSaveClick);
        }
    }

    function handleSaveClick() {
        const selectedCheckboxes = document.querySelectorAll('input[name="selected_series[]"]:checked');
        const newQuantity = parseInt(document.getElementById('newQuantity').value);

        if (selectedCheckboxes.length !== newQuantity) {
            alert(`Debes seleccionar exactamente ${newQuantity} series.`);
            return;
        }

        // Enviar formulario
        const form = document.getElementById('editSeriesForm');
        if (form) {
            form.submit();
        } else {
            console.error('Error: No se encontró el formulario para enviar');
        }
    }
    
    // ===== MANEJO DE FORMULARIOS =====
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
    
    // ===== FILTRADO DE DATOS =====
    function aplicarFiltros() {
        const nombre = document.getElementById('nombre')?.value.trim() || '';
        const celular = document.getElementById('celular')?.value.trim() || '';
        const serie = document.getElementById('serie')?.value.trim() || '';
        
        console.log("Aplicando filtros:", { nombre, celular, serie });
        
        // Filtrar usando DataTable API
        if (reservasTable) {
            try {
                // Limpiar filtros actuales
                reservasTable.search('').columns().search('').draw();
                
                // Si hay valor de serie, usarlo como búsqueda global
                if (serie) {
                    reservasTable.search(serie).draw();
                    return;
                }
                
                // Para los demás filtros, aplicar por columna
                let filtrosAplicados = false;
                
                if (nombre) {
                    // Verificar si la columna existe antes de intentar filtrar
                    if (reservasTable.columns(1).nodes().length > 0) {
                        reservasTable.columns(1).search(nombre, true, false);
                        filtrosAplicados = true;
                    }
                }
                
                if (celular) {
                    // Verificar si la columna existe antes de intentar filtrar
                    if (reservasTable.columns(2).nodes().length > 0) {
                        reservasTable.columns(2).search(celular, true, false);
                        filtrosAplicados = true;
                    }
                }
                
                // Dibujar la tabla con los filtros aplicados
                reservasTable.draw();
            } catch (error) {
                console.error("Error al aplicar filtros:", error);
                showNoResultsMessage();
            }
        } else {
            console.error("DataTable no está inicializado correctamente");
            showNoResultsMessage();
        }
    }
    
    function limpiarFiltros() {
        // Limpiar campos de texto
        document.querySelectorAll('#nombre, #celular, #serie').forEach(input => {
            if (input) input.value = '';
        });
        
        // Limpiar filtros de DataTable
        if (reservasTable) {
            try {
                reservasTable.search('').columns().search('').draw();
            } catch (error) {
                console.error("Error al limpiar filtros:", error);
                // Si hay error, reinicializar DataTable
                initializeDataTable();
            }
        }
    }
    
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
    
    // ===== CARGA DE CONTENIDO VÍA AJAX =====
    function loadTableContent(url, actualizarFiltros = true) {
        // Guardar los valores actuales de los filtros si existen en la página
        const filtros = {
            nombre: document.getElementById('nombre')?.value || '',
            celular: document.getElementById('celular')?.value || '',
            serie: document.getElementById('serie')?.value || ''
        };
        
        // Guardar referencia al modal para preservarlo
        const modalElement = document.getElementById('editSeriesModal');
        
        // Mostrar indicador de carga
        const tableContainer = document.getElementById('tableContent');
        if (!tableContainer) {
            console.error('Error: No se encontró el contenedor de la tabla #tableContent');
            return;
        }
        
        const loadingHTML = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div><p class="mt-2 text-light">Cargando...</p></div>';
        tableContainer.innerHTML = loadingHTML;
        
        // Destruir DataTable existente si existe
        if (reservasTable !== null) {
            try {
                reservasTable.destroy();
            } catch (error) {
                console.error("Error al destruir DataTable:", error);
            }
            reservasTable = null;
        }
        
        // Hacer la petición AJAX
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Si el HTML está vacío, mostrar mensaje
            if (html.trim() === '') {
                tableContainer.innerHTML = '<div class="alert alert-warning text-center">No hay resultados que concuerden con tu filtro.</div>';
                return;
            }
            
            // Verificar si el contenido contiene una tabla con datos
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Preservar el modal original (eliminarlo de la respuesta para evitar duplicados)
            const newModalElement = tempDiv.querySelector('#editSeriesModal');
            if (newModalElement) {
                newModalElement.remove();
            }
            
            // Buscar si hay filas de datos (excluyendo encabezados y filas de "no hay resultados")
            const hasDataRows = Array.from(tempDiv.querySelectorAll('table tbody tr')).some(tr => {
                // Ignorar filas con ID específicos que usamos para mensajes
                if (tr.id === 'no-results-row' || tr.id === 'empty-row') {
                    return false;
                }
                
                // Excluir filas que tienen mensaje de "no hay resultados"
                const text = tr.textContent.trim().toLowerCase();
                return !text.includes('no hay') && !text.includes('no se encontraron');
            });
            
            if (!hasDataRows) {
                // Si no hay filas con datos, mostrar un mensaje personalizado
                tableContainer.innerHTML = '<div class="alert alert-warning text-center">No hay resultados que concuerden con tu filtro.</div>';
                return;
            }
            
            // Si hay contenido válido, actualizar el contenedor
            tableContainer.innerHTML = tempDiv.innerHTML;
            
            // Añadir nuevamente el modal original si existía
            if (modalElement) {
                // Verificar si ya está en el DOM para evitar duplicados
                if (!document.getElementById('editSeriesModal')) {
                    document.body.appendChild(modalElement);
                }
            }
            
            // Verificar si hay tabla antes de inicializar DataTable
            if (document.querySelector('table')) {
                // Reinicializar DataTable usando la función existente
                initializeDataTable();
                
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
            
            // Si se debe actualizar el tipo actual basado en la URL
            if (actualizarFiltros) {
                if (url.includes('comprobantesDuplicados')) {
                    tipoActual = 'comprobantes-duplicados';
                } else if (url.includes('pedidosDuplicados')) {
                    tipoActual = 'pedidos-duplicados';
                } else if (url.includes('cartonesEliminados')) {
                    tipoActual = 'cartones-eliminados';
                } else {
                    tipoActual = 'todas';
                }
            }
        })
        .catch(error => {
            console.error('Error cargando contenido:', error);
            tableContainer.innerHTML = '<div class="alert alert-danger">Error al cargar los datos. Por favor, intenta nuevamente.</div>';
        });
    }
    
    function updateActiveButtons(url) {
        // Resetear todos los botones a estado no activo
        document.querySelectorAll('#btnOriginal, #btnComprobanteDuplicado, #btnPedidoDuplicado, #btnCartonesEliminados').forEach(btn => {
            if (btn) {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
            }
        });
        
        // Activar el botón correspondiente según la URL
        let activeButtonId = 'btnOriginal';
        
        if (url.includes('comprobantesDuplicados')) {
            activeButtonId = 'btnComprobanteDuplicado';
        } else if (url.includes('pedidosDuplicados')) {
            activeButtonId = 'btnPedidoDuplicado';
        } else if (url.includes('cartonesEliminados')) {
            activeButtonId = 'btnCartonesEliminados';
        }
        
        const activeButton = document.getElementById(activeButtonId);
        if (activeButton) {
            activeButton.classList.add('btn-primary');
            activeButton.classList.remove('btn-secondary');
        }
    }
    
    // ===== MANEJO DE MODAL PARA BORRAR TODOS LOS CLIENTES =====
    function openDeleteAllClientsModal() {
        const modal = document.getElementById('confirmDeleteModal');
        if (!modal) {
            console.error('Error: No se encontró el modal #confirmDeleteModal');
            return;
        }
        
        // Limpiar el campo de confirmación
        const confirmTextInput = document.getElementById('confirmText');
        if (confirmTextInput) {
            confirmTextInput.value = '';
        }
        
        // Deshabilitar el botón de confirmación
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.disabled = true;
        }
        
        // Mostrar el modal
        try {
            const confirmDeleteModal = new bootstrap.Modal(modal);
            confirmDeleteModal.show();
        } catch (error) {
            console.error('Error al mostrar el modal:', error);
            // Alternativa si hay error con Bootstrap
            modal.style.display = 'block';
            modal.classList.add('show');
        }
        
        // Configurar el evento para el campo de texto de confirmación
        setupConfirmDeleteValidation();
    }
    
    function setupConfirmDeleteValidation() {
        const confirmTextInput = document.getElementById('confirmText');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const expectedText = "BORRAR TODOS LOS CLIENTES";
        
        if (!confirmTextInput || !confirmDeleteBtn) {
            console.error('Error: No se encontraron los elementos necesarios para la validación');
            return;
        }
        
        // Remover listener previo para evitar duplicados
        confirmTextInput.removeEventListener('input', handleConfirmTextInput);
        
        // Agregar nuevo listener
        confirmTextInput.addEventListener('input', handleConfirmTextInput);
        
        function handleConfirmTextInput() {
            // Habilitar el botón solo si el texto coincide exactamente
            const inputText = confirmTextInput.value.trim();
            confirmDeleteBtn.disabled = (inputText !== expectedText);
            
            // Cambiar estilo del campo dependiendo si es correcto
            if (inputText === expectedText) {
                confirmTextInput.classList.add('is-valid');
                confirmTextInput.classList.remove('is-invalid');
            } else if (inputText.length > 0) {
                confirmTextInput.classList.add('is-invalid');
                confirmTextInput.classList.remove('is-valid');
            } else {
                confirmTextInput.classList.remove('is-valid', 'is-invalid');
            }
        }
        
        // Configurar evento para el formulario de eliminación
        const deleteClientsForm = document.getElementById('deleteClientsForm');
        if (deleteClientsForm) {
            // Remover listener previo
            deleteClientsForm.removeEventListener('submit', handleDeleteAllClientsSubmit);
            
            // Agregar nuevo listener
            deleteClientsForm.addEventListener('submit', handleDeleteAllClientsSubmit);
        }
    }
    
    function handleDeleteAllClientsSubmit(event) {
        const confirmTextInput = document.getElementById('confirmText');
        const expectedText = "BORRAR TODOS LOS CLIENTES";
        
        // Verificación adicional de seguridad
        if (!confirmTextInput || confirmTextInput.value.trim() !== expectedText) {
            event.preventDefault();
            alert('Por favor, confirma la eliminación escribiendo el texto exacto solicitado.');
            return false;
        }
        
        // Si todo está correcto, se enviará el formulario normalmente
        console.log('Enviando formulario para eliminar todos los clientes...');
        
        // Opcionalmente, cerrar el modal después de enviar
        try {
            const modal = document.getElementById('confirmDeleteModal');
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        } catch (error) {
            console.error('Error al cerrar el modal:', error);
        }
    }
    
    // ===== MANEJO DE ELIMINACIÓN DE CLIENTES =====
    function handleDeleteCliente(event) {
        event.preventDefault();
        
        // Obtener el ID y nombre del cliente desde los atributos de datos
        const clienteId = this.getAttribute('data-id');
        const clienteNombre = this.getAttribute('data-nombre') || 'este cliente';
        
        if (!clienteId) {
            console.error('Error: No se encontró el ID del cliente para eliminar');
            return;
        }
        
        // Mostrar confirmación antes de eliminar
        if (confirm(`¿Estás seguro de que deseas eliminar a ${clienteNombre}? Esta acción no se puede deshacer.`)) {
            // Crear y enviar formulario dinámicamente
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Construir URL de eliminación
            let deleteUrl = this.getAttribute('data-url');
            if (!deleteUrl) {
                // Construir URL por defecto si no se proporciona
                deleteUrl = `/admin/clientes/${clienteId}`;
            }
            
            form.action = deleteUrl;
            
            // Agregar token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
            }
            
            // Agregar método DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            // Añadir formulario al DOM y enviarlo
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // ===== INICIALIZACIÓN Y ASIGNACIÓN DE EVENTOS =====
    
    // Inicializar DataTable al cargar la página
    initializeDataTable();
    
    // Asignar eventos a los botones para cargar diferentes vistas
    const btnOriginal = document.getElementById('btnOriginal');
    if (btnOriginal) {
        btnOriginal.addEventListener('click', function() {
            const route = this.getAttribute('data-route') || "/admin/reservas";
            loadTableContent(route);
        });
    }

    const btnComprobanteDuplicado = document.getElementById('btnComprobanteDuplicado');
    if (btnComprobanteDuplicado) {
        btnComprobanteDuplicado.addEventListener('click', function() {
            const route = this.getAttribute('data-route') || "/admin/comprobantesDuplicados";
            loadTableContent(route);
        });
    }

    const btnPedidoDuplicado = document.getElementById('btnPedidoDuplicado');
    if (btnPedidoDuplicado) {
        btnPedidoDuplicado.addEventListener('click', function() {
            const route = this.getAttribute('data-route') || "/admin/pedidosDuplicados";
            loadTableContent(route);
        });
    }

    const btnCartonesEliminados = document.getElementById('btnCartonesEliminados');
    if (btnCartonesEliminados) {
        btnCartonesEliminados.addEventListener('click', function() {
            const route = this.getAttribute('data-route') || "/admin/cartonesEliminados";
            loadTableContent(route);
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
    
    // Asegurar que el modal se cierre correctamente
    const modalCloseButtons = document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.closest('.modal')?.id;
            
            if (modalId === 'editSeriesModal' && modalInstance) {
                modalInstance.hide();
            } else if (modalId === 'confirmDeleteModal') {
                try {
                    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                    if (confirmModal) {
                        confirmModal.hide();
                    } else {
                        const modal = document.getElementById('confirmDeleteModal');
                        if (modal) {
                            modal.style.display = 'none';
                            modal.classList.remove('show');
                        }
                    }
                } catch (error) {
                    console.error('Error al cerrar modal:', error);
                    const modal = document.getElementById('confirmDeleteModal');
                    if (modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                    }
                }
            } else {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                }
            }
        });
    });
    
    // Configurar validación para borrar todos los clientes si el modal ya está en el DOM
    if (document.getElementById('confirmDeleteModal')) {
        setupConfirmDeleteValidation();
    }
});
</script>