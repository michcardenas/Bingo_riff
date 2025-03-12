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

    // Mover la tabla existente al contenedor tableContent
    const existingTable = document.querySelector('#reservas-table');
    const tableContent = document.querySelector('#tableContent');
    if (existingTable && tableContent) {
        // Verificar si la tabla ya está en el contenedor adecuado
        if (!tableContent.contains(existingTable)) {
            // Limpiar el contenedor
            tableContent.innerHTML = '';
            // Mover la tabla
            tableContent.appendChild(existingTable);
        }
    }
    
    // Inicializar la tabla que ya está en el DOM
    function initializeExistingTable() {
        // Verificar si hay una tabla en la página
        const table = document.querySelector('#reservas-table');
        if (!table) {
            console.error('No se encontró la tabla #reservas-table en el DOM');
            return;
        }
        
        // Destruir DataTable existente si existe
        if (dataTable !== null) {
            dataTable.destroy();
            dataTable = null;
        }
        
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
                    
                    // Configurar eventos después de inicializar DataTable
                    setupEditSeriesEvents();
                }
            });
            
            // Mostrar mensaje si no hay datos
            if ($(table).find('tbody tr').length === 0 || 
                $(table).find('tbody tr:first').text().includes('No hay reservas')) {
                $('#tableContent').append('<div id="no-reservas" class="alert alert-warning text-center mt-3">No hay reservas registradas para este bingo.</div>');
            } else {
                $('#no-reservas').remove();
            }
        } catch (error) {
            console.error('Error inicializando DataTable:', error);
            tableContent.innerHTML = '<div class="alert alert-danger text-center">Error al inicializar la tabla: ' + error.message + '</div>';
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
            const form = $('#editSeriesForm');
            
            // Construir URL basada en el pathname actual
            const currentPath = window.location.pathname;
            const baseUrl = currentPath.includes('/admin') 
              ? currentPath.substring(0, currentPath.indexOf('/admin')) 
              : '';
            
            // Usar esta URL para asegurar compatibilidad en todos los entornos
            form.attr('action', `${baseUrl}/reservas/${reservaId}/update-series`);
            
            // Para depurar
            console.log('URL del formulario:', form.attr('action'));
            console.log('ID de Reserva:', reservaId);
            console.log('Series:', series);
            
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
                
                // Mostrar indicador de carga
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');
                
                // Enviar formulario
                $('#editSeriesForm').submit();
            });
            
            // Manejar envío del formulario
            $('#editSeriesForm').off('submit').on('submit', function() {
                // Aquí puedes agregar lógica adicional si es necesario
                // El formulario se enviará normalmente
            });
        });
        
        // Manejar envío de formularios de aprobación/rechazo
        $('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]').off('submit').on('submit', function() {
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
        
        // Manejar la actualización del número de comprobante mediante AJAX
        $('.comprobante-input').off('blur').on('blur', function() {
            const reservaId = $(this).data('id');
            const numeroComprobante = $(this).val();
            // Aquí puedes implementar el guardado vía AJAX si lo necesitas
            console.log('Actualizar comprobante:', reservaId, numeroComprobante);
        });
    }
    
    // Inicializar la tabla existente en carga
    initializeExistingTable();
    
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
    
    // Funciones de filtrado para la tabla existente
    document.getElementById('btnFiltrar').addEventListener('click', function() {
        const nombre = document.getElementById('nombre').value.trim().toLowerCase();
        const celular = document.getElementById('celular').value.trim();
        const serie = document.getElementById('serie').value.trim();
        
        if (dataTable) {
            // Limpiar mensajes anteriores
            $('#no-resultados, #no-duplicados').remove();
            
            // Resetear la tabla para mostrar todas las filas
            dataTable.search('').columns().search('').draw();
            
            // Mostrar todas las filas antes de aplicar filtros
            dataTable.rows().nodes().to$().show();
            
            // Para filtro complejo, usamos $.fn.dataTable.ext.search
            $.fn.dataTable.ext.search.pop(); // Eliminar filtros anteriores
            
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    const row = dataTable.row(dataIndex).node();
                    let seriesStr = data[5]; // Columna de series
                    
                    // Verificar si cumple con los filtros
                    const nombreMatch = !nombre || data[1].toLowerCase().includes(nombre);
                    const celularMatch = !celular || data[2].includes(celular);
                    
                    // Para series, intentamos buscar en el texto visible
                    let serieMatch = true;
                    if (serie) {
                        serieMatch = seriesStr.includes(serie);
                    }
                    
                    return nombreMatch && celularMatch && serieMatch;
                }
            );
            
            // Redraw con los filtros aplicados
            dataTable.draw();
            
            // Verificar si hay resultados
            const visibleRows = dataTable.rows({search:'applied'}).nodes().length;
            if (visibleRows === 0) {
                $('#tableContent').append('<div id="no-resultados" class="alert alert-warning text-center mt-3">No hay resultados que coincidan con los filtros aplicados.</div>');
            }
        }
    });
    
    // Limpiar filtros
    document.getElementById('btnLimpiar').addEventListener('click', function() {
        document.getElementById('nombre').value = '';
        document.getElementById('celular').value = '';
        document.getElementById('serie').value = '';
        
        // Limpiar mensajes
        $('#no-resultados, #no-duplicados').remove();
        
        if (dataTable) {
            // Eliminar filtros personalizados
            $.fn.dataTable.ext.search.pop();
            
            // Mostrar todas las filas
            dataTable.rows().nodes().to$().show();
            
            // Limpiar búsquedas
            dataTable.search('').columns().search('').draw();
            
            // Eliminar clases de duplicados
            $(dataTable.rows().nodes()).removeClass('duplicado-comprobante duplicado-pedido carton-eliminado');
        }
    });
    
    // Permitir filtrar con Enter en los campos de texto
    document.getElementById('filterForm').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btnFiltrar').click();
        }
    });
    
    // Para manejar diferentes vistas
    document.getElementById('btnTodasReservas').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'todas';
        
        // Limpiar mensajes
        $('#no-resultados, #no-duplicados').remove();
        
        // Mostrar todas las filas
        if (dataTable) {
            // Eliminar filtros personalizados
            $.fn.dataTable.ext.search.pop();
            
            // Eliminar cualquier clase especial
            $(dataTable.rows().nodes()).removeClass('duplicado-comprobante duplicado-pedido carton-eliminado');
            
            // Mostrar todas las filas
            dataTable.rows().nodes().to$().show();
            
            // Limpiar búsquedas
            dataTable.search('').columns().search('').draw();
        }
    });
    
    // Función para detectar comprobantes duplicados
    document.getElementById('btnComprobanteDuplicado').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'comprobantes-duplicados';
        
        // Limpiar mensajes anteriores
        $('#no-resultados, #no-duplicados').remove();
        
        if (dataTable) {
            // Obtener todos los números de comprobante
            const comprobantes = {};
            dataTable.rows().every(function() {
                const row = this.node();
                const numeroComprobante = $(row).find('input[type="text"]').val();
                
                if (numeroComprobante && numeroComprobante.trim() !== '') {
                    if (!comprobantes[numeroComprobante]) {
                        comprobantes[numeroComprobante] = [];
                    }
                    comprobantes[numeroComprobante].push(row);
                }
            });
            
            // Limpiar primero todas las clases
            $(dataTable.rows().nodes()).removeClass('duplicado-comprobante duplicado-pedido carton-eliminado');
            
            // Ocultar todas las filas primero
            dataTable.rows().nodes().to$().hide();
            
            // Mostrar solo las filas con comprobantes duplicados
            let duplicadosEncontrados = false;
            for (const [comprobante, filas] of Object.entries(comprobantes)) {
                if (filas.length > 1) {
                    // Este comprobante está duplicado
                    filas.forEach(row => {
                        $(row).addClass('duplicado-comprobante').show();
                    });
                    duplicadosEncontrados = true;
                }
            }
            
            // Mensaje si no hay duplicados
            if (!duplicadosEncontrados) {
                $('#tableContent').append('<div id="no-duplicados" class="alert alert-success text-center mt-3">No se encontraron comprobantes duplicados.</div>');
            }
            
            // Actualizar DataTable
            dataTable.draw();
        }
    });
    
    // Función para detectar pedidos duplicados
    document.getElementById('btnPedidoDuplicado').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'pedidos-duplicados';
        
        // Limpiar mensajes anteriores
        $('#no-resultados, #no-duplicados').remove();
        
        if (dataTable) {
            // Obtener todos los números de celular
            const celulares = {};
            dataTable.rows().every(function() {
                const row = this.node();
                const celular = $(row).find('td:eq(2)').text().trim();
                const nombre = $(row).find('td:eq(1)').text().trim();
                
                if (celular && celular !== '') {
                    const key = nombre + '-' + celular; // Combinar nombre y celular para ser más específico
                    if (!celulares[key]) {
                        celulares[key] = [];
                    }
                    celulares[key].push(row);
                }
            });
            
            // Limpiar primero todas las clases
            $(dataTable.rows().nodes()).removeClass('duplicado-comprobante duplicado-pedido carton-eliminado');
            
            // Ocultar todas las filas primero
            dataTable.rows().nodes().to$().hide();
            
            // Mostrar solo las filas con pedidos duplicados
            let duplicadosEncontrados = false;
            for (const [key, filas] of Object.entries(celulares)) {
                if (filas.length > 1) {
                    // Este pedido está duplicado
                    filas.forEach(row => {
                        $(row).addClass('duplicado-pedido').show();
                    });
                    duplicadosEncontrados = true;
                }
            }
            
            // Mensaje si no hay duplicados
            if (!duplicadosEncontrados) {
                $('#tableContent').append('<div id="no-duplicados" class="alert alert-info text-center mt-3">No se encontraron pedidos duplicados.</div>');
            }
            
            // Actualizar DataTable
            dataTable.draw();
        }
    });
    
    // Función para cartones eliminados
    document.getElementById('btnCartonesEliminados').addEventListener('click', function() {
        updateActiveButton(this);
        tipoActual = 'cartones-eliminados';
        
        // Limpiar mensajes anteriores
        $('#no-resultados, #no-duplicados').remove();
        
        // Podemos mostrar un mensaje de que esta funcionalidad requiere una implementación específica
        $('#tableContent').append('<div id="no-duplicados" class="alert alert-secondary text-center mt-3">La funcionalidad de cartones eliminados requiere configuración adicional. Por favor, contacta al administrador.</div>');
        
        // Aquí deberías implementar la lógica específica para detectar cartones eliminados
        // Como esta funcionalidad depende de cómo se manejan los cartones eliminados en tu sistema,
        // proporcionamos un mensaje informativo en su lugar.
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