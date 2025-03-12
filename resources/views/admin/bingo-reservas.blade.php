@extends('layouts.admin')

@section('content')
<div class="container-fluid p-0">
    <!-- Encabezado Panel verde -->
    <div style="background-color: #00bf63;" class="text-white p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-5 m-0">Participantes: {{ $bingo->nombre ?? 'Bingo' }}</h1>
                <p class="lead m-0">Fecha: {{ isset($bingo->fecha) ? \Carbon\Carbon::parse($bingo->fecha)->format('d/m/Y') : date('d/m/Y') }}</p>
            </div>
            <div class="text-center">
                <h3 class="mb-0">${{ isset($bingo->precio) ? number_format($bingo->precio, 0, ',', '.') : '0' }} Pesos</h3>
                <span class="badge {{ isset($bingo->estado) ? (strtolower($bingo->estado) == 'archivado' ? 'bg-warning text-dark' : (strtolower($bingo->estado) == 'abierto' ? 'bg-success' : 'bg-danger')) : 'bg-secondary' }} fs-6">
                    {{ isset($bingo->estado) ? ucfirst($bingo->estado) : 'Estado' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Resumen de Estadísticas -->
    <div class="container mb-4">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card bg-dark text-white border-success">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Participantes</h5>
                        <h2 class="mb-0 fw-bold">{{ $totalParticipantes ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white border-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title">Cartones Vendidos</h5>
                        <h2 class="mb-0 fw-bold">{{ $totalCartones ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white border-warning">
                    <div class="card-body text-center">
                        <h5 class="card-title">Reservas Aprobadas</h5>
                        <h2 class="mb-0 fw-bold">{{ $totalAprobadas ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white border-danger">
                    <div class="card-body text-center">
                        <h5 class="card-title">Reservas Pendientes</h5>
                        <h2 class="mb-0 fw-bold">{{ $totalPendientes ?? 0 }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de opciones (botones) -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-auto">
                <button id="btnTodasReservas" class="btn btn-sm btn-primary me-2 active">
                    Todas las Reservas
                </button>
                <button id="btnComprobanteDuplicado" class="btn btn-sm btn-secondary me-2">
                    Comprobante Duplicado
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

    <!-- Filtros -->
    <div class="container mb-4">
        <div class="card bg-dark text-white">
            <div class="card-header bg-secondary">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" id="nombre" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-4">
                        <label for="celular" class="form-label">Celular:</label>
                        <input type="text" id="celular" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-4">
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

    <!-- Botones de acción -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-12 d-flex justify-content-between">
                <a href="{{ route('bingos.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver al Panel
                </a>
            </div>
        </div>
    </div>

    <!-- Contenedor para la tabla (ahora será DataTable) -->
    <div class="container" id="tableContent">
        <!-- La tabla se cargará aquí dinámicamente -->
    </div>
</div>

<!-- DataTables CSS y JS desde CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variable para almacenar el tipo de vista actual
    let tipoActual = 'todas';
    let dataTable = null;
    
    // Función para cargar contenido de tabla vía AJAX
    function loadTableContent(url, actualizarFiltros = true) {
        // Mostrar indicador de carga
        document.getElementById('tableContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div><p class="mt-2 text-light">Cargando...</p></div>';
        
        // Destruir DataTable existente si existe
        if (dataTable !== null) {
            dataTable.destroy();
            dataTable = null;
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
                document.getElementById('tableContent').innerHTML = '<div class="alert alert-warning text-center">No hay resultados que concuerden con tu filtro.</div>';
                return;
            }
            
            // Verificar si el contenido contiene una tabla con datos
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Buscar si hay filas de datos (excluyendo encabezados y filas de "no hay resultados")
            const hasDataRows = Array.from(tempDiv.querySelectorAll('table tbody tr')).some(tr => {
                // Excluir filas que tienen mensaje de "no hay resultados"
                const text = tr.textContent.trim().toLowerCase();
                return !text.includes('no hay') && !text.includes('no se encontraron');
            });
            
            if (!hasDataRows) {
                // Si no hay filas con datos, mostrar un mensaje personalizado
                document.getElementById('tableContent').innerHTML = '<div class="alert alert-warning text-center">No hay resultados que concuerden con tu filtro.</div>';
                return;
            }
            
            // Si hay contenido válido, actualizar el contenedor
            document.getElementById('tableContent').innerHTML = html;
            
            // Inicializar DataTable después de cargar la tabla
            initializeDataTable();
            
            // Actualizamos el tipo actual basado en qué botón está activo
            if (actualizarFiltros) {
                const activeButton = document.querySelector('.col-auto button.active');
                if (activeButton) {
                    if (activeButton.id === 'btnComprobanteDuplicado') tipoActual = 'comprobantes-duplicados';
                    else if (activeButton.id === 'btnPedidoDuplicado') tipoActual = 'pedidos-duplicados';
                    else if (activeButton.id === 'btnCartonesEliminados') tipoActual = 'cartones-eliminados';
                    else tipoActual = 'todas';
                }
            }
        })
        .catch(error => {
            console.error('Error cargando contenido:', error);
            document.getElementById('tableContent').innerHTML = '<div class="alert alert-danger">Error al cargar los datos. Por favor, intenta nuevamente.</div>';
        });
    }
    
    // Función para inicializar DataTable en la tabla cargada
function initializeDataTable() {
    // Verificar si hay una tabla en el contenedor
    const table = document.querySelector('#tableContent table');
    if (!table) return;
    
    try {
        // Inicializar DataTable con opciones personalizadas
        dataTable = $(table).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                emptyTable: "No hay resultados que concuerden con tu filtro",
                zeroRecords: "No hay resultados que concuerden con tu filtro"
            },
            order: [[0, 'desc']], // Ordenar por la primera columna de forma descendente
            responsive: true,
            // Personalizar con clases oscuras para mantener el estilo
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
            initComplete: function() {
                // Añadir clases personalizadas para el tema oscuro
                $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').addClass('text-white');
                $('.dataTables_wrapper .form-control').addClass('bg-dark text-white border-secondary');
                $('.dataTables_wrapper .page-link').addClass('bg-dark text-white border-secondary');
                
                // Aplicar estilos según el tipo de vista
                if (tipoActual === 'comprobantes-duplicados') {
                    $('#tableContent table tbody tr').addClass('duplicado-comprobante');
                } else if (tipoActual === 'pedidos-duplicados') {
                    $('#tableContent table tbody tr').addClass('duplicado-pedido');
                } else if (tipoActual === 'cartones-eliminados') {
                    $('#tableContent table tbody tr').addClass('carton-eliminado');
                }
                
                // Configurar eventos después de inicializar DataTable
                setupEditSeriesEvents();
            }
        });
    } catch (error) {
        console.error('Error inicializando DataTable:', error);
        // Si hay un error al inicializar DataTable, mostrar un mensaje
        document.getElementById('tableContent').innerHTML = '<div class="alert alert-warning text-center">No hay resultados que concuerden con tu filtro.</div>';
    }
}

// Función para configurar los eventos de edición de series
function setupEditSeriesEvents() {
    // Manejar el evento de clic en el botón "Editar Series"
    $('.edit-series').off('click').on('click', function() {
        // Obtener datos del botón
        const reservaId = $(this).data('id');
        const nombre = $(this).data('nombre');
        let seriesData = $(this).data('series');
        const cantidad = parseInt($(this).data('cantidad'));
        const total = parseInt($(this).data('total'));
        const bingoId = $(this).data('bingo-id');
        const bingoPrice = parseInt($(this).data('bingo-precio'));
        
        console.log("Botón Editar Series clickeado:", {
            reservaId, nombre, seriesData, cantidad, total, bingoId, bingoPrice
        });
        
        // Verificar que el modal existe
        const modal = $('#editSeriesModal');
        if (modal.length === 0) {
            console.error('Error: No se encontró el modal #editSeriesModal');
            alert('Error: No se encontró el modal para editar series. Por favor, contacta al administrador.');
            return;
        }
        
        // Convertir seriesData a array si es una cadena
        let series = [];
        if (typeof seriesData === 'string') {
            try {
                // Intentar parsear como JSON
                series = JSON.parse(seriesData);
            } catch (e) {
                console.error('Error al parsear series:', e);
                // Si no es JSON válido, dividir por comas
                series = seriesData.split(',').map(item => item.trim());
            }
        } else if (Array.isArray(seriesData)) {
            series = seriesData;
        }
        
        // Completar datos del formulario en el modal
        $('#reserva_id').val(reservaId);
        $('#bingo_id').val(bingoId);
        $('#clientName').text(nombre);
        $('#newQuantity').val(cantidad);
        $('#newQuantity').attr('max', Array.isArray(series) ? series.length : 1);
        $('#currentTotal').text(new Intl.NumberFormat('es-CL').format(total));
        
        // Establecer URL del formulario
        $('#editSeriesForm').attr('action', `/admin/reservas/${reservaId}/update-series`);
        
        // Limpiar contenido previo
        $('#currentSeries').empty();
        $('#seriesCheckboxes').empty();
        
        // Mostrar series actuales y crear checkboxes
        if (Array.isArray(series) && series.length > 0) {
            const seriesList = $('<ul class="list-group"></ul>');
            
            series.forEach((serie, index) => {
                // Crear elemento de lista
                const listItem = $(`<li class="list-group-item bg-dark text-white border-light">Serie ${serie}</li>`);
                seriesList.append(listItem);
                
                // Crear checkbox
                const col = $('<div class="col-md-4 mb-2"></div>');
                const checkDiv = $('<div class="form-check"></div>');
                const checkbox = $(`<input type="checkbox" id="serie_${index}" name="selected_series[]" value="${serie}" class="form-check-input" checked>`);
                const label = $(`<label for="serie_${index}" class="form-check-label">Serie ${serie}</label>`);
                
                checkDiv.append(checkbox).append(label);
                col.append(checkDiv);
                $('#seriesCheckboxes').append(col);
            });
            
            $('#currentSeries').append(seriesList);
        } else {
            $('#currentSeries').text('No hay series disponibles');
        }
        
        // Configurar evento para cambio en cantidad
        $('#newQuantity').off('change').on('change', function() {
            const newQuantity = parseInt($(this).val());
            
            // Actualizar total estimado
            const newTotal = newQuantity * bingoPrice;
            $('#currentTotal').text(new Intl.NumberFormat('es-CL').format(newTotal));
            
            // Verificar selecciones
            updateSelectedCounter();
        });
        
        // Función para actualizar contador de seleccionados
        function updateSelectedCounter() {
            const checkboxes = $('input[name="selected_series[]"]');
            const newQuantity = parseInt($('#newQuantity').val());
            let checkedCount = 0;
            
            checkboxes.each(function() {
                if ($(this).prop('checked')) checkedCount++;
            });
            
            // Si hay más seleccionados que la cantidad permitida, desmarcar los últimos
            if (checkedCount > newQuantity) {
                let toUncheck = checkedCount - newQuantity;
                $(checkboxes.get().reverse()).each(function() {
                    if ($(this).prop('checked') && toUncheck > 0) {
                        $(this).prop('checked', false);
                        toUncheck--;
                    }
                });
            }
        }
        
        // Configurar eventos para checkboxes
        $('input[name="selected_series[]"]').off('change').on('change', function() {
            const newQuantity = parseInt($('#newQuantity').val());
            let checkedCount = 0;
            
            $('input[name="selected_series[]"]').each(function() {
                if ($(this).prop('checked')) checkedCount++;
            });
            
            // Si se excede la cantidad permitida, desmarcar este checkbox
            if (checkedCount > newQuantity && $(this).prop('checked')) {
                $(this).prop('checked', false);
                alert(`Solo puedes seleccionar ${newQuantity} series.`);
            }
        });
        
        // Inicializar contador de selecciones
        updateSelectedCounter();
        
        // Mostrar modal
        modal.modal('show');
        
        // Configurar botón de guardar
        $('#saveSeriesChanges').off('click').on('click', function() {
            const selectedCheckboxes = $('input[name="selected_series[]"]:checked');
            const newQuantity = parseInt($('#newQuantity').val());
            
            if (selectedCheckboxes.length !== newQuantity) {
                alert(`Debes seleccionar exactamente ${newQuantity} series.`);
                return;
            }
            
            // Enviar formulario
            $('#editSeriesForm').submit();
        });
    });
    
    // Manejar envío de formularios de aprobación/rechazo
    $('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]').on('submit', function(e) {
        // Encuentra la fila que contiene el formulario
        const row = $(this).closest('tr');
        // Busca el input editable del número de comprobante en la misma fila
        const input = row.find('.comprobante-input');
        if (input.length > 0) {
            // Crea un campo oculto para enviar el valor
            $('<input>').attr({
                type: 'hidden',
                name: 'numero_comprobante',
                value: input.val()
            }).appendTo($(this));
        }
    });
}

    
    
    // Función para actualizar botones activos
    function updateActiveButton(activeBtn) {
        document.querySelectorAll('.col-auto button').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.remove('active');
            btn.classList.add('btn-secondary');
        });
        
        activeBtn.classList.remove('btn-secondary');
        activeBtn.classList.add('btn-primary');
        activeBtn.classList.add('active');
    }
    
    // Función para añadir filtros a una URL
    function addFiltersToUrl(baseUrl) {
        const nombre = document.getElementById('nombre').value.trim();
        const celular = document.getElementById('celular').value.trim();
        const serie = document.getElementById('serie').value.trim();
        
        // Crear objeto URL para manipular parámetros
        const url = new URL(baseUrl, window.location.origin);
        
        // Añadir parámetros si existen
        if (nombre) url.searchParams.append('nombre', nombre);
        if (celular) url.searchParams.append('celular', celular);
        if (serie) url.searchParams.append('serie', serie);
        
        return url.toString();
    }
    
    // Obtener el ID del bingo actual de la URL o variable global
    let bingoId;
    try {
        // Intentar obtener el ID del bingo de una variable global (si existe)
        bingoId = typeof bingo_id !== 'undefined' ? bingo_id : window.location.pathname.split('/').filter(Boolean)[2];
    } catch (e) {
        // Si falla, extraer de la URL
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        bingoId = pathParts[pathParts.indexOf('bingos') + 1] || '0';
    }
    
    // Definir la ruta para todas las reservas usando tabla existente
    const rutaTablaTodasReservas = `/admin/bingos/${bingoId}/reservas-tabla?tipo=todas`;
    
    // Cargar inicialmente la tabla de todas las reservas
    loadTableContent(rutaTablaTodasReservas);
    
    // Asignar eventos a los botones
    document.getElementById('btnTodasReservas').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'todas';
        loadTableContent(rutaTablaTodasReservas);
    });

    document.getElementById('btnComprobanteDuplicado').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'comprobantes-duplicados';
        loadTableContent("{{ route('admin.comprobantesDuplicados') }}");
    });

    document.getElementById('btnPedidoDuplicado').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'pedidos-duplicados';
        loadTableContent("{{ route('admin.pedidosDuplicados') }}");
    });

    document.getElementById('btnCartonesEliminados').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'cartones-eliminados';
        loadTableContent("{{ route('admin.cartonesEliminados') }}");
    });
    
    // Evento para el botón de Filtrar
    document.getElementById('btnFiltrar').addEventListener('click', function() {
        let baseUrl;
        
        // Determinar qué ruta base usar según el tipo actual
        switch(tipoActual) {
            case 'comprobantes-duplicados':
                baseUrl = "{{ route('admin.comprobantesDuplicados') }}";
                break;
            case 'pedidos-duplicados':
                baseUrl = "{{ route('admin.pedidosDuplicados') }}";
                break;
            case 'cartones-eliminados':
                baseUrl = "{{ route('admin.cartonesEliminados') }}";
                break;
            default:
                baseUrl = rutaTablaTodasReservas;
        }
        
        // Añadir los filtros a la URL base
        const filteredUrl = addFiltersToUrl(baseUrl);
        
        // Cargar la tabla con la URL filtrada
        loadTableContent(filteredUrl, false);
    });
    
    // Evento para el botón de Limpiar filtros
    document.getElementById('btnLimpiar').addEventListener('click', function() {
        document.getElementById('nombre').value = '';
        document.getElementById('celular').value = '';
        document.getElementById('serie').value = '';
        
        // Determinar qué ruta base usar según el tipo actual
        let baseUrl;
        switch(tipoActual) {
            case 'comprobantes-duplicados':
                baseUrl = "{{ route('admin.comprobantesDuplicados') }}";
                break;
            case 'pedidos-duplicados':
                baseUrl = "{{ route('admin.pedidosDuplicados') }}";
                break;
            case 'cartones-eliminados':
                baseUrl = "{{ route('admin.cartonesEliminados') }}";
                break;
            default:
                baseUrl = rutaTablaTodasReservas;
        }
        
        loadTableContent(baseUrl, false);
    });
    
    // Permitir filtrar con Enter en los campos de texto
    document.getElementById('filterForm').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btnFiltrar').click();
        }
    });
});
</script>

<style>
/* Estilos para DataTables en tema oscuro */
.dataTables_wrapper .dataTables_length, 
.dataTables_wrapper .dataTables_filter, 
.dataTables_wrapper .dataTables_info, 
.dataTables_wrapper .dataTables_processing, 
.dataTables_wrapper .dataTables_paginate {
    color: #fff;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    color: #fff !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current, 
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: #00bf63 !important;
    color: white !important;
    border-color: #00bf63 !important;
}

.dataTables_wrapper .dataTables_length select, 
.dataTables_wrapper .dataTables_filter input {
    background-color: #343a40;
    color: #fff;
    border: 1px solid #6c757d;
}

/* Estilos para filas de duplicados */
.duplicado-comprobante {
    background-color: #fff3cd !important;
    color: #212529 !important;
}

.duplicado-pedido {
    background-color: #cff4fc !important;
    color: #212529 !important;
}

.carton-eliminado {
    background-color: #f8d7da !important;
    color: #212529 !important;
}
</style>

<style type="text/css" media="print">
    @media print {
        .btn, button, form, .actions, .card-header, #filterForm, 
        .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info {
            display: none !important;
        }
        
        body {
            background-color: white !important;
            color: black !important;
        }
        
        .table {
            border-collapse: collapse;
            width: 100%;
        }
        
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            color: black !important;
        }
        
        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .badge {
            border: 1px solid #ddd;
            padding: 3px 5px;
            border-radius: 4px;
        }
        
        .bg-success {
            background-color: #d4edda !important;
            color: #155724 !important;
        }
        
        .bg-warning {
            background-color: #fff3cd !important;
            color: #856404 !important;
        }
        
        .bg-danger {
            background-color: #f8d7da !important;
            color: #721c24 !important;
        }
    }
</style>
@endsection