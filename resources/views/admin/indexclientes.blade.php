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
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                    emptyTable: "No hay datos disponibles",
                    zeroRecords: "No hay resultados que concuerden con tu filtro"
                },
                order: [[0, 'desc']], // Ordenar por ID de forma descendente
                responsive: true,
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                // Manejar errores durante la inicialización
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
        // Configurar eventos para botones de editar series
        document.querySelectorAll('.edit-series').forEach(button => {
            button.addEventListener('click', handleEditSeries);
        });
        
        // Configurar eventos para formularios de aprobación/rechazo
        document.querySelectorAll('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]').forEach(form => {
            form.addEventListener('submit', handleFormSubmit);
        });
    }
    
    // Manejador para el evento de editar series
    function handleEditSeries() {
        const modal = document.getElementById('editSeriesModal');
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

        // Completar datos del formulario
        document.getElementById('reserva_id').value = reservaId;
        document.getElementById('bingo_id').value = bingoId;
        document.getElementById('clientName').textContent = this.getAttribute('data-nombre');
        document.getElementById('newQuantity').value = cantidad;
        document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);

        // Establecer URL del formulario
        const form = document.getElementById('editSeriesForm');
        form.action = `/admin/reservas/${reservaId}/update-series`;

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
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

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
        
        // Hacer la petición AJAX
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
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
@endsection