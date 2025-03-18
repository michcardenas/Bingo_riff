<table class="table table-dark table-striped align-middle">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Celular</th>
            <th>Fecha</th>
            <th># Cartones</th>
            <th>Series</th>
            <th>Bingo</th>
            <th>Total</th>
            <th>Comprobante</th>
            <th># Comprobante</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="reservas-tbody">
        @forelse($reservas as $reserva)
        <tr class="reserva-row"
            data-estado="{{ $reserva->estado }}"
            data-nombre="{{ $reserva->nombre }}"
            data-celular="{{ $reserva->celular }}"
            data-series="{{ is_string($reserva->series) ? $reserva->series : json_encode($reserva->series) }}">
            <td>{{ $reserva->id }}</td>
            <td>{{ $reserva->nombre }}</td>
            <td>{{ $reserva->celular }}</td>
            <td>{{ $reserva->created_at->format('d/m/Y H:i') }}</td>
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
                @if($reserva->estado == 'revision')
                <input type="text" class="form-control form-control-sm bg-dark text-white border-light comprobante-input" value="{{ $reserva->numero_comprobante ?? '' }}">
                @else
                <input type="text" class="form-control form-control-sm bg-dark text-white border-light" value="{{ $reserva->numero_comprobante ?? '' }}">
                @endif
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
                <!-- Botón para editar series -->
                @if($reserva->estado == 'revision' || $reserva->estado == 'aprobado')
                <button type="button" class="btn btn-sm btn-warning mb-1 edit-series"
                    data-id="{{ $reserva->id }}"
                    data-update-url="{{ route('reservas.update-series', $reserva->id) }}"
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
                <form action="{{ route('reservas.aprobar', $reserva->id) }}" method="POST" class="d-inline aprobar-form mt-1">
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
                <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline mt-1">
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
        <tr id="no-results-row" style="display: none;">
            <td colspan="12" class="text-center">No hay reservas que coincidan con los filtros seleccionados.</td>
        </tr>
        <tr id="empty-row">
            <td colspan="12" class="text-center">No hay reservas registradas.</td>
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
        let currentTable = null;

        // Debug info for production
        console.log('DOM loaded');
        console.log('All buttons:', Array.from(document.querySelectorAll('button')).map(b => b.id));
        console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Available' : 'Not available');
        console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'Not available');
        console.log('DataTables:', typeof $.fn.DataTable !== 'undefined' ? 'Available' : 'Not available');

        // CSS correcciones para la interfaz - aplicar inmediatamente
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
            display: block !important;
        }
        
        /* Fix for filter container */
        #filtrosContainer {
            width: 100% !important;
        }
        
        /* Ensure top menu is visible and horizontal */
        .container.mb-4 .row .col-auto {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
        }
    `;
        document.head.appendChild(style);

        // Inicializar DataTable directamente en la tabla existente
        function initializeDataTable() {
            console.log('Inicializando DataTable...');

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
                    order: [
                        [0, 'desc']
                    ], // Ordenar por ID de forma descendente
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

                // Configurar eventos para elementos dentro de la tabla
                setTimeout(function() {
                    setupTableEvents();
                }, 100);

            } catch (error) {
                console.error('Error al inicializar DataTable:', error);
                // En caso de error, mostrar mensaje personalizado
                showNoResultsMessage();
            }
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
                button.removeEventListener('click', handleEditSeries); // Eliminar listeners anteriores
                button.addEventListener('click', handleEditSeries);
                console.log('Evento configurado para botón editar series');
            });

            // Configurar eventos para formularios de aprobación/rechazo
            document.querySelectorAll('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]').forEach(form => {
                form.removeEventListener('submit', handleFormSubmit); // Eliminar listeners anteriores
                form.addEventListener('submit', handleFormSubmit);
            });
        }

        // Manejador para el evento de editar series
        function handleEditSeries(e) {
            console.log('Botón editar series clickeado');
            e.preventDefault();

            const modal = document.getElementById('editSeriesModal');
            if (!modal) {
                console.error('No se encontró el modal editSeriesModal');
                alert('Error: No se encontró el modal para editar series');
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

            console.log('Datos obtenidos:', {
                reservaId,
                bingoId,
                cantidad,
                total,
                series
            });

            // Completar datos del formulario
            document.getElementById('reserva_id').value = reservaId;
            document.getElementById('bingo_id').value = bingoId;
            document.getElementById('clientName').textContent = this.getAttribute('data-nombre');
            document.getElementById('newQuantity').value = cantidad;
            document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
            document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);

            // Establecer URL del formulario
            const form = document.getElementById('editSeriesForm');
            form.action = this.getAttribute('data-update-url') || `/admin/reservas/${reservaId}/update-series`;

            // Mostrar series actuales y crear checkboxes
            const currentSeriesDiv = document.getElementById('currentSeries');
            const seriesCheckboxesDiv = document.getElementById('seriesCheckboxes');

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
            const newQuantityInput = document.getElementById('newQuantity');
            newQuantityInput.removeEventListener('change', handleQuantityChange);
            newQuantityInput.addEventListener('change', handleQuantityChange);

            function handleQuantityChange() {
                const newQuantity = parseInt(this.value);

                // Actualizar el total estimado
                const newTotal = newQuantity * bingoPrice;
                document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(newTotal);

                // Actualizar contador
                updateSelectedCounter();
            }

            // Función para actualizar contador de seleccionados
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

            // Añadir listeners a los checkboxes
            document.querySelectorAll('input[name="selected_series[]"]').forEach(checkbox => {
                checkbox.removeEventListener('change', handleCheckboxChange);
                checkbox.addEventListener('change', handleCheckboxChange);
            });

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

            // Inicializar contador
            updateSelectedCounter();

            // Mostrar modal
            console.log('Intentando abrir modal...');
            try {
                if (typeof bootstrap !== 'undefined') {
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    console.log('Modal abierto con Bootstrap');
                } else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                    $(modal).modal('show');
                    console.log('Modal abierto con jQuery');
                } else {
                    // Método manual
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');

                    // Crear backdrop si no existe
                    let backdrop = document.querySelector('.modal-backdrop');
                    if (!backdrop) {
                        backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                    console.log('Modal abierto manualmente');

                    // Manejar botones de cierre
                    modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .close, .btn-secondary').forEach(button => {
                        button.addEventListener('click', function() {
                            modal.style.display = 'none';
                            modal.classList.remove('show');
                            document.body.classList.remove('modal-open');
                            const existingBackdrop = document.querySelector('.modal-backdrop');
                            if (existingBackdrop) existingBackdrop.remove();
                        });
                    });
                }
            } catch (error) {
                console.error('Error al abrir modal:', error);
            }

            // Manejar clic en el botón de guardar
            const saveButton = document.getElementById('saveSeriesChanges');
            saveButton.removeEventListener('click', handleSaveClick);
            saveButton.addEventListener('click', handleSaveClick);

            function handleSaveClick() {
                const selectedCheckboxes = document.querySelectorAll('input[name="selected_series[]"]:checked');
                const newQuantity = parseInt(document.getElementById('newQuantity').value);

                if (selectedCheckboxes.length !== newQuantity) {
                    alert(`Debes seleccionar exactamente ${newQuantity} series.`);
                    return;
                }

                // Enviar formulario
                document.getElementById('editSeriesForm').submit();
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

            console.log("Aplicando filtros:", {
                nombre,
                celular,
                serie
            });

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

        // Asignar eventos a los botones para cargar diferentes vistas
        function setupButtonEvents() {
            console.log('Configurando eventos de botones de menú');

            // Limpiar cualquier evento existente
            document.querySelectorAll('#btnOriginal, #btnComprobanteDuplicado, #btnPedidoDuplicado, #btnCartonesEliminados').forEach(btn => {
                const newBtn = btn.cloneNode(true);
                if (btn.parentNode) {
                    btn.parentNode.replaceChild(newBtn, btn);
                }
            });

            // Asignar nuevos eventos
            const btnOriginal = document.getElementById('btnOriginal');
            if (btnOriginal) {
                btnOriginal.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Botón Original clickeado');
                    loadTableContent("{{ route('reservas.index') }}");
                });
            }

            const btnComprobanteDuplicado = document.getElementById('btnComprobanteDuplicado');
            if (btnComprobanteDuplicado) {
                btnComprobanteDuplicado.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Botón Comprobante Duplicado clickeado');
                    loadTableContent("{{ route('admin.comprobantesDuplicados') }}");
                });
            }

            const btnPedidoDuplicado = document.getElementById('btnPedidoDuplicado');
            if (btnPedidoDuplicado) {
                btnPedidoDuplicado.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Botón Pedido Duplicado clickeado');
                    loadTableContent("{{ route('admin.pedidosDuplicados') }}");
                });
            }

            const btnCartonesEliminados = document.getElementById('btnCartonesEliminados');
            if (btnCartonesEliminados) {
                btnCartonesEliminados.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Botón Cartones Eliminados clickeado');
                    loadTableContent("{{ route('admin.cartonesEliminados') }}");
                });
            }
        }

        // Configurar eventos de botones
        setupButtonEvents();

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
                .map(b => ({
                    id: b.id,
                    text: b.textContent,
                    classes: b.className
                }))
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
                    confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="confirmDeleteBtn.innerHTML = ' < span class = "spinner-border spinner-border-sm"
                    role = "status"
                    aria - hidden = "true" > < /span> Eliminando...';
                    confirmDeleteBtn.disabled = true;
                }

                return true;
            });
        }
    });

    // Ejecutar después de cargar todo para asegurar funcionamiento en producción
    window.addEventListener('load', function() {
        console.log('Window fully loaded - checking UI components');

        // CSS correcciones para la interfaz (repetir para asegurar aplicación)
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
            display: block !important;
        }
        
        /* Fix for filter container */
        #filtrosContainer {
            width: 100% !important;
        }
        
        /* Ensure top menu is visible and horizontal */
        .container.mb-4 .row .col-auto {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
        }

        /* Fix menu buttons */
        #btnOriginal, #btnComprobanteDuplicado, #btnPedidoDuplicado, #btnCartonesEliminados {
            margin-bottom: 5px !important;
        }
    `;
        document.head.appendChild(style);

        // Verificar si los botones de menú funcionan correctamente
        setTimeout(function() {
            ['btnOriginal', 'btnComprobanteDuplicado', 'btnPedidoDuplicado', 'btnCartonesEliminados'].forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    console.log(`Verificando botón ${btnId}`);

                    // Asegurar que tiene un controlador de eventos
                    if (!btn.onclick) {
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
                                location.href = routes[btnId];
                            }
                        });
                    }
                } else {
                    console.warn(`Botón ${btnId} no encontrado`);
                }
            });
        }, 500);

        // También verificar botones de editar series
        setTimeout(function() {
            console.log('Verificando botones de editar series');
            const editButtons = document.querySelectorAll('.edit-series');

            if (editButtons.length) {
                console.log(`Encontrados ${editButtons.length} botones de editar series`);

                editButtons.forEach(button => {
                    const newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);

                    newButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Botón de editar series clickeado');

                        const modal = document.getElementById('editSeriesModal');
                        if (!modal) {
                            console.error('No se encontró el modal editSeriesModal');
                            alert('Error: No se encontró el modal para editar series');
                            return;
                        }

                        const seriesData = this.getAttribute('data-series');
                        let series = [];

                        try {
                            series = JSON.parse(seriesData);
                        } catch (e) {
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

                        console.log('Llenando formulario con datos:', {
                            reservaId,
                            bingoId,
                            cantidad,
                            total,
                            bingoPrice
                        });

                        // Completar datos del formulario
                        document.getElementById('reserva_id').value = reservaId;
                        document.getElementById('bingo_id').value = bingoId;
                        document.getElementById('clientName').textContent = this.getAttribute('data-nombre');
                        document.getElementById('newQuantity').value = cantidad;
                        document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
                        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);

                        // Establecer URL del formulario
                        const form = document.getElementById('editSeriesForm');
                        form.action = this.getAttribute('data-update-url') || `/admin/reservas/${reservaId}/update-series`;

                        // Mostrar series actuales y crear checkboxes
                        const currentSeriesDiv = document.getElementById('currentSeries');
                        const seriesCheckboxesDiv = document.getElementById('seriesCheckboxes');

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

                        // Configurar comportamiento de cantidad y selección
                        const newQuantityInput = document.getElementById('newQuantity');

                        function handleQuantityChange() {
                            const newQuantity = parseInt(this.value);
                            const newTotal = newQuantity * bingoPrice;
                            document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(newTotal);
                            updateSelectedCounter();
                        }

                        function updateSelectedCounter() {
                            const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
                            const newQuantity = parseInt(document.getElementById('newQuantity').value);
                            let checkedCount = 0;

                            checkboxes.forEach(cb => {
                                if (cb.checked) checkedCount++;
                            });

                            if (checkedCount > newQuantity) {
                                let toUncheck = checkedCount - newQuantity;
                                for (let i = checkboxes.length - 1; i >= 0 && toUncheck > 0; i--) {
                                    if (checkboxes[i].checked) {
                                        checkboxes[i].checked = false;
                                        toUncheck--;
                                    }
                                }
                            }
                        }

                        function handleCheckboxChange() {
                            const newQuantity = parseInt(document.getElementById('newQuantity').value);
                            const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
                            let checkedCount = 0;

                            checkboxes.forEach(cb => {
                                if (cb.checked) checkedCount++;
                            });

                            if (checkedCount > newQuantity && this.checked) {
                                this.checked = false;
                                alert(`Solo puedes seleccionar ${newQuantity} series.`);
                            }
                        }

                        // Configurar eventos
                        newQuantityInput.addEventListener('change', handleQuantityChange);

                        document.querySelectorAll('input[name="selected_series[]"]').forEach(checkbox => {
                            checkbox.addEventListener('change', handleCheckboxChange);
                        });

                        updateSelectedCounter();

                        // Abrir el modal usando el método más compatible
                        try {
                            if (typeof bootstrap !== 'undefined') {
                                const modalInstance = new bootstrap.Modal(modal);
                                modalInstance.show();
                            } else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                                $(modal).modal('show');
                            } else {
                                modal.style.display = 'block';
                                modal.classList.add('show');
                                document.body.classList.add('modal-open');

                                // Crear backdrop
                                const backdrop = document.createElement('div');
                                backdrop.className = 'modal-backdrop fade show';
                                document.body.appendChild(backdrop);
                            }
                        } catch (error) {
                            console.error('Error al abrir modal:', error);
                        }

                        // Configurar el botón guardar
                        const saveButton = document.getElementById('saveSeriesChanges');
                        if (saveButton) {
                            saveButton.onclick = function() {
                                const selectedCheckboxes = document.querySelectorAll('input[name="selected_series[]"]:checked');
                                const newQuantity = parseInt(document.getElementById('newQuantity').value);

                                if (selectedCheckboxes.length !== newQuantity) {
                                    alert(`Debes seleccionar exactamente ${newQuantity} series.`);
                                    return;
                                }

                                document.getElementById('editSeriesForm').submit();
                            };
                        }
                    });
                });
            } else {
                console.warn('No se encontraron botones de editar series');
            }
        }, 1000);
    });
</script>