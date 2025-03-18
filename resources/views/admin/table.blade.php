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
            <td>{{ $reserva->orden_bingo ?? 'N/A' }}</td>
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
                <!-- Cambiado de "revision o aprobado" a solo "revision" -->
                @if($reserva->estado == 'revision')
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

    // ===== INICIALIZACIÓN DE DATATABLES =====
    function initializeDataTable() {
        console.log('Inicializando DataTable...');
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
            
            // IMPORTANTE: Agregar evento para cuando se redibuje la tabla (paginación, filtro, etc.)
            reservasTable.on('draw.dt', function() {
                console.log('Tabla redibujada');
            });
            
            console.log('DataTable inicializado con éxito');
        } catch (error) {
            console.error('Error al inicializar DataTable:', error);
        }
    }
    
    // ===== FUNCIONES PARA CARGAR CONTENIDO =====
    function loadTableContent(url) {
        console.log('Cargando contenido desde URL:', url);
        
        // Si la URL contiene syntax de Laravel, informar al desarrollador
        if (url.includes('{{') && url.includes('}}')) {
            console.warn('La URL contiene sintaxis de Laravel que no se procesará correctamente en el cliente');
        }
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta: ' + response.status);
                }
                return response.text();
            })
            .then(html => {
                // Actualizar el contenido
                const tableContainer = document.querySelector('#table-container') || document.querySelector('main') || document.body;
                
                if (tableContainer) {
                    tableContainer.innerHTML = html;
                    console.log('Contenido actualizado');
                    
                    // Reinicializar DataTable después de cargar el contenido
                    initializeDataTable();
                } else {
                    console.error('No se encontró un contenedor para la tabla');
                }
            })
            .catch(error => {
                console.error('Error al cargar contenido:', error);
            });
    }
    
    // ===== DELEGACIÓN DE EVENTOS =====
    // Usar delegación para manejar clics en toda la página
    document.addEventListener('click', function(e) {
        // Manejar clics en botones de editar series
        const editSeriesButton = e.target.closest('.edit-series');
        if (editSeriesButton) {
            handleEditSeries(editSeriesButton);
        }
        
        // Manejar clics en botones de cerrar modal
        if (e.target.matches('[data-bs-dismiss="modal"], .btn-close')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        }
        
        // Manejar clics en botones de guardar cambios
        if (e.target.id === 'saveSeriesChanges') {
            handleSaveSeriesChanges(e.target);
        }
    });
    
    // Manejar envíos de formularios mediante delegación
    document.addEventListener('submit', function(e) {
        const form = e.target;
        
        // Verificar si es un formulario de aprobar/rechazar
        if (form.action && (form.action.includes('aprobar') || form.action.includes('rechazar'))) {
            e.preventDefault(); // Prevenir envío por defecto
            handleFormSubmit(form);
        }
    });
    
    // Manejar cambios en inputs mediante delegación
    document.addEventListener('change', function(e) {
        // Manejar cambio en cantidad de series
        if (e.target.id === 'newQuantity') {
            handleQuantityChange(e.target);
        }
        
        // Manejar cambio en checkboxes de series
        if (e.target.name === 'selected_series[]') {
            handleSeriesCheckboxChange(e.target);
        }
    });
    
    // Manejar eventos de teclado para filtros
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && 
           (e.target.id === 'nombre' || e.target.id === 'celular' || e.target.id === 'serie')) {
            e.preventDefault();
            aplicarFiltros();
        }
    });
    
    // ===== FUNCIONES DE MANEJO DE EVENTOS =====
    
    // Manejador para editar series
    function handleEditSeries(button) {
        console.log('Abriendo modal de edición de series');
        
        const modal = document.getElementById('editSeriesModal');
        if (!modal) {
            console.error('Error: No se encontró el modal #editSeriesModal en el DOM');
            return;
        }
        
        const seriesData = button.getAttribute('data-series');
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

        const reservaId = button.getAttribute('data-id');
        const bingoId = button.getAttribute('data-bingo-id');
        const cantidad = parseInt(button.getAttribute('data-cantidad'));
        const total = parseInt(button.getAttribute('data-total'));
        const bingoPrice = parseInt(button.getAttribute('data-bingo-precio'));
        const updateUrl = button.getAttribute('data-update-url');

        // Completar datos del formulario
        document.getElementById('reserva_id').value = reservaId;
        document.getElementById('bingo_id').value = bingoId;
        document.getElementById('clientName').textContent = button.getAttribute('data-nombre');
        document.getElementById('newQuantity').value = cantidad;
        document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);

        // Establecer URL del formulario usando el atributo data-update-url
        const form = document.getElementById('editSeriesForm');
        
        // Verificar si la URL existe y no es null o undefined
        if (updateUrl && updateUrl !== 'null' && updateUrl !== 'undefined') {
            form.action = updateUrl;
            console.log('URL del formulario establecida:', updateUrl);
        } else {
            // Construir URL alternativa basada en el ID de la reserva
            const alternativeUrl = `/admin/reservas/${reservaId}/actualizarSeries`;
            form.action = alternativeUrl;
            console.log('URL alternativa establecida:', alternativeUrl);
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

        // Inicializar contador de seleccionados
        updateSelectedCounter();

        // Mostrar modal utilizando Bootstrap 5
        try {
            const bsModal = new bootstrap.Modal(modal);
            window.currentBsModal = bsModal; // Guardar referencia global para acceso posterior
            bsModal.show();
        } catch (error) {
            console.error('Error al mostrar el modal con Bootstrap:', error);
            // Alternativa manual si falla Bootstrap
            showModalManually(modal);
        }
    }
    
    // Función para mostrar modal manualmente (alternativa)
    function showModalManually(modal) {
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
    }
    
    // Función para cerrar modal
    function closeModal(modal) {
        try {
            // Intentar usar Bootstrap primero
            if (window.currentBsModal) {
                window.currentBsModal.hide();
                window.currentBsModal = null;
            } else if (bootstrap && bootstrap.Modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                } else {
                    // Si no hay instancia, cerrar manualmente
                    closeModalManually(modal);
                }
            } else {
                // Si no hay Bootstrap, cerrar manualmente
                closeModalManually(modal);
            }
        } catch (error) {
            console.error('Error al cerrar modal:', error);
            closeModalManually(modal);
        }
    }
    
    // Función para cerrar modal manualmente
    function closeModalManually(modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Eliminar backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
    
    // Manejar cambio en cantidad
    function handleQuantityChange(input) {
        const newQuantity = parseInt(input.value);
        const bingoPrice = parseInt(document.querySelector('[data-bingo-precio]')?.getAttribute('data-bingo-precio') || 0);
        
        // Actualizar el total estimado
        const newTotal = newQuantity * bingoPrice;
        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(newTotal);

        // Actualizar contador de seleccionados
        updateSelectedCounter();
    }
    
    // Manejar cambio en checkbox de series
    function handleSeriesCheckboxChange(checkbox) {
        const newQuantity = parseInt(document.getElementById('newQuantity').value);
        const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
        let checkedCount = 0;

        checkboxes.forEach(cb => {
            if (cb.checked) checkedCount++;
        });

        // Si se excede la cantidad permitida, desmarcar este checkbox
        if (checkedCount > newQuantity && checkbox.checked) {
            checkbox.checked = false;
            alert(`Solo puedes seleccionar ${newQuantity} series.`);
        }
    }
    
    // Actualizar contador de seleccionados
    function updateSelectedCounter() {
        const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
        const newQuantity = parseInt(document.getElementById('newQuantity')?.value || 0);
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
    
    // Manejar clic en guardar cambios
    function handleSaveSeriesChanges() {
        const selectedCheckboxes = document.querySelectorAll('input[name="selected_series[]"]:checked');
        const newQuantity = parseInt(document.getElementById('newQuantity').value);
        const form = document.getElementById('editSeriesForm');

        if (selectedCheckboxes.length !== newQuantity) {
            alert(`Debes seleccionar exactamente ${newQuantity} series.`);
            return;
        }

        // Verificar que la acción del formulario esté establecida
        if (!form.action || form.action.includes('null')) {
            // Si no hay acción establecida o contiene 'null', establecer una predeterminada
            const reservaId = document.getElementById('reserva_id').value;
            console.error('Error: URL del formulario no válida', form.action);
            form.action = `/admin/reservas/${reservaId}/actualizarSeries`;
            console.log('Corrigiendo URL del formulario a:', form.action);
        }

        // Enviar formulario
        form.submit();
    }
    
    // Manejador para evento de envío de formularios
    function handleFormSubmit(form) {
        // Encuentra la fila que contiene el formulario
        const row = form.closest('tr');
        // Busca el input editable del número de comprobante en la misma fila
        const input = row.querySelector('.comprobante-input');
        if (input) {
            // Crea un campo oculto para enviar el valor
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'numero_comprobante';
            hiddenInput.value = input.value;
            form.appendChild(hiddenInput);
        }
        
        // Continuar con el envío normal
        form.submit();
    }
    
    // Función para aplicar filtros
    function aplicarFiltros() {
        console.log('Aplicando filtros...');
        // Implementa según necesites
        
        // Si tienes un formulario de filtros, podrías enviarlo aquí
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.submit();
        }
    }
    
    // Función para limpiar filtros
    function limpiarFiltros() {
        console.log('Limpiando filtros...');
        // Implementa según necesites
        
        // Limpiar campos de filtro
        const filterInputs = document.querySelectorAll('#nombre, #celular, #serie');
        filterInputs.forEach(input => {
            if (input) input.value = '';
        });
        
        // Aplicar filtros limpios
        aplicarFiltros();
    }

    // ===== INICIALIZACIÓN Y EVENTOS DE BOTONES =====
    
    // Inicializar DataTable al cargar la página
    initializeDataTable();
    
    // Asignar eventos a los botones para cargar diferentes vistas
    document.getElementById('btnOriginal')?.addEventListener('click', function() { 
        loadTableContent("{{ route('reservas.index') }}"); 
    });
    
    document.getElementById('btnComprobanteDuplicado')?.addEventListener('click', function() { 
        loadTableContent("{{ route('admin.comprobantesDuplicados') }}"); 
    });
    
    document.getElementById('btnPedidoDuplicado')?.addEventListener('click', function() { 
        loadTableContent("{{ route('admin.pedidosDuplicados') }}"); 
    });
    
    document.getElementById('btnCartonesEliminados')?.addEventListener('click', function() { 
        loadTableContent("{{ route('admin.cartonesEliminados') }}"); 
    });

    // Asignar eventos a los botones de filtro
    document.getElementById('btnFiltrar')?.addEventListener('click', aplicarFiltros);
    document.getElementById('btnLimpiar')?.addEventListener('click', limpiarFiltros);
});
</script>