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

// Función para inicializar DataTable
function initializeDataTable() {
    const table = document.querySelector('#tableContent table');
    if (!table) {
        console.error('No se encontró ninguna tabla en #tableContent');
        return;
    }

    try {
        // Destruir tabla existente si ya existe
        if (dataTable !== null) {
            dataTable.destroy();
            dataTable = null;
        }

        // Inicializar nueva DataTable
        dataTable = $(table).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
            },
            responsive: true,
            order: [
                [0, 'desc']
            ],
            columnDefs: [{
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
                }
            ],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Todos"]
            ],
            stateSave: true,
            // Asegurarse de que los filtros personalizados funcionen con la paginación
            drawCallback: function(settings) {
                // Verificar si hay un filtro personalizado activo
                const filtroActivo = document.querySelector('#filterType') ? 
                                    document.querySelector('#filterType').value : 'todas';
                
                if (filtroActivo !== 'todas') {
                    // Si hay un filtro personalizado activo, aplicamos visibilidad personalizada
                    console.log('Manteniendo filtro activo:', filtroActivo);
                    
                    // Asegurarse de que las filas filtradas permanezcan visibles
                    // y las no filtradas permanezcan ocultas después de cambiar de página
                    dataTable.rows().every(function(rowIdx) {
                        const node = this.node();
                        const esFiltrada = $(node).hasClass('duplicado-comprobante') || 
                                         $(node).hasClass('duplicado-pedido') || 
                                         $(node).hasClass('carton-eliminado');
                        
                        if (!esFiltrada) {
                            $(node).addClass('d-none');
                        } else {
                            $(node).removeClass('d-none');
                        }
                    });
                }
            }
        });

        console.log('DataTable inicializado correctamente');

        // Configurar eventos después de inicializar DataTable
        setupEventHandlers();
        
        // Configuración de los selectores de filtro
        configurarFiltros();
    } catch (error) {
        console.error('Error al inicializar DataTable:', error);
    }
}

// Función para configurar los filtros personalizados
function configurarFiltros() {
    // Si ya existe el selector de filtros, actualizamos eventos
    const selectorFiltro = document.getElementById('filterType');
    
    if (selectorFiltro) {
        console.log('Configurando eventos para selector de filtros existente');
        selectorFiltro.addEventListener('change', function() {
            const tipoFiltro = this.value;
            filtrarPorTipo(tipoFiltro);
        });
    } else {
        // Si no existe, podemos crearlo de manera dinámica
        console.log('Creando selector de filtros personalizado');
        
        // Crear el contenedor de filtros si no existe
        let filterContainer = document.querySelector('.dataTables_filter');
        if (!filterContainer) {
            // Si no hay un contenedor existente, crear uno
            const tableTools = document.createElement('div');
            tableTools.className = 'table-tools-container mt-2 mb-3 d-flex flex-wrap justify-content-between align-items-center';
            const tableWrapper = document.querySelector('.dataTables_wrapper');
            if (tableWrapper) {
                tableWrapper.prepend(tableTools);
                filterContainer = tableTools;
            }
        }
        
        if (filterContainer) {
            // Agregar el selector de filtros personalizados
            const filterDiv = document.createElement('div');
            filterDiv.className = 'custom-filter-container me-2';
            filterDiv.innerHTML = `
                <label class="me-2">Filtros rápidos:</label>
                <select id="filterType" class="form-select form-select-sm d-inline-block" style="width: auto;">
                    <option value="todas" selected>Todas las reservas</option>
                    <option value="comprobantes-duplicados">Comprobantes duplicados</option>
                    <option value="pedidos-duplicados">Pedidos con celular duplicado</option>
                    <option value="cartones-eliminados">Cartones rechazados</option>
                </select>
            `;
            
            filterContainer.prepend(filterDiv);
            
            // Agregar evento al selector
            document.getElementById('filterType').addEventListener('change', function() {
                const tipoFiltro = this.value;
                filtrarPorTipo(tipoFiltro);
            });
        }
    }
}

// Función para cargar la tabla vía AJAX con soporte para filtros
function loadTableContent(url, filtrarDespues = false, tipoFiltro = '') {
    // Cancelar cualquier solicitud de carga previa
    if (window.currentTableLoadRequest && typeof window.currentTableLoadRequest.abort === 'function') {
        window.currentTableLoadRequest.abort();
    }

    console.log('Intentando cargar tabla desde URL:', url);

    // Crear un nuevo AbortController
    const controller = new AbortController();
    window.currentTableLoadRequest = controller;

    // Mostrar indicador de carga
    document.getElementById('tableContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div><p class="mt-2 text-light">Cargando...</p></div>';

    // Hacer la petición AJAX
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal // Añadir la señal de aborto
    })
    .then(response => {
        console.log('Estado de respuesta:', response.status);
        if (!response.ok) {
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
        
        // Inicializar DataTable
        initializeDataTable();
        
        // Si hay que filtrar después de cargar, aplicar el filtro
        if (filtrarDespues && dataTable) {
            setTimeout(() => {
                // Actualizar también el selector visual si existe
                const selectorFiltro = document.getElementById('filterType');
                if (selectorFiltro) {
                    selectorFiltro.value = tipoFiltro;
                }
                
                filtrarPorTipo(tipoFiltro);
            }, 300); // Aumentamos el tiempo para asegurar que DataTable está completamente inicializado
        }
    })
    .catch(error => {
        // Ignorar errores de aborto
        if (error.name === 'AbortError') {
            console.log('Carga de tabla cancelada');
            return;
        }

        console.error('Error cargando tabla:', error);
        document.getElementById('tableContent').innerHTML =
            `<div class="alert alert-danger text-center">
                Error al cargar los datos: ${error.message}<br>
                <button class="btn btn-sm btn-primary mt-2" onclick="window.location.reload()">Recargar página</button>
            </div>`;
    })
    .finally(() => {
        // Limpiar la referencia al request actual
        if (window.currentTableLoadRequest === controller) {
            window.currentTableLoadRequest = null;
        }
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
                    order: [
                        [0, 'desc']
                    ],
                    columnDefs: [{
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
                        }
                    ],
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Todos"]
                    ],
                    stateSave: true
                });

                console.log('DataTable inicializado correctamente');

                // Configurar eventos después de inicializar DataTable
                setupEventHandlers();
            } catch (error) {
                console.error('Error al inicializar DataTable:', error);
            }
        }

// Función mejorada para filtrar según el tipo seleccionado
function filtrarPorTipo(tipo) {
    console.log(`Aplicando filtro: ${tipo}`);
    
    if (!dataTable) {
        console.error('DataTable no está inicializada, no se puede filtrar');
        return;
    }

    // Limpiar mensajes previos
    $('#mensaje-filtro').remove();
    
    // Quitar clases de resaltado previas
    $('.duplicado-comprobante, .duplicado-pedido, .carton-eliminado').removeClass('duplicado-comprobante duplicado-pedido carton-eliminado');
    
    // Si es "todas", simplemente mostramos todo y salimos
    if (tipo === 'todas') {
        console.log('Mostrando todas las filas sin filtrar');
        
        // Restaurar tabla normal sin filtros
        $('tr.d-none').removeClass('d-none');
        dataTable.draw();
        return;
    }

    // Determinar qué buscar según el tipo de filtro
    try {
        // Identificar filas según tipo de filtro
        let filasEncontradas = [];
        let mensajeVacio = '';
        let tipoAlerta = '';
        let claseResaltado = '';
        
        switch (tipo) {
            case 'comprobantes-duplicados':
                filasEncontradas = buscarComprobantesDuplicados();
                mensajeVacio = 'No se encontraron comprobantes duplicados.';
                tipoAlerta = 'success';
                claseResaltado = 'duplicado-comprobante';
                break;
            case 'pedidos-duplicados':
                filasEncontradas = buscarPedidosDuplicados();
                mensajeVacio = 'No se encontraron números de teléfono duplicados.';
                tipoAlerta = 'info';
                claseResaltado = 'duplicado-pedido';
                break;
            case 'cartones-eliminados':
                filasEncontradas = buscarCartonesEliminados();
                mensajeVacio = 'No se encontraron reservas con estado rechazado.';
                tipoAlerta = 'danger';
                claseResaltado = 'carton-eliminado';
                break;
            default:
                console.error('Tipo de filtro no reconocido:', tipo);
                return;
        }
        
        // Mostrar resultados o mensaje de vacío
        if (filasEncontradas.length > 0) {
            console.log(`Se encontraron ${filasEncontradas.length} filas que cumplen el criterio`);
            
            // SOLUCIÓN DIRECTA CON JQUERY:
            // 1. Primero ocultamos TODAS las filas con jQuery
            const tabla = $('#tableContent table');
            tabla.find('tbody tr').addClass('d-none');
            
            // 2. Luego mostramos solo las filas que queremos
            filasEncontradas.forEach(function(rowIdx) {
                const fila = dataTable.row(rowIdx).node();
                $(fila).removeClass('d-none').addClass(claseResaltado);
            });
            
            // 3. Mostrar mensaje con la cantidad de elementos encontrados
            $('#tableContent').prepend(`
                <div id="mensaje-filtro" class="alert alert-${tipoAlerta}">
                    Se encontraron ${filasEncontradas.length} resultados.
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-3" onclick="exportarResultados('${tipo}')">
                        <i class="fas fa-download"></i> Exportar resultados
                    </button>
                </div>
            `);
        } else {
            // No hay resultados, ocultar todas las filas y mostrar mensaje
            $('#tableContent table tbody tr').addClass('d-none');
            
            $('#tableContent').prepend(`<div id="mensaje-filtro" class="alert alert-${tipoAlerta}">${mensajeVacio}</div>`);
            console.log('No se encontraron resultados para este filtro');
        }
        
    } catch (error) {
        console.error(`Error al aplicar filtro ${tipo}:`, error);
        $('#tableContent').prepend(`<div id="mensaje-filtro" class="alert alert-danger">Error al aplicar filtro: ${error.message}</div>`);
    }
    
    // Funciones para buscar cada tipo de elemento
    function buscarComprobantesDuplicados() {
        console.log('Buscando comprobantes duplicados...');
        const comprobantes = {};
        let filasDuplicadas = [];

        // Primera pasada: recopilar todos los comprobantes
        dataTable.rows().every(function(rowIdx) {
            try {
                const fila = this.node();
                const comprobante = $(fila).find('input[type="text"]').val();

                if (comprobante && comprobante.trim() !== '') {
                    if (!comprobantes[comprobante]) {
                        comprobantes[comprobante] = [];
                    }
                    comprobantes[comprobante].push(rowIdx);
                }
            } catch (e) {
                console.warn('Error procesando fila para comprobante duplicado:', e);
            }
        });

        // Segunda pasada: identificar duplicados
        for (const comp in comprobantes) {
            if (comprobantes[comp].length > 1) {
                filasDuplicadas = filasDuplicadas.concat(comprobantes[comp]);
            }
        }

        console.log('Filas con comprobantes duplicados:', filasDuplicadas.length);
        return filasDuplicadas;
    }

    function buscarPedidosDuplicados() {
        console.log('Buscando pedidos con números de teléfono duplicados...');
        const telefonos = {};
        let filasDuplicadas = [];

        // Primera pasada: recopilar todos los teléfonos
        dataTable.rows().every(function(rowIdx) {
            try {
                const fila = this.node();
                // Buscar en la tercera columna (índice 2)
                const celular = $(fila).find('td:eq(2)').text().trim();

                // Solo procesamos números no vacíos
                if (celular && celular !== '') {
                    // Normalizar el número (eliminar espacios, guiones, etc.)
                    const celularNormalizado = celular.replace(/[\s\-\(\)\.]/g, '');
                    
                    if (celularNormalizado) {
                        if (!telefonos[celularNormalizado]) {
                            telefonos[celularNormalizado] = [];
                        }
                        telefonos[celularNormalizado].push(rowIdx);
                    }
                }
            } catch (e) {
                console.warn('Error procesando fila para pedido duplicado:', e);
            }
        });

        // Segunda pasada: identificar duplicados
        for (const telefono in telefonos) {
            if (telefonos[telefono].length > 1) {
                filasDuplicadas = filasDuplicadas.concat(telefonos[telefono]);
            }
        }

        console.log('Filas con teléfonos duplicados:', filasDuplicadas.length);
        return filasDuplicadas;
    }

    function buscarCartonesEliminados() {
        console.log('Buscando cartones eliminados (estado rechazado)...');
        let filasRechazadas = [];

        // Buscar filas con estado rechazado
        dataTable.rows().every(function(rowIdx) {
            try {
                const fila = this.node();
                
                // Buscar el estado en cualquier columna que pueda contenerlo
                const filaCompleta = $(fila);
                const contieneRechazado = filaCompleta.text().toLowerCase().includes('rechazado');
                
                // También buscar específicamente en la columna de estado (suponiendo índice 10)
                const estadoCell = filaCompleta.find('td:eq(10)');
                const tieneEstadoRechazado = estadoCell.length > 0 && 
                    (estadoCell.text().toLowerCase().includes('rechazado') || 
                     estadoCell.find('.badge.bg-danger').length > 0 ||
                     estadoCell.hasClass('estado-rechazado'));
                
                if (contieneRechazado || tieneEstadoRechazado) {
                    filasRechazadas.push(rowIdx);
                }
            } catch (e) {
                console.warn('Error procesando fila para cartón eliminado:', e);
            }
        });

        console.log('Filas con cartones rechazados:', filasRechazadas.length);
        return filasRechazadas;
    }
}

// Agregar CSS necesario para el filtrado
function agregarEstilosCSS() {
    // Comprobar si ya existe
    if (!document.getElementById('estilos-filtrado-personalizado')) {
        const estilos = document.createElement('style');
        estilos.id = 'estilos-filtrado-personalizado';
        estilos.innerHTML = `
            .duplicado-comprobante {
                background-color: rgba(255, 193, 7, 0.2) !important;
            }
            
            .duplicado-pedido {
                background-color: rgba(13, 110, 253, 0.2) !important;
            }
            
            .carton-eliminado {
                background-color: rgba(220, 53, 69, 0.2) !important;
            }
            
            /* Estilos para cuando se pasa el mouse por encima */
            .duplicado-comprobante:hover {
                background-color: rgba(255, 193, 7, 0.3) !important;
            }
            
            .duplicado-pedido:hover {
                background-color: rgba(13, 110, 253, 0.3) !important;
            }
            
            .carton-eliminado:hover {
                background-color: rgba(220, 53, 69, 0.3) !important;
            }
            
            /* Estilo para verificar visibilidad durante depuración */
            /* tr:not(.d-none) {
                border: 2px solid green !important;
            } */
        `;
        document.head.appendChild(estilos);
    }
}

// Función para exportar los resultados filtrados
function exportarResultados(tipoFiltro) {
    // Crear un nuevo objeto de libro de trabajo
    const wb = XLSX.utils.book_new();
    
    // Obtener solo las filas visibles (las que no tienen clase d-none)
    const filasVisibles = [];
    $('#tableContent table tbody tr:not(.d-none)').each(function() {
        const datosFilas = [];
        $(this).find('td').each(function() {
            // Obtener texto sin HTML
            datosFilas.push($(this).text().trim());
        });
        filasVisibles.push(datosFilas);
    });
    
    // Obtener los nombres de las columnas
    const columnas = [];
    $('#tableContent table thead th').each(function() {
        columnas.push($(this).text().trim());
    });
    
    // Preparar los datos para la exportación
    const datos = [columnas].concat(filasVisibles);
    
    // Crear hoja de cálculo
    const ws = XLSX.utils.aoa_to_sheet(datos);
    
    // Añadir la hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, "Resultados");
    
    // Determinar nombre del archivo basado en el tipo de filtro
    let nombreArchivo = 'resultados';
    switch (tipoFiltro) {
        case 'comprobantes-duplicados':
            nombreArchivo = 'comprobantes_duplicados';
            break;
        case 'pedidos-duplicados':
            nombreArchivo = 'pedidos_celular_duplicado';
            break;
        case 'cartones-eliminados':
            nombreArchivo = 'cartones_rechazados';
            break;
    }
    
    // Descargar el archivo
    const fechaActual = new Date().toISOString().slice(0,10);
    nombreArchivo = `${nombreArchivo}_${fechaActual}.xlsx`;
    XLSX.writeFile(wb, nombreArchivo);
    
    console.log(`Exportados ${filasVisibles.length} resultados a ${nombreArchivo}`);
}

// Función para cargar la tabla vía AJAX con soporte para filtros
function loadTableContent(url, filtrarDespues = false, tipoFiltro = '') {
    // Cancelar cualquier solicitud de carga previa
    if (window.currentTableLoadRequest && typeof window.currentTableLoadRequest.abort === 'function') {
        window.currentTableLoadRequest.abort();
    }

    console.log('Intentando cargar tabla desde URL:', url);

    // Crear un nuevo AbortController
    const controller = new AbortController();
    window.currentTableLoadRequest = controller;

    // Mostrar indicador de carga
    document.getElementById('tableContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div><p class="mt-2 text-light">Cargando...</p></div>';

    // Hacer la petición AJAX
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal // Añadir la señal de aborto
    })
    .then(response => {
        console.log('Estado de respuesta:', response.status);
        if (!response.ok) {
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
        
        // Inicializar DataTable
        initializeDataTable();
        
        // Configurar selector de filtros personalizado
        configurarFiltros();
        
        // Si hay que filtrar después de cargar, aplicar el filtro
        if (filtrarDespues && dataTable) {
            setTimeout(() => {
                // Actualizar también el selector visual si existe
                const selectorFiltro = document.getElementById('filterType');
                if (selectorFiltro) {
                    selectorFiltro.value = tipoFiltro;
                }
                
                filtrarPorTipo(tipoFiltro);
            }, 500); // Aumentamos el tiempo para asegurar que DataTable está completamente inicializado
        }
    })
    .catch(error => {
        // Ignorar errores de aborto
        if (error.name === 'AbortError') {
            console.log('Carga de tabla cancelada');
            return;
        }

        console.error('Error cargando tabla:', error);
        document.getElementById('tableContent').innerHTML =
            `<div class="alert alert-danger text-center">
                Error al cargar los datos: ${error.message}<br>
                <button class="btn btn-sm btn-primary mt-2" onclick="window.location.reload()">Recargar página</button>
            </div>`;
    })
    .finally(() => {
        // Limpiar la referencia al request actual
        if (window.currentTableLoadRequest === controller) {
            window.currentTableLoadRequest = null;
        }
    });
}

// Función para configurar los filtros personalizados
function configurarFiltros() {
    // Si ya existe el selector de filtros, actualizamos eventos
    const selectorFiltro = document.getElementById('filterType');
    
    if (selectorFiltro) {
        console.log('Configurando eventos para selector de filtros existente');
        
        // Eliminar eventos anteriores para evitar duplicación
        const nuevoSelector = selectorFiltro.cloneNode(true);
        selectorFiltro.parentNode.replaceChild(nuevoSelector, selectorFiltro);
        
        // Agregar el nuevo evento
        nuevoSelector.addEventListener('change', function() {
            const tipoFiltro = this.value;
            filtrarPorTipo(tipoFiltro);
        });
    } else {
        // Si no existe, podemos crearlo de manera dinámica
        console.log('Creando selector de filtros personalizado');
        
        // Crear el contenedor de filtros si no existe
        let filterContainer = document.querySelector('.dataTables_filter');
        if (!filterContainer) {
            // Si no hay un contenedor existente, crear uno
            const tableTools = document.createElement('div');
            tableTools.className = 'table-tools-container mt-2 mb-3 d-flex flex-wrap justify-content-between align-items-center';
            const tableWrapper = document.querySelector('.dataTables_wrapper');
            if (tableWrapper) {
                tableWrapper.prepend(tableTools);
                filterContainer = tableTools;
            }
        }
        
        if (filterContainer) {
            // Agregar el selector de filtros personalizados
            const filterDiv = document.createElement('div');
            filterDiv.className = 'custom-filter-container me-2';
            filterDiv.innerHTML = `
                <label class="me-2">Filtros rápidos:</label>
                <select id="filterType" class="form-select form-select-sm d-inline-block" style="width: auto;">
                    <option value="todas" selected>Todas las reservas</option>
                    <option value="comprobantes-duplicados">Comprobantes duplicados</option>
                    <option value="pedidos-duplicados">Pedidos con celular duplicado</option>
                    <option value="cartones-eliminados">Cartones rechazados</option>
                </select>
            `;
            
            filterContainer.prepend(filterDiv);
            
            // Agregar evento al selector
            document.getElementById('filterType').addEventListener('change', function() {
                const tipoFiltro = this.value;
                filtrarPorTipo(tipoFiltro);
            });
        }
    }
}

// Función mejorada para inicializar DataTable
function initializeDataTable() {
    const table = document.querySelector('#tableContent table');
    if (!table) {
        console.error('No se encontró ninguna tabla en #tableContent');
        return;
    }

    // Agregar los estilos CSS necesarios
    agregarEstilosCSS();

    try {
        // Destruir tabla existente si ya existe
        if (dataTable !== null) {
            dataTable.destroy();
            dataTable = null;
        }

        // Inicializar nueva DataTable
        dataTable = $(table).DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
            },
            responsive: true,
            order: [
                [0, 'desc']
            ],
            columnDefs: [{
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
                }
            ],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Todos"]
            ],
            stateSave: true
        });

        console.log('DataTable inicializado correctamente');

        // Configurar eventos después de inicializar DataTable
        if (typeof setupEventHandlers === 'function') {
            setupEventHandlers();
        }
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

document.getElementById('btnComprobanteDuplicado').addEventListener('click', async function() {
    updateActiveButton(this);
    tipoActual = 'comprobantes-duplicados';
    
    // Siempre cargar la tabla completa primero, luego filtrar
    await loadTableContent(rutaTablaTodasReservas);
    filtrarPorTipo('comprobantes-duplicados');
});

document.getElementById('btnPedidoDuplicado').addEventListener('click', async function() {
    updateActiveButton(this);
    tipoActual = 'pedidos-duplicados';
    
    // Siempre cargar la tabla completa primero, luego filtrar
    await loadTableContent(rutaTablaTodasReservas);
    filtrarPorTipo('pedidos-duplicados');
});

document.getElementById('btnCartonesEliminados').addEventListener('click', async function() {
    updateActiveButton(this);
    tipoActual = 'cartones-eliminados';
    
    // Siempre cargar la tabla completa primero, luego filtrar
    await loadTableContent(rutaTablaTodasReservas);
    filtrarPorTipo('cartones-eliminados');
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