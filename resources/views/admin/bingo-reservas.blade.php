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
    // Variables para control
    let tipoActual = 'todas';
    let dataTable = null;
    
    // Función para cargar la tabla vía AJAX
    function loadTableContent(url) {
        console.log('Intentando cargar tabla desde URL:', url);
        
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
        .then(response => {
            console.log('Estado de respuesta:', response.status);
            return response.text();
        })
        .then(html => {
            console.log('Contenido recibido (primeros 100 caracteres):', html.substring(0, 100));
            
            // Si el HTML está vacío o contiene mensaje de no resultados
            if (html.trim() === '' || html.includes('No hay reservas') || html.includes('No se encontraron')) {
                document.getElementById('tableContent').innerHTML = '<div class="alert alert-warning text-center">No hay reservas que concuerden con tu filtro.</div>';
                return;
            }
            
            // Actualizar el contenedor con la tabla
            document.getElementById('tableContent').innerHTML = html;
            
            // Inicializar DataTable
            initializeDataTable();
        })
        .catch(error => {
            console.error('Error cargando tabla:', error);
            document.getElementById('tableContent').innerHTML = '<div class="alert alert-danger text-center">Error al cargar los datos: ' + error.message + '</div>';
        });
    }
    
    // Función para inicializar DataTable
    function initializeDataTable() {
        const table = document.querySelector('#tableContent table');
        if (!table) {
            console.error('No se encontró ninguna tabla en #tableContent');
            return;
        }
        
        try {
            dataTable = $(table).DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
                },
                responsive: true,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: true, targets: [0, 1, 2, 3, 7] },
                    { orderable: false, targets: '_all' },
                    { targets: 11, searchable: false }
                ],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                stateSave: true
            });
            
            console.log('DataTable inicializado correctamente');
            
            // Configurar eventos después de inicializar DataTable
            setupEventHandlers();
        } catch (error) {
            console.error('Error al inicializar DataTable:', error);
        }
    }
    
    // Configurar manejadores de eventos
    function setupEventHandlers() {
        // Eventos para edición de series
        $('.edit-series').off('click').on('click', function() {
            const modal = $('#editSeriesModal');
            const seriesData = $(this).data('series');
            let series = [];
            
            try {
                series = typeof seriesData === 'object' ? seriesData : JSON.parse(seriesData);
            } catch (e) {
                console.error('Error al parsear series:', e);
                if (typeof seriesData === 'string') {
                    series = seriesData.split(',').map(item => item.trim());
                }
            }
            
            const reservaId = $(this).data('id');
            const bingoId = $(this).data('bingo-id');
            const cantidad = parseInt($(this).data('cantidad'));
            const total = parseInt($(this).data('total'));
            const bingoPrice = parseInt($(this).data('bingo-precio'));
            
            // Completar datos del formulario
            $('#reserva_id').val(reservaId);
            $('#bingo_id').val(bingoId);
            $('#clientName').text($(this).data('nombre'));
            $('#newQuantity').val(cantidad);
            $('#newQuantity').attr('max', Array.isArray(series) ? series.length : 1);
            $('#currentTotal').text(new Intl.NumberFormat('es-CL').format(total));
            
            // Establecer URL del formulario
            const form = $('#editSeriesForm');
            const currentPath = window.location.pathname;
            const baseUrl = currentPath.includes('/admin') 
                ? currentPath.substring(0, currentPath.indexOf('/admin')) 
                : '';
            form.attr('action', `${baseUrl}/reservas/${reservaId}/update-series`);
            
            // Mostrar series actuales y crear checkboxes
            const currentSeriesDiv = $('#currentSeries');
            const seriesCheckboxesDiv = $('#seriesCheckboxes');
            
            // Limpiar contenido previo
            currentSeriesDiv.empty();
            seriesCheckboxesDiv.empty();
            
            // Mostrar y crear checkboxes para cada serie
            if (Array.isArray(series) && series.length > 0) {
                const seriesList = $('<ul class="list-group"></ul>');
                
                series.forEach((serie, index) => {
                    const listItem = $(`<li class="list-group-item bg-dark text-white border-light">Serie ${serie}</li>`);
                    seriesList.append(listItem);
                    
                    const col = $('<div class="col-md-4 mb-2"></div>');
                    const checkDiv = $('<div class="form-check"></div>');
                    const checkbox = $(`<input type="checkbox" id="serie_${index}" name="selected_series[]" value="${serie}" class="form-check-input" checked>`);
                    const label = $(`<label for="serie_${index}" class="form-check-label">Serie ${serie}</label>`);
                    
                    checkDiv.append(checkbox).append(label);
                    col.append(checkDiv);
                    seriesCheckboxesDiv.append(col);
                });
                
                currentSeriesDiv.append(seriesList);
            } else {
                currentSeriesDiv.text('No hay series disponibles');
            }
            
            // Función para actualizar contador de seleccionados
            function updateSelectedCounter() {
                const checkedCount = $('input[name="selected_series[]"]:checked').length;
                const newQuantity = parseInt($('#newQuantity').val());
                
                if (checkedCount > newQuantity) {
                    let toUncheck = checkedCount - newQuantity;
                    $($('input[name="selected_series[]"]:checked').get().reverse()).each(function() {
                        if (toUncheck > 0) {
                            $(this).prop('checked', false);
                            toUncheck--;
                        }
                    });
                }
            }
            
            // Manejar cambio en la cantidad de cartones
            $('#newQuantity').off('change').on('change', function() {
                const newQuantity = parseInt($(this).val());
                
                // Actualizar el total estimado
                const newTotal = newQuantity * bingoPrice;
                $('#currentTotal').text(new Intl.NumberFormat('es-CL').format(newTotal));
                
                // Actualizar contador
                updateSelectedCounter();
            });
            
            // Añadir listeners a los checkboxes
            $('input[name="selected_series[]"]').off('change').on('change', function() {
                const newQuantity = parseInt($('#newQuantity').val());
                const checkedCount = $('input[name="selected_series[]"]:checked').length;
                
                if (checkedCount > newQuantity && $(this).is(':checked')) {
                    $(this).prop('checked', false);
                    alert(`Solo puedes seleccionar ${newQuantity} series.`);
                }
            });
            
            // Inicializar contador
            updateSelectedCounter();
            
            // Mostrar modal
            modal.modal('show');
            
            // Manejar clic en el botón de guardar
            $('#saveSeriesChanges').off('click').on('click', function() {
                const selectedCheckboxes = $('input[name="selected_series[]"]:checked');
                const newQuantity = parseInt($('#newQuantity').val());
                
                if (selectedCheckboxes.length !== newQuantity) {
                    alert(`Debes seleccionar exactamente ${newQuantity} series.`);
                    return;
                }
                
                // Mostrar indicador de carga
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');
                
                // Enviar formulario
                $('#editSeriesForm').submit();
            });
        });
        
        // Manejar evento de formularios de aprobación/rechazo
        $('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]').off('submit').on('submit', function() {
            const row = $(this).closest('tr');
            const input = row.find('.comprobante-input');
            if (input.length) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'numero_comprobante',
                    value: input.val()
                }).appendTo(this);
            }
        });
        
        // Manejar actualización de número de comprobante
        $('.comprobante-input').off('blur').on('blur', function() {
            const reservaId = $(this).data('id');
            const numeroComprobante = $(this).val();
            console.log('Actualizar comprobante:', reservaId, numeroComprobante);
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
    // Construir la URL correcta basada en la ubicación actual
    console.log('Location completa:', window.location.href);
    
    // Obtener la parte base de la URL (todo antes de /admin)
    let basePath = '';
    const urlPath = window.location.pathname;
    
    // Verificar si incluye un path de usuario con tilde (~)
    if (urlPath.includes('~')) {
        // Extraer todo hasta la siguiente barra después de la tilde
        const tildeMatch = urlPath.match(/^(\/~[^/]+)/);
        if (tildeMatch) {
            basePath = tildeMatch[1];
            console.log('Base path con tilde:', basePath);
        }
    }
    
    // Extraer el ID del bingo
    const bingoMatch = urlPath.match(/\/bingos\/(\d+)/);
    let bingoId = '0';
    
    if (bingoMatch && bingoMatch[1]) {
        bingoId = bingoMatch[1];
        console.log('ID del bingo extraído:', bingoId);
    } else {
        console.warn('No se pudo extraer el ID del bingo');
    }
    
    // Construir la ruta correcta con el basePath
    const rutaTablaTodasReservas = `${basePath}/admin/bingos/${bingoId}/reservas-tabla?tipo=todas`;
    console.log('URL para todas las reservas:', rutaTablaTodasReservas);
    
    // Cargar inicialmente la tabla de todas las reservas
    loadTableContent(rutaTablaTodasReservas);
    
    // Para los otros botones, usar la función para construir URLs correctas
    function getCorrectPath(route) {
        return `${basePath}${route}`;
    }
    
    // Asignar eventos a los botones
    document.getElementById('btnTodasReservas').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'todas';
        loadTableContent(rutaTablaTodasReservas);
    });
    
    document.getElementById('btnComprobanteDuplicado').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'comprobantes-duplicados';
        loadTableContent(getCorrectPath("/admin/bingos/comprobantes-duplicados"));
    });
    
    document.getElementById('btnPedidoDuplicado').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'pedidos-duplicados';
        loadTableContent(getCorrectPath("/admin/bingos/pedidos-duplicados"));
    });
    
    document.getElementById('btnCartonesEliminados').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'cartones-eliminados';
        loadTableContent(getCorrectPath("/admin/bingos/cartones-eliminados"));
    });
    
    // Evento para el botón de Filtrar
    document.getElementById('btnFiltrar').addEventListener('click', function() {
        let baseUrl;
        
        // Determinar qué ruta base usar según el tipo actual
        switch(tipoActual) {
            case 'comprobantes-duplicados':
                baseUrl = getCorrectPath("/admin/bingos/comprobantes-duplicados");
                break;
            case 'pedidos-duplicados':
                baseUrl = getCorrectPath("/admin/bingos/pedidos-duplicados");
                break;
            case 'cartones-eliminados':
                baseUrl = getCorrectPath("/admin/bingos/cartones-eliminados");
                break;
            default:
                baseUrl = rutaTablaTodasReservas;
        }
        
        // Añadir los filtros a la URL base
        const filteredUrl = addFiltersToUrl(baseUrl);
        
        // Cargar la tabla con la URL filtrada
        loadTableContent(filteredUrl);
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
                baseUrl = getCorrectPath("/admin/bingos/comprobantes-duplicados");
                break;
            case 'pedidos-duplicados':
                baseUrl = getCorrectPath("/admin/bingos/pedidos-duplicados");
                break;
            case 'cartones-eliminados':
                baseUrl = getCorrectPath("/admin/bingos/cartones-eliminados");
                break;
            default:
                baseUrl = rutaTablaTodasReservas;
        }
        
        loadTableContent(baseUrl);
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