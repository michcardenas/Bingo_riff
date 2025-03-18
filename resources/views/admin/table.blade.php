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
    let reservasTable = null;
    let tipoActual = 'todas';
    
    // Inicializar DataTable directamente en la tabla existente
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
                    // Configurar eventos para elementos dentro de la tabla después de inicialización
                    setupTableEvents();
                }
            });
            
            console.log('DataTable inicializado con éxito');
        } catch (error) {
            console.error('Error al inicializar DataTable:', error);
        }
    }
    
  // Manejador para el evento de editar series
function handleEditSeries() {
    console.log('🔍 DEBUG: Iniciando handleEditSeries con this:', this);
    
    try {
        const modal = document.getElementById('editSeriesModal');
        if (!modal) {
            console.error('🛑 ERROR: No se encontró el elemento #editSeriesModal');
            return;
        }
        
        console.log('🔍 DEBUG: Modal encontrado correctamente');
        
        const seriesData = this.getAttribute('data-series');
        const reservaId = this.getAttribute('data-id');
        const bingoId = this.getAttribute('data-bingo-id');
        const cantidad = parseInt(this.getAttribute('data-cantidad'));
        const total = parseInt(this.getAttribute('data-total'));
        const bingoPrice = parseInt(this.getAttribute('data-bingo-precio'));
        
        let series = [];

        try {
            if (seriesData) {
                series = JSON.parse(seriesData);
            } else {
                console.warn('⚠️ ADVERTENCIA: data-series está vacío o no definido');
            }
        } catch (e) {
            console.error('🛑 ERROR al parsear series:', e);
            // Si las series no están en formato JSON, intentar convertirlas desde string
            if (typeof seriesData === 'string') {
                series = seriesData.split(',').map(item => item.trim());
            }
        }

        // Completar datos del formulario
        document.getElementById('reserva_id').value = reservaId;
        document.getElementById('bingo_id').value = bingoId;
        document.getElementById('clientName').textContent = this.getAttribute('data-nombre');
        document.getElementById('newQuantity').value = cantidad;
        document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);

        // Establecer URL del formulario usando el atributo data-update-url
        const form = document.getElementById('editSeriesForm');
        form.action = this.getAttribute('data-update-url');

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

        // Mostrar modal - ENFOQUE MÚLTIPLE PARA ASEGURAR QUE SE MUESTRE
        console.log('🔍 DEBUG: Intentando mostrar modal con múltiples métodos');
        
        // 1. Intentar con Bootstrap 5 (método preferido)
        try {
            if (typeof bootstrap !== 'undefined') {
                console.log('🔍 DEBUG: Usando Bootstrap 5');
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
            } else {
                console.log('🔍 DEBUG: Bootstrap no disponible, probando otros métodos');
            }
        } catch (e) {
            console.warn('⚠️ ADVERTENCIA: Error con Bootstrap Modal:', e);
        }
        
        // 2. Intentar con jQuery (fallback)
        try {
            if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
                console.log('🔍 DEBUG: Usando jQuery modal');
                $(modal).modal('show');
            }
        } catch (e) {
            console.warn('⚠️ ADVERTENCIA: Error con jQuery Modal:', e);
        }
        
        // 3. Método manual (último recurso)
        try {
            console.log('🔍 DEBUG: Aplicando método manual para mostrar el modal');
            
            // Agregar clases y estilos necesarios
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('role', 'dialog');
            modal.removeAttribute('aria-hidden');
            
            // Crear backdrop si no existe
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
            
            // Añadir clase al body
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '15px'; // Compensar por scrollbar
        } catch (e) {
            console.error('🛑 ERROR con método manual de modal:', e);
        }
        
        // Verificar si el modal está visible después de 100ms
        setTimeout(() => {
            console.log('🔍 DEBUG: Verificando visibilidad del modal');
            const isVisible = window.getComputedStyle(modal).display !== 'none';
            console.log('🔍 DEBUG: Modal visible:', isVisible);
            
            // Si todavía no es visible, intentar nuevamente con método manual
            if (!isVisible) {
                console.log('🔍 DEBUG: Modal no visible, aplicando método manual adicional');
                modal.style.display = 'block';
                modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                
                // Agregar event listener para cerrar el modal
                const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"], .close, .btn-close');
                closeButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        closeModal();
                    });
                });
                
                // Agregar event listener para cerrar con tecla Escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') closeModal();
                });
                
                // Crear función para cerrar el modal
                window.closeModal = function() {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    
                    // Remover backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    
                    // Restaurar body
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                };
            }
        }, 100);

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
    } catch (error) {
        console.error('🛑 ERROR CRÍTICO en handleEditSeries:', error);
    }
}

// Manejador para evento de envío de formularios
function handleFormSubmit(event) {
    try {
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
    } catch (error) {
        console.error('🛑 ERROR en handleFormSubmit:', error);
    }
}

// Función para configurar eventos de tabla
function setupTableEvents() {
    console.log('🔍 DEBUG: Iniciando setupTableEvents');
    
    try {
        // Configurar eventos para botones de editar series
        const editButtons = document.querySelectorAll('.edit-series');
        console.log('🔍 DEBUG: Encontrados', editButtons.length, 'botones de editar series (.edit-series)');
        
        editButtons.forEach((button, index) => {
            button.addEventListener('click', handleEditSeries);
        });
        
        // Configurar eventos para formularios de aprobación/rechazo
        const formSelectors = '.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]';
        const forms = document.querySelectorAll(formSelectors);
        console.log('🔍 DEBUG: Encontrados', forms.length, 'formularios para aprobar/rechazar');
        
        forms.forEach((form) => {
            form.addEventListener('submit', handleFormSubmit);
        });
        
        console.log('🔍 DEBUG: setupTableEvents completado correctamente');
    } catch (error) {
        console.error('🛑 ERROR CRÍTICO en setupTableEvents:', error);
    }
}

// Ejecutar inicialización cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    setupTableEvents();
});

// También inicializar si el documento ya está cargado
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setupTableEvents();
}
    
    // Función para aplicar filtros
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
    
    // Función para limpiar filtros
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
    
    // Función para cargar contenido de tabla vía AJAX
    function loadTableContent(url, actualizarFiltros = true) {
        // Guardar los valores actuales de los filtros si existen en la página
        const filtros = {
            nombre: document.getElementById('nombre')?.value || '',
            celular: document.getElementById('celular')?.value || '',
            serie: document.getElementById('serie')?.value || ''
        };
        
        // Mostrar indicador de carga
        const tableContainer = document.getElementById('tableContent');
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
            tableContainer.innerHTML = html;
            
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
    
    // Actualizar estado activo de los botones basado en la URL
    function updateActiveButtons(url) {
        // Resetear todos los botones a estado no activo
        document.querySelectorAll('#btnOriginal, #btnComprobanteDuplicado, #btnPedidoDuplicado, #btnCartonesEliminados').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        });
        
        // Activar el botón correspondiente según la URL
        if (url.includes('comprobantesDuplicados')) {
            document.getElementById('btnComprobanteDuplicado').classList.add('btn-primary');
            document.getElementById('btnComprobanteDuplicado').classList.remove('btn-secondary');
        } else if (url.includes('pedidosDuplicados')) {
            document.getElementById('btnPedidoDuplicado').classList.add('btn-primary');
            document.getElementById('btnPedidoDuplicado').classList.remove('btn-secondary');
        } else if (url.includes('cartonesEliminados')) {
            document.getElementById('btnCartonesEliminados').classList.add('btn-primary');
            document.getElementById('btnCartonesEliminados').classList.remove('btn-secondary');
        } else {
            document.getElementById('btnOriginal').classList.add('btn-primary');
            document.getElementById('btnOriginal').classList.remove('btn-secondary');
        }
    }
    
    // Inicializar DataTable al cargar la página
    initializeDataTable();
    
    // Asignar eventos a los botones para cargar diferentes vistas
    document.getElementById('btnOriginal').addEventListener('click', function() {
        loadTableContent("{{ route('reservas.index') }}");
    });

    document.getElementById('btnComprobanteDuplicado').addEventListener('click', function() {
        loadTableContent("{{ route('admin.comprobantesDuplicados') }}");
    });

    document.getElementById('btnPedidoDuplicado').addEventListener('click', function() {
        loadTableContent("{{ route('admin.pedidosDuplicados') }}");
    });

    document.getElementById('btnCartonesEliminados').addEventListener('click', function() {
        loadTableContent("{{ route('admin.cartonesEliminados') }}");
    });
    
    // Asignar eventos a los botones de filtro
    document.getElementById('btnFiltrar').addEventListener('click', aplicarFiltros);
    document.getElementById('btnLimpiar').addEventListener('click', limpiarFiltros);
    
    // Permitir filtrar con Enter en los campos de texto
    document.querySelectorAll('#nombre, #celular, #serie').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                aplicarFiltros();
            }
        });
    });
});
</script>