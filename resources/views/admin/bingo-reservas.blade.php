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

                <a href="{{ route('bingos.buscador.serie', $bingo->id) }}" class="btn btn-primary">
    <i class="bi bi-search"></i> Buscar por Número de Serie
</a>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addParticipantModal">
                    <i class="bi bi-plus-circle"></i> Añadir Participante
                </button>
            </div>
        </div>
    </div>


<!-- Modal Añadir Participante -->
<div class="modal fade" id="addParticipantModal" tabindex="-1" aria-labelledby="addParticipantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addParticipantModalLabel">Añadir Nuevo Participante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addParticipantForm" method="POST" action="{{ route('bingo.store') }}" enctype="multipart/form-data">
                    @csrf
                    <!-- Bingo ID oculto -->
                    <input type="hidden" name="bingo_id" value="{{ $bingo->id }}">
                    <!-- Campo para indicar que es desde panel admin -->
                    <input type="hidden" name="desde_admin" value="1">
                    <!-- Campo para la redirección -->
                    <input type="hidden" name="redirect_to" value="{{ route('bingos.index', $bingo->id) }}">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control bg-dark text-white border-light" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="celular" class="form-label">Número de Celular</label>
                            <input type="text" class="form-control bg-dark text-white border-light" id="celular" name="celular" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cartones" class="form-label">Cantidad de Cartones</label>
                            <input type="number" class="form-control bg-dark text-white border-light" id="cartones" name="cartones" min="1" value="1" required>
                            <small class="form-text text-muted">Precio por cartón: ${{ number_format($bingo->precio, 0, ',', '.') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label for="totalPagar" class="form-label">Total a Pagar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-white border-light">$</span>
                                <input type="text" class="form-control bg-dark text-white border-light" id="totalPagar" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comprobante" class="form-label">Comprobante de Pago</label>
                        <input type="file" class="form-control bg-dark text-white border-light" id="comprobante" name="comprobante[]" accept="image/*" multiple required>
                        <small class="form-text text-muted">Puedes subir múltiples imágenes (máximo 5MB cada una)</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="autoApprove" name="auto_approve">
                            <label class="form-check-label" for="autoApprove">
                                Aprobar automáticamente
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-dark">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="addParticipantForm" class="btn btn-success">Guardar Participante</button>
            </div>
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
    // Añadir esto al inicio del script existente, dentro del DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        // Variables para control
        let tipoActual = 'todas';
        let dataTable = null;

        // Agregar botón para ocultar/mostrar estadísticas
        const statsContainer = document.querySelector('.container.mb-4:has(.card.bg-dark)');

        if (statsContainer) {
            // Añadir ID al contenedor de estadísticas
            statsContainer.id = 'estadisticasContainer';

            // Crear el botón
            const toggleButton = document.createElement('button');
            toggleButton.className = 'btn btn-secondary mb-2';
            toggleButton.innerHTML = '<i class="fas fa-eye-slash me-2"></i>Ocultar Estadísticas';

            // Insertar el botón antes del contenedor de estadísticas
            statsContainer.parentNode.insertBefore(toggleButton, statsContainer);

            // Verificar localStorage
            if (localStorage.getItem('estadisticasOcultas') === 'true') {
                statsContainer.style.display = 'none';
                toggleButton.innerHTML = '<i class="fas fa-eye me-2"></i>Mostrar Estadísticas';
            }

            // Evento del botón
            toggleButton.addEventListener('click', function() {
                if (statsContainer.style.display === 'none') {
                    statsContainer.style.display = '';
                    toggleButton.innerHTML = '<i class="fas fa-eye-slash me-2"></i>Ocultar Estadísticas';
                    localStorage.setItem('estadisticasOcultas', 'false');
                } else {
                    statsContainer.style.display = 'none';
                    toggleButton.innerHTML = '<i class="fas fa-eye me-2"></i>Mostrar Estadísticas';
                    localStorage.setItem('estadisticasOcultas', 'true');
                }
            });
        }

// Modificar la función loadTableContent para usar un tiempo de espera extremadamente largo
function loadTableContent(url, filtrarDespues = false, tipoFiltro = '', attemptCount = 0) {
    // Configuración de reintentos
    const MAX_ATTEMPTS = 3; // Número máximo de intentos
    const RETRY_DELAY = 3000; // Tiempo entre reintentos (3 segundos)
    const TIMEOUT = 1200000; // Tiempo de espera extremadamente largo (15 minutos)
    
    // Cancelar cualquier solicitud de carga previa
    if (window.currentTableLoadRequest && typeof window.currentTableLoadRequest.abort === 'function') {
        window.currentTableLoadRequest.abort();
    }

    console.log(`Intentando cargar tabla desde URL: ${url} (Intento ${attemptCount + 1} de ${MAX_ATTEMPTS})`);

    // Crear un nuevo AbortController
    const controller = new AbortController();
    window.currentTableLoadRequest = controller;

    // Configurar un timeout extremadamente largo
    const timeoutId = setTimeout(() => {
        controller.abort();
        console.warn(`La solicitud ha excedido el tiempo de espera (${TIMEOUT/1000}s)`);
    }, TIMEOUT);

    // Mostrar indicador de carga con información sobre el intento
    let loadingMessage = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div>';
    if (attemptCount > 0) {
        loadingMessage += `<p class="mt-2 text-light">Cargando... Intento ${attemptCount + 1} de ${MAX_ATTEMPTS}</p>`;
        loadingMessage += `<p class="small text-muted">Esta operación puede tardar hasta ${(TIMEOUT/60000).toFixed(1)} minutos con grandes conjuntos de datos</p>`;
    } else {
        loadingMessage += '<p class="mt-2 text-light">Cargando datos...</p>';
        loadingMessage += `<p class="small text-muted">Por favor espere, esto puede tardar varios minutos con grandes conjuntos de datos</p>`;
    }
    loadingMessage += '</div>';
    
    document.getElementById('tableContent').innerHTML = loadingMessage;

    // Destruir DataTable existente si existe
    if (dataTable !== null) {
        dataTable.destroy();
        dataTable = null;
    }

    // Hacer la petición AJAX con timeout extendido
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        console.log('Estado de respuesta:', response.status);
        
        if (!response.ok) {
            // Capturar específicamente el error 500
            if (response.status === 500) {
                throw new Error('SERVER_TIMEOUT');
            }
            throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.text();
    })
    .then(html => {
        // Verificar si la solicitud ha sido abortada
        if (controller.signal.aborted) {
            console.log('Carga de tabla cancelada');
            return;
        }

        console.log('Contenido recibido (primeros 100 caracteres):', html.substring(0, 100));
        
        // Si el HTML está vacío o contiene mensaje de no resultados
        if (html.trim() === '' || html.includes('No hay reservas') || html.includes('No se encontraron')) {
            document.getElementById('tableContent').innerHTML = '<div class="alert alert-warning text-center">No hay reservas que concuerden con tu filtro.</div>';
            return;
        }
        
        // Actualizar el contenedor con la tabla
        document.getElementById('tableContent').innerHTML = html;
        
        // Inicializar DataTable con opciones optimizadas para grandes conjuntos de datos
        initializeDataTable();
        
        // Si hay que filtrar después de cargar, aplicar el filtro
        if (filtrarDespues && dataTable) {
            setTimeout(() => {
                filtrarPorTipo(tipoFiltro);
            }, 100);
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        
        // Ignorar errores de aborto específicos de la interfaz
        if (error.name === 'AbortError') {
            console.log('Carga de tabla cancelada por el usuario');
            return;
        }
        
        console.error('Error cargando tabla:', error);
        
        // Comprobar si debemos reintentar (solo para errores de timeout o 500)
        const isServerTimeout = error.message === 'SERVER_TIMEOUT' || 
                               error.message.includes('timeout') || 
                               error.message.includes('Error HTTP: 500');
                               
        if (isServerTimeout && attemptCount < MAX_ATTEMPTS - 1) {
            console.log(`Reintentando en ${RETRY_DELAY/1000} segundos...`);
            
            document.getElementById('tableContent').innerHTML = 
                `<div class="alert alert-warning text-center">
                    El servidor está tardando en responder. Reintentando en ${RETRY_DELAY/1000} segundos...
                    <div class="progress mt-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 100%" 
                             aria-valuenow="100" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                </div>`;
                
            setTimeout(() => {
                loadTableContent(url, filtrarDespues, tipoFiltro, attemptCount + 1);
            }, RETRY_DELAY);
            
            return;
        }
        
        // Si ya hemos agotado los reintentos o es otro tipo de error, mostrar mensaje
        let errorMessage = `<div class="alert alert-danger text-center">
            Error al cargar los datos: ${error.message}
            <div class="mt-2">
                <button class="btn btn-sm btn-primary me-2" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Recargar página
                </button>`;
                
        // Solo mostrar botón de reintentar si estamos en el último intento
        if (attemptCount >= MAX_ATTEMPTS - 1) {
            errorMessage += `
                <button class="btn btn-sm btn-outline-primary" onclick="loadTableContent('${url}', ${filtrarDespues}, '${tipoFiltro}', 0)">
                    <i class="fas fa-redo me-1"></i>Intentar de nuevo
                </button>`;
        }
        
        errorMessage += `</div></div>`;
        
        document.getElementById('tableContent').innerHTML = errorMessage;
    })
    .finally(() => {
        // Limpiar la referencia al request actual
        if (window.currentTableLoadRequest === controller) {
            window.currentTableLoadRequest = null;
        }
    });
}

// Función para inicializar DataTable con configuración optimizada
function initializeDataTable() {
    const table = document.querySelector('#tableContent table');
    if (!table) {
        console.error('No se encontró ninguna tabla en #tableContent');
        return;
    }

    try {
        // Añadir clase para identificar la tabla
        table.classList.add('reservas-table');
        
        // Opciones optimizadas para DataTables con muchos datos
        dataTable = $(table).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json',
                processing: '<div class="spinner-border text-light" role="status"></div><span>Procesando...</span>'
            },
            processing: true,         // Mostrar indicador de procesamiento
            deferRender: true,        // Renderizado diferido para mejor rendimiento
            scroller: true,           // Usar scroll virtual para mejor rendimiento
            scrollY: '60vh',          // Altura máxima de la tabla con scroll
            scrollCollapse: true,     // Colapsar altura cuando hay pocos datos
            responsive: true,
            order: [[0, 'desc']],
            columnDefs: [
                {
                    orderable: true,
                    targets: [0, 1, 2, 3, 7]
                },
                {
                    orderable: false,
                    targets: '_all'
                },
                {
                    targets: 11,
                    searchable: false
                },
                // Ocultar columnas menos relevantes en móviles
                {
                    targets: [4, 5, 6, 8],
                    responsivePriority: 2
                }
            ],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Todos"]
            ],
            stateSave: true,
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            // Opciones para cargas grandes
            searchDelay: 500,        // Retraso en búsqueda para mejor rendimiento
            serverSide: false        // Cambiar a true si implementas server-side processing
        });

        console.log('DataTable inicializado correctamente');

        // Configurar eventos después de inicializar DataTable
        setupEventHandlers();
        
        // Añadir un listener para el evento search de DataTable para quitar mensajes de filtro
        dataTable.on('search.dt', function() {
            $('#mensaje-filtro').fadeOut(300, function() {
                $(this).remove();
            });
        });
    } catch (error) {
        console.error('Error al inicializar DataTable:', error);
    }
}

// Mejora para optimizar el filtrado de grandes conjuntos
function filtrarPorTipo(tipo) {
    if (!dataTable) return;

    // Limpiar mensajes previos
    $('#mensaje-filtro').remove();
    
    // Mostrar indicador de procesamiento
    $('#tableContent').prepend(
        '<div id="mensaje-filtro" class="alert alert-info text-center">' +
        '<div class="spinner-border spinner-border-sm me-2" role="status"></div>' +
        'Aplicando filtro... Por favor espere.' +
        '</div>'
    );

    // Retrasar la ejecución para permitir la actualización de la UI
    setTimeout(() => {
        // Resetear DataTable para mostrar todas las filas
        dataTable.search('').columns().search('').draw();

        if (tipo === 'todas') {
            $('#mensaje-filtro').fadeOut(300, function() {
                $(this).remove();
            });
            return; // No hacer nada, mostrar todas
        }

        try {
            if (tipo === 'pedidos-duplicados') {
                // Mostrar mensaje de procesamiento
                $('#mensaje-filtro').html(
                    '<div class="spinner-border spinner-border-sm me-2" role="status"></div>' +
                    'Buscando pedidos duplicados... Esto puede tardar unos momentos.'
                );
                
                // Buscar pedidos duplicados (mismo número de teléfono) con rendimiento optimizado
                const telefonos = {};
                let filasDuplicadas = [];
                let totalRows = 0;

                // Procesar en lotes para evitar bloquear la UI
                function processBatch(startIdx, batchSize) {
                    const endIdx = Math.min(startIdx + batchSize, dataTable.rows()[0].length);
                    
                    for (let i = startIdx; i < endIdx; i++) {
                        const rowIdx = dataTable.rows()[0][i];
                        const fila = dataTable.row(rowIdx).node();
                        const celular = $(fila).find('td:eq(2)').text().trim();
                        totalRows++;

                        if (celular) {
                            if (!telefonos[celular]) {
                                telefonos[celular] = [];
                            }
                            telefonos[celular].push(rowIdx);
                        }
                    }

                    // Actualizar mensaje de progreso
                    const progressPercent = Math.round((endIdx / dataTable.rows()[0].length) * 100);
                    $('#mensaje-filtro').html(
                        `<div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Procesando datos... ${progressPercent}% completado
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar" style="width: ${progressPercent}%" 
                                 aria-valuenow="${progressPercent}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>`
                    );

                    // Si hay más filas por procesar, continuar con el siguiente lote
                    if (endIdx < dataTable.rows()[0].length) {
                        setTimeout(() => {
                            processBatch(endIdx, batchSize);
                        }, 0);
                    } else {
                        // Finalizado el procesamiento, identificar duplicados
                        for (const telefono in telefonos) {
                            if (telefonos[telefono].length > 1) {
                                filasDuplicadas = filasDuplicadas.concat(telefonos[telefono]);
                            }
                        }

                        finalizarFiltroDuplicados(filasDuplicadas);
                    }
                }

                // Iniciar procesamiento por lotes (procesar 100 filas a la vez)
                processBatch(0, 100);
                
                function finalizarFiltroDuplicados(filasDuplicadas) {
                    if (filasDuplicadas.length > 0) {
                        // Mostrar solo las filas con teléfonos duplicados
                        dataTable.rows().every(function(rowIdx) {
                            if (!filasDuplicadas.includes(rowIdx)) {
                                $(this.node()).addClass('d-none');
                            } else {
                                $(this.node()).removeClass('d-none').addClass('duplicado-pedido');
                            }
                        });
                        
                        // Reordenar DataTable para agrupar por número de teléfono
                        dataTable.order([2, 'asc']).draw(); // La columna 2 es la del celular
                        
                        // Mostrar todos los resultados en una sola página
                        dataTable.page.len(-1).draw('page');
                        
                        // Actualizar mensaje
                        $('#mensaje-filtro').removeClass('alert-info').addClass('alert-success')
                            .html(`<i class="fas fa-check-circle me-2"></i>Se encontraron ${filasDuplicadas.length} registros con números de teléfono duplicados.`);
                    } else {
                        // No hay duplicados
                        dataTable.rows().nodes().to$().addClass('d-none');
                        dataTable.page.len(-1).draw('page');
                        $('#mensaje-filtro').removeClass('alert-info').addClass('alert-warning')
                            .html('<i class="fas fa-exclamation-triangle me-2"></i>No se encontraron números de teléfono duplicados.');
                    }
                }
            } else if (tipo === 'cartones-eliminados') {
                // Código similar para cartones eliminados, con procesamiento optimizado
                let filasRechazadas = [];
                
                dataTable.rows().every(function(rowIdx) {
                    const fila = this.node();
                    const estadoCell = $(fila).find('td:eq(10)');
                    const estadoTexto = estadoCell.text().trim().toLowerCase();

                    // Verificar si el texto contiene "rechazado" o si la celda posee una etiqueta con clase bg-danger
                    if (estadoTexto.includes('rechazado') || estadoCell.find('.badge.bg-danger').length > 0) {
                        filasRechazadas.push(rowIdx);
                    }
                });

                if (filasRechazadas.length > 0) {
                    // Mostrar solo las filas con estado rechazado
                    dataTable.rows().every(function(rowIdx) {
                        if (!filasRechazadas.includes(rowIdx)) {
                            $(this.node()).addClass('d-none');
                        } else {
                            $(this.node()).removeClass('d-none').addClass('carton-eliminado');
                        }
                    });
                    // Mostrar todos los resultados en una sola página
                    dataTable.page.len(-1).draw('page');
                    
                    // Actualizar mensaje
                    $('#mensaje-filtro').removeClass('alert-info').addClass('alert-success')
                        .html(`<i class="fas fa-check-circle me-2"></i>Se encontraron ${filasRechazadas.length} reservas con estado rechazado.`);
                } else {
                    // No hay reservas rechazadas
                    dataTable.rows().nodes().to$().addClass('d-none');
                    dataTable.page.len(-1).draw('page');
                    $('#mensaje-filtro').removeClass('alert-info').addClass('alert-danger')
                        .html('<i class="fas fa-times-circle me-2"></i>No se encontraron reservas con estado rechazado.');
                }
            }
        } catch (error) {
            console.error('Error al aplicar filtro:', error);
            $('#mensaje-filtro').removeClass('alert-info').addClass('alert-danger')
                .html(`<i class="fas fa-exclamation-circle me-2"></i>Error al aplicar el filtro: ${error.message}`);
        }
    }, 50);
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
                form.attr('action', `${basePath}/admin/reservas/${reservaId}/update-series`);

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
                // Aquí puedes implementar el guardado vía AJAX si lo necesitas
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

        // Modificar los event listeners para los botones de filtro
document.getElementById('btnTodasReservas').addEventListener('click', function() {
    updateActiveButton(this);
    tipoActual = 'todas';
    
    // Siempre cargar la tabla completa para "Todas las reservas"
    loadTableContent(rutaTablaTodasReservas);
});

document.getElementById('btnComprobanteDuplicado').addEventListener('click', function() {
    updateActiveButton(this);
    tipoActual = 'comprobantes-duplicados';
    
    // Solo aplicar el filtro, sin recargar la tabla
    filtrarPorTipo('comprobantes-duplicados');
});

document.getElementById('btnPedidoDuplicado').addEventListener('click', async function() {
    updateActiveButton(this);
    tipoActual = 'pedidos-duplicados';
    
    // Siempre cargar la tabla completa primero, luego filtrar
    loadTableContent(rutaTablaTodasReservas, true, 'pedidos-duplicados');

});

document.getElementById('btnCartonesEliminados').addEventListener('click', async function() {
    updateActiveButton(this);
    tipoActual = 'cartones-eliminados';
    
    // Siempre cargar la tabla completa primero, luego filtrar
    loadTableContent(rutaTablaTodasReservas, true, 'cartones-eliminados');

});

      // Evento para el botón de Filtrar
document.getElementById('btnFiltrar').addEventListener('click', function() {
    const nombre = document.getElementById('nombre').value.trim();
    const celular = document.getElementById('celular').value.trim();
    const serie = document.getElementById('serie').value.trim();

    // Si no hay filtros, mostrar todos los datos
    if (!nombre && !celular && !serie) {
        if (dataTable) {
            dataTable.search('').columns().search('').draw();
        }
        return;
    }

    // Si ya tenemos un DataTable inicializado, aplicamos filtros directamente
    if (dataTable) {
        // Importante: primero volver a la primera página
        dataTable.page('first').draw('page');
        
        // Limpiar filtros anteriores de DataTable
        dataTable.search('').columns().search('').draw(false);
        
        // Establecer búsqueda global que combine todos los criterios
        let terminoBusqueda = '';
        if (nombre) terminoBusqueda += nombre + ' ';
        if (celular) terminoBusqueda += celular + ' ';
        if (serie) terminoBusqueda += serie + ' ';
        
        // Aplicar la búsqueda y mostrar resultados
        dataTable.search(terminoBusqueda.trim()).draw();
        
        // Verificar si hay resultados visibles
        const resultadosVisibles = dataTable.page.info().recordsDisplay;
        
        // Mostrar mensaje si no hay coincidencias
        $('#mensaje-filtro').remove();
        if (resultadosVisibles === 0) {
            $('#tableContent').prepend('<div id="mensaje-filtro" class="alert alert-warning">No se encontraron reservas que coincidan con los filtros aplicados.</div>');
        } else {
            $('#tableContent').prepend(`<div id="mensaje-filtro" class="alert alert-success">Se encontraron ${resultadosVisibles} reservas que coinciden con los filtros.</div>`);
        }
    } else {
        // Si no hay DataTable, hacer petición con filtros
        const filteredUrl = addFiltersToUrl(rutaTablaTodasReservas);
        loadTableContent(filteredUrl);
    }
});

        // Evento para el botón de Limpiar filtros
        document.getElementById('btnLimpiar').addEventListener('click', function() {
            document.getElementById('nombre').value = '';
            document.getElementById('celular').value = '';
            document.getElementById('serie').value = '';

            // Cargar todas las reservas y después aplicar el filtro por tipo
            loadTableContent(rutaTablaTodasReservas, true, tipoActual);
        });

        // Permitir filtrar con Enter en los campos de texto
        document.getElementById('filterForm').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('btnFiltrar').click();
            }
        });

        // Agregar estilos CSS para ocultar filas y manejar el botón de estadísticas
        const style = document.createElement('style');
        style.textContent = `
        .d-none {
            display: none !important;
        }
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
    `;
        document.head.appendChild(style);
    });
</script>

<script>
      document.addEventListener('DOMContentLoaded', function() {
        // Calcular el total a pagar cuando cambia la cantidad de cartones
        const cartonesInput = document.getElementById('cartones');
        const totalPagarInput = document.getElementById('totalPagar');
        const precioCarton = {{ $bingo->precio ?? 0 }};
        
        console.log('Precio del cartón:', precioCarton);
        
        function actualizarTotal() {
            const cantidad = parseInt(cartonesInput.value) || 0;
            const total = cantidad * precioCarton;
            console.log('[DEBUG] Total actualizado:', cantidad, 'cartones x', precioCarton, '=', total);
            totalPagarInput.value = new Intl.NumberFormat('es-CL').format(total);
        }
        
        // Asegurarse de que el elemento existe antes de añadir el evento
        if (cartonesInput && totalPagarInput) {
            cartonesInput.addEventListener('input', actualizarTotal);
            // Inicializar el total al cargar
            actualizarTotal();
        } else {
            console.error('No se encontraron los elementos necesarios para el cálculo del precio');
        }
        
        // Inicializar el total
        actualizarTotal();
        
        // Envío del formulario vía AJAX
        const form = document.getElementById('addParticipantForm');
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            // Mostrar indicador de carga en el botón de envío
            const submitBtn = document.querySelector('button[type="submit"][form="addParticipantForm"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            
            // Crear FormData para enviar archivos
            const formData = new FormData(form);
            
            // Obtener el token CSRF de forma segura
            let csrfToken = '';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                csrfToken = csrfMeta.getAttribute('content');
            } else {
                // Intentar obtener del formulario (Laravel automáticamente añade un campo _token)
                const tokenInput = document.querySelector('input[name="_token"]');
                if (tokenInput) {
                    csrfToken = tokenInput.value;
                }
            }
            
            // Construir headers
            const headers = {
                'X-Requested-With': 'XMLHttpRequest'
            };
            
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }
            
            // Realizar la petición AJAX
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: headers
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Error al procesar la solicitud');
                    });
                }
                return response.json();
            })
            .then(data => {
                // Cerrar el modal
                const modal = document.getElementById('addParticipantModal');
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                
                // Mostrar mensaje de éxito
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    <strong>¡Éxito!</strong> ${data.message || 'Participante añadido correctamente.'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container-fluid').insertAdjacentElement('afterbegin', alertDiv);
                
                // Recargar la tabla de datos
                if (typeof loadTableContent === 'function') {
                    const bingoId = formData.get('bingo_id');
                    const rutaTablaTodasReservas = `${basePath}/admin/bingos/${bingoId}/reservas-tabla?tipo=todas`;
                    loadTableContent(rutaTablaTodasReservas);
                }
                
                // Limpiar el formulario
                form.reset();
                actualizarTotal();
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Mostrar mensaje de error
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    <strong>Error:</strong> ${error.message || 'Ha ocurrido un error al procesar la solicitud.'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.modal-body').insertAdjacentElement('afterbegin', alertDiv);
            })
            .finally(() => {
                // Restaurar el botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnComprobanteDuplicado = document.getElementById('btnComprobanteDuplicado');
    if (!btnComprobanteDuplicado) {
        console.error('Botón Comprobante Duplicado no encontrado');
        return;
    }
    
    // Añadir una función de respaldo para ocultar cargando
    function ocultarCargando() {
        const loadingElement = document.getElementById('loading-spinner');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
    
    btnComprobanteDuplicado.addEventListener('click', function() {
        console.log('Botón Comprobante Duplicado clickeado');
        
        // Extraer bingo_id de la URL si está presente
        const urlParts = window.location.pathname.split('/');
        const bingoIdIndex = urlParts.indexOf('bingos');
        const bingoId = bingoIdIndex !== -1 ? urlParts[bingoIdIndex + 1] : null;

        console.log('Bingo ID extraído:', bingoId);

        if (!bingoId) {
            console.error('No se pudo encontrar el bingo_id en la URL');
            ocultarCargando();
            mostrarMensaje('Error: No se pudo identificar el bingo', 'danger');
            return;
        }

        // Mostrar cargando
        if (typeof mostrarCargando === 'function') {
            mostrarCargando();
        }

        // URL con bingo_id como parámetro
        const url = bingoId 
            ? `{{ route('admin.comprobantesDuplicados') }}?bingo_id=${bingoId}`
            : "{{ route('admin.comprobantesDuplicados') }}";
        console.log('URL de solicitud:', url);

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            console.log('Estado de respuesta:', response.status);
            
            if (!response.ok) {
                // Intentar leer el cuerpo del error
                return response.text().then(errorText => {
                    console.error('Cuerpo del error:', errorText);
                    throw new Error('Error en la respuesta del servidor: ' + errorText);
                });
            }
            return response.text();
        })
        .then(html => {
            console.log('Contenido recibido (primeros 100 caracteres):', html.substring(0, 100));
            
            const container = document.getElementById('tableContent');
            if (!container) {
                throw new Error('Contenedor "tableContent" no encontrado');
            }
            container.innerHTML = html;
            console.log('HTML inyectado en tableContent:', container.innerHTML);
            
            // Reinicializar DataTable si existe la función
            if (typeof reinicializarDataTable === 'function') {
                reinicializarDataTable();
            }
            
            // Mostrar mensaje de éxito
            if (typeof mostrarMensaje === 'function') {
                mostrarMensaje('Comprobantes duplicados cargados correctamente', 'success');
            }
            
            // Activar botón si existe la función
            if (typeof activarBoton === 'function') {
                activarBoton(btnComprobanteDuplicado);
            }
            
        })
        .catch(error => {
            console.error('Error completo:', error);
        
            
            // Mostrar mensaje de error
            if (typeof mostrarMensaje === 'function') {
                mostrarMensaje('Error al cargar comprobantes duplicados: ' + error.message, 'danger');
            }
        });
    });



    
    function mostrarCargando() {
        const container = document.getElementById('tableContent');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-5">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-light">Cargando comprobantes duplicados...</p>
                </div>
            `;
        }
    }

    
    function reinicializarDataTable() {
        if (typeof $.fn.DataTable === 'function') {
            // Si ya existe una instancia de DataTable en la tabla con id "reservas-table", destrúyela.
            if ($.fn.DataTable.isDataTable('#reservas-table')) {
                $('#reservas-table').DataTable().destroy();
            }
            
            // Reinicializa DataTable sobre la tabla que debe existir dentro del HTML inyectado.
            $('#reservas-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
                },
                responsive: true,
                ordering: true,
                paging: true,
                pageLength: 50,
                stateSave: true,
                drawCallback: function() {
                    console.log('DataTable inicializado correctamente');
                }
            });
        }
    }
    
    function mostrarMensaje(mensaje, tipo) {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.container-fluid') || document.body;
        container.insertAdjacentElement('afterbegin', alerta);
        
        setTimeout(() => {
            alerta.classList.remove('show');
            setTimeout(() => alerta.remove(), 150);
        }, 5000);
    }
    
    function activarBoton(boton) {
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        });
        boton.classList.remove('btn-secondary');
        boton.classList.add('btn-primary');
    }
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

        .btn,
        button,
        form,
        .actions,
        .card-header,
        #filterForm,
        .dataTables_filter,
        .dataTables_length,
        .dataTables_paginate,
        .dataTables_info {
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

        .table th,
        .table td {
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