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
    let reservasTable = null;
    let tipoActual = 'todas';

    // 1. INICIALIZACIÓN DE COMPONENTS
    initializeDataTable();
    initializeModals();
    setupEventListeners();

    // 2. FUNCIONES DE INICIALIZACIÓN

    // Inicializa DataTable
    function initializeDataTable() {
        console.log('Inicializando DataTable...');
        const table = document.querySelector('table');
        
        if (!table) {
            console.error('No se encontró la tabla para inicializar DataTables');
            return;
        }
        
        // Verificar si DataTable ya está inicializado y destruirlo para recrearlo
        if ($.fn.DataTable.isDataTable(table)) {
            $(table).DataTable().destroy();
        }
        
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
            
            console.log('DataTable inicializado con éxito');
        } catch (error) {
            console.error('Error al inicializar DataTable:', error);
        }
    }

    // Inicializa los modales utilizando Bootstrap 5
    function initializeModals() {
        console.log('Inicializando modales...');
        
        // 1. Modal de Editar Series - Configuración mediante evento
        const editSeriesModal = document.getElementById('editSeriesModal');
        if (editSeriesModal) {
            // Crear la instancia de Modal una sola vez
            const editModalInstance = new bootstrap.Modal(editSeriesModal);
            
            // Guardar referencia global
            window.editModalInstance = editModalInstance;
            
            console.log('Modal EditSeries inicializado');
        } else {
            console.error('No se encontró el modal EditSeries');
        }
        
        // 2. Modal de confirmación para borrar clientes
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        if (confirmDeleteModal) {
            // Crear solo la instancia, se abrirá con el evento del botón
            const deleteModalInstance = new bootstrap.Modal(confirmDeleteModal);
            
            // Configurar comportamiento del campo de confirmación
            const confirmText = document.getElementById('confirmText');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            if (confirmText && confirmDeleteBtn) {
                confirmText.addEventListener('input', function() {
                    confirmDeleteBtn.disabled = this.value !== 'BORRAR TODOS LOS CLIENTES';
                });
            }
            
            console.log('Modal BorrarClientes inicializado');
        }
    }
    
    // Configura todos los event listeners de la página
    function setupEventListeners() {
        console.log('Configurando event listeners...');
        
        // 1. Botones de Editar Series
        const editButtons = document.querySelectorAll('.edit-series');
        console.log('Encontrados', editButtons.length, 'botones de editar series');
        
        editButtons.forEach(button => {
            // Eliminar event listeners existentes para evitar duplicados
            button.removeEventListener('click', handleEditSeries);
            // Agregar nuevo event listener
            button.addEventListener('click', handleEditSeries);
        });
        
        // 2. Formularios de aprobación/rechazo
        const forms = document.querySelectorAll('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]');
        console.log('Encontrados', forms.length, 'formularios para aprobar/rechazar');
        
        forms.forEach(form => {
            // Eliminar event listeners existentes para evitar duplicados
            form.removeEventListener('submit', handleFormSubmit);
            // Agregar nuevo event listener
            form.addEventListener('submit', handleFormSubmit);
        });
        
        // 3. Botón Guardar Cambios en el modal de editar series
        const saveButton = document.getElementById('saveSeriesChanges');
        if (saveButton) {
            saveButton.removeEventListener('click', handleSaveClick);
            saveButton.addEventListener('click', handleSaveClick);
        }
        
        // 4. Botón Borrar Clientes
        const btnBorrarClientes = document.getElementById('btnBorrarClientes');
        if (btnBorrarClientes) {
            btnBorrarClientes.addEventListener('click', function() {
                const confirmDeleteModal = document.getElementById('confirmDeleteModal');
                if (confirmDeleteModal) {
                    const deleteModalInstance = new bootstrap.Modal(confirmDeleteModal);
                    deleteModalInstance.show();
                }
            });
        }
        
        // 5. Botones de navegación y filtros
        const btnOriginal = document.getElementById('btnOriginal');
        const btnComprobanteDuplicado = document.getElementById('btnComprobanteDuplicado');
        const btnPedidoDuplicado = document.getElementById('btnPedidoDuplicado');
        const btnCartonesEliminados = document.getElementById('btnCartonesEliminados');
        const btnFiltrar = document.getElementById('btnFiltrar');
        const btnLimpiar = document.getElementById('btnLimpiar');
        
        if (btnOriginal) btnOriginal.addEventListener('click', () => loadTableContent("{{ route('reservas.index') }}"));
        if (btnComprobanteDuplicado) btnComprobanteDuplicado.addEventListener('click', () => loadTableContent("{{ route('admin.comprobantesDuplicados') }}"));
        if (btnPedidoDuplicado) btnPedidoDuplicado.addEventListener('click', () => loadTableContent("{{ route('admin.pedidosDuplicados') }}"));
        if (btnCartonesEliminados) btnCartonesEliminados.addEventListener('click', () => loadTableContent("{{ route('admin.cartonesEliminados') }}"));
        if (btnFiltrar) btnFiltrar.addEventListener('click', aplicarFiltros);
        if (btnLimpiar) btnLimpiar.addEventListener('click', limpiarFiltros);
        
        // 6. Filtrado con tecla Enter
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
        
        console.log('Event listeners configurados correctamente');
    }

    // 3. MANEJADORES DE EVENTOS

    // Maneja el evento de editar series
    function handleEditSeries() {
        console.log('Procesando evento de editar series');
        
        try {
            // Obtener datos del botón
            const reservaId = this.getAttribute('data-id');
            const bingoId = this.getAttribute('data-bingo-id');
            const nombreCliente = this.getAttribute('data-nombre');
            const cantidad = parseInt(this.getAttribute('data-cantidad'));
            const total = parseInt(this.getAttribute('data-total'));
            const bingoPrice = parseInt(this.getAttribute('data-bingo-precio'));
            let seriesData = this.getAttribute('data-series');
            
            console.log('Datos obtenidos del botón:', { reservaId, bingoId, nombreCliente, cantidad, total });
            
            // Parsear series
            let series = [];
            try {
                if (seriesData) {
                    series = JSON.parse(seriesData);
                    console.log('Series parseadas correctamente:', series);
                } else {
                    console.warn('data-series está vacío o no definido');
                }
            } catch (e) {
                console.error('Error al parsear series:', e);
                if (typeof seriesData === 'string') {
                    series = seriesData.split(',').map(item => item.trim());
                    console.log('Series convertidas desde string:', series);
                }
            }
            
            // Rellenar el formulario con los datos
            document.getElementById('reserva_id').value = reservaId;
            document.getElementById('bingo_id').value = bingoId;
            document.getElementById('clientName').textContent = nombreCliente;
            document.getElementById('newQuantity').value = cantidad;
            document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
            document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);
            
            // Establecer URL del formulario
            const form = document.getElementById('editSeriesForm');
            form.action = this.getAttribute('data-update-url');
            
            // Preparar los contenedores para series
            const currentSeriesDiv = document.getElementById('currentSeries');
            const seriesCheckboxesDiv = document.getElementById('seriesCheckboxes');
            
            // Limpiar contenido previo
            currentSeriesDiv.innerHTML = '';
            seriesCheckboxesDiv.innerHTML = '';
            
            // Generar listas y checkboxes para las series
            if (Array.isArray(series) && series.length > 0) {
                // Crear lista de series actuales
                const seriesList = document.createElement('ul');
                seriesList.className = 'list-group';
                
                series.forEach((serie, index) => {
                    // Lista de series actuales
                    const listItem = document.createElement('li');
                    listItem.className = 'list-group-item bg-dark text-white border-light';
                    listItem.textContent = `Serie ${serie}`;
                    seriesList.appendChild(listItem);
                    
                    // Checkboxes para seleccionar series
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
                
                // Agregar event listeners a los checkboxes
                document.querySelectorAll('input[name="selected_series[]"]').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
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
                    });
                });
                
                // Configurar evento para cambio en cantidad
                const newQuantityInput = document.getElementById('newQuantity');
                newQuantityInput.addEventListener('change', function() {
                    const newQuantity = parseInt(this.value);
                    
                    // Actualizar total estimado
                    const newTotal = newQuantity * bingoPrice;
                    document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(newTotal);
                    
                    // Ajustar selección si es necesario
                    const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
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
                });
            } else {
                currentSeriesDiv.textContent = 'No hay series disponibles';
            }
            
            // Mostrar el modal usando la instancia creada en initializeModals
            if (window.editModalInstance) {
                console.log('Mostrando modal con instancia existente');
                window.editModalInstance.show();
            } else {
                console.log('Creando nueva instancia de modal');
                const modal = document.getElementById('editSeriesModal');
                if (modal) {
                    const modalInstance = new bootstrap.Modal(modal);
                    window.editModalInstance = modalInstance;
                    modalInstance.show();
                } else {
                    console.error('No se encontró el elemento del modal');
                }
            }
        } catch (error) {
            console.error('Error crítico en handleEditSeries:', error);
            alert('Ha ocurrido un error al intentar editar las series. Por favor, intenta nuevamente.');
        }
    }

    // Maneja el evento de guardar cambios
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

    // Maneja el evento de enviar formularios
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
            console.error('Error en handleFormSubmit:', error);
        }
    }

    // 4. FUNCIONES DE FILTRADO Y CARGA DE DATOS

    // Aplica filtros a la tabla
    function aplicarFiltros() {
        const nombre = document.getElementById('nombre')?.value.trim() || '';
        const celular = document.getElementById('celular')?.value.trim() || '';
        const serie = document.getElementById('serie')?.value.trim() || '';
        
        console.log("Aplicando filtros:", { nombre, celular, serie });
        
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
                if (nombre && reservasTable.columns(1).nodes().length > 0) {
                    reservasTable.columns(1).search(nombre, true, false);
                }
                
                if (celular && reservasTable.columns(2).nodes().length > 0) {
                    reservasTable.columns(2).search(celular, true, false);
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

    // Limpia los filtros de la tabla
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
                initializeDataTable();
            }
        }
    }

    // Carga el contenido de la tabla vía AJAX
    function loadTableContent(url, actualizarFiltros = true) {
        // Guardar los valores actuales de los filtros
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
            // Verificar contenido y actualizar
            if (html.trim() === '') {
                tableContainer.innerHTML = '<div class="alert alert-warning text-center">No hay resultados disponibles.</div>';
                return;
            }
            
            // Analizar si hay datos en la respuesta
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            const hasDataRows = Array.from(tempDiv.querySelectorAll('table tbody tr')).some(tr => {
                if (tr.id === 'no-results-row' || tr.id === 'empty-row') {
                    return false;
                }
                
                const text = tr.textContent.trim().toLowerCase();
                return !text.includes('no hay') && !text.includes('no se encontraron');
            });
            
            if (!hasDataRows) {
                tableContainer.innerHTML = '<div class="alert alert-warning text-center">No hay resultados que concuerden con tu filtro.</div>';
                return;
            }
            
            // Actualizar contenido
            tableContainer.innerHTML = html;
            
            // Reinicializar componentes
            if (document.querySelector('table')) {
                initializeDataTable();
                
                // Restaurar filtros
                const nombreInput = document.getElementById('nombre');
                const celularInput = document.getElementById('celular');
                const serieInput = document.getElementById('serie');
                
                if (nombreInput) nombreInput.value = filtros.nombre;
                if (celularInput) celularInput.value = filtros.celular;
                if (serieInput) serieInput.value = filtros.serie;
                
                // Aplicar filtros si había alguno activo
                if (filtros.nombre || filtros.celular || filtros.serie) {
                    setTimeout(aplicarFiltros, 100);
                }
                
                // Re-configurar eventos para la nueva tabla
                setupEventListeners();
            } else {
                tableContainer.innerHTML = '<div class="alert alert-info">No hay datos disponibles para mostrar.</div>';
            }
            
            // Actualizar botones activos
            updateActiveButtons(url);
            
            // Actualizar tipo actual
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

    // 5. FUNCIONES AUXILIARES

    // Muestra un mensaje de "No hay resultados" cuando DataTable falla
    function showNoResultsMessage() {
        const table = document.querySelector('.table');
        if (table) {
            const tbody = table.querySelector('tbody');
            if (tbody) {
                const headerCells = table.querySelectorAll('thead th');
                const colCount = headerCells.length || 1;
                
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.setAttribute('colspan', colCount);
                td.className = 'text-center py-3';
                td.textContent = "No hay resultados que concuerden con tu filtro";
                tr.appendChild(td);
                
                tbody.innerHTML = '';
                tbody.appendChild(tr);
            }
        }
    }

    // Actualiza el estado activo de los botones según la URL
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
});
</script>