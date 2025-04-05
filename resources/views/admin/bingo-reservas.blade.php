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

// Script para mejorar la tabla de reservas existente
$(document).ready(function() {
  console.log('Inicializando mejoras para la tabla de reservas...');

  // Añadir el selector de filtros personalizados a la barra de botones existente
  function agregarSelectorFiltros() {
    // Buscar la barra de botones de DataTables
    const botonesContainer = $('.dt-buttons');
    
    if (botonesContainer.length) {
      console.log('Añadiendo selector de filtros junto a los botones existentes');
      
      // Crear el selector de filtros
      const filterDiv = $('<div class="dt-button-collection btn-group ms-2"></div>');
      filterDiv.html(`
        <select id="filterType" class="form-select form-select-sm" style="width: auto; display: inline-block;">
          <option value="todas" selected>Todas las reservas</option>
          <option value="comprobantes-duplicados">Comprobantes duplicados</option>
          <option value="pedidos-duplicados">Celulares duplicados</option>
          <option value="cartones-eliminados">Cartones rechazados</option>
        </select>
      `);
      
      // Añadir después de los botones existentes
      botonesContainer.after(filterDiv);
      
      // Añadir evento al selector
      $('#filterType').on('change', function() {
        const tipoFiltro = $(this).val();
        aplicarFiltro(tipoFiltro);
      });
      
      return true;
    } else {
      console.warn('No se encontró la barra de botones de DataTables');
      return false;
    }
  }
  
  // Añadir estilos CSS necesarios
  function agregarEstilosCSS() {
    if (!$('#estilos-filtrado-personalizado').length) {
      const estilos = `
        <style id="estilos-filtrado-personalizado">
          .duplicado-comprobante {
            background-color: rgba(255, 193, 7, 0.3) !important;
          }
          
          .duplicado-pedido {
            background-color: rgba(13, 110, 253, 0.3) !important;
          }
          
          .carton-eliminado {
            background-color: rgba(220, 53, 69, 0.3) !important;
          }
          
          #mensaje-filtro {
            margin-top: 10px;
            margin-bottom: 10px;
          }
        </style>
      `;
      
      $('head').append(estilos);
    }
  }
  
  // Aplicar filtro según el tipo seleccionado
  function aplicarFiltro(tipo) {
    console.log(`Aplicando filtro: ${tipo}`);
    
    // Limpiar mensajes previos
    $('#mensaje-filtro').remove();
    
    // Obtener referencia a la tabla
    const tabla = $('#reservas-table');
    
    // Quitar clases de resaltado previas
    tabla.find('tbody tr').removeClass('duplicado-comprobante duplicado-pedido carton-eliminado');
    
    // Si es "todas", simplemente mostramos todo y restauramos la tabla
    if (tipo === 'todas') {
      console.log('Mostrando todas las filas sin filtrar');
      
      // Eliminar todas las clases d-none
      tabla.find('tbody tr').removeClass('d-none');
      
      // Eliminar la fila de "no hay resultados" si existe
      $('#empty-results-row').remove();
      
      return;
    }
    
    // Determinar qué buscar según el tipo de filtro
    try {
      let filasEncontradas = [];
      let mensajeVacio = '';
      let tipoAlerta = '';
      let claseResaltado = '';
      
      switch (tipo) {
        case 'comprobantes-duplicados':
          filasEncontradas = buscarComprobantesDuplicados(tabla);
          mensajeVacio = 'No se encontraron comprobantes duplicados.';
          tipoAlerta = 'success';
          claseResaltado = 'duplicado-comprobante';
          break;
        case 'pedidos-duplicados':
          filasEncontradas = buscarPedidosDuplicados(tabla);
          mensajeVacio = 'No se encontraron números de teléfono duplicados.';
          tipoAlerta = 'info';
          claseResaltado = 'duplicado-pedido';
          break;
        case 'cartones-eliminados':
          filasEncontradas = buscarCartonesEliminados(tabla);
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
        console.log(`Se encontraron ${filasEncontradas.length} filas que cumplen el criterio de ${tipo}`);
        
        // MÉTODO DIRECTO: Ocultar todas las filas primero
        tabla.find('tbody tr').addClass('d-none');
        
        // Luego mostrar solo las filas filtradas
        $(filasEncontradas).each(function() {
          $(this).removeClass('d-none').addClass(claseResaltado);
        });
        
        // Eliminar la fila de "no hay resultados" si existe
        $('#empty-results-row').remove();
        
        // Mostrar mensaje con la cantidad de elementos encontrados
        tabla.before(`
          <div id="mensaje-filtro" class="alert alert-${tipoAlerta}">
            Se encontraron ${filasEncontradas.length} resultados.
            <button type="button" class="btn btn-outline-secondary btn-sm ms-3" onclick="exportarResultadosFiltrados('${tipo}')">
              <i class="bi bi-download"></i> Exportar resultados
            </button>
          </div>
        `);
      } else {
        // No hay resultados, ocultar todas las filas
        tabla.find('tbody tr').addClass('d-none');
        
        // Crear la fila de "no hay resultados" si no existe
        if ($('#empty-results-row').length === 0) {
          tabla.find('tbody').append(`
            <tr id="empty-results-row">
              <td colspan="12" class="text-center">No hay reservas que coincidan con el criterio.</td>
            </tr>
          `);
        } else {
          // Si ya existe, asegurarse de que sea visible
          $('#empty-results-row').removeClass('d-none');
        }
        
        // Mostrar mensaje de no resultados
        tabla.before(`<div id="mensaje-filtro" class="alert alert-${tipoAlerta}">${mensajeVacio}</div>`);
      }
    } catch (error) {
      console.error(`Error al aplicar filtro ${tipo}:`, error);
      tabla.before(`<div id="mensaje-filtro" class="alert alert-danger">Error al aplicar filtro: ${error.message}</div>`);
    }
  }
  
  // Funciones para buscar cada tipo de elemento
  function buscarComprobantesDuplicados(tabla) {
    console.log('Buscando comprobantes duplicados...');
    const comprobantes = {};
    let filasDuplicadas = [];

    // Primera pasada: recopilar todos los comprobantes
    tabla.find('tbody tr').each(function() {
      try {
        const $fila = $(this);
        // Buscar en la columna de número de comprobante (índice 9)
        const comprobante = $fila.find('td:eq(9) input').val();

        if (comprobante && comprobante.trim() !== '') {
          if (!comprobantes[comprobante]) {
            comprobantes[comprobante] = [];
          }
          comprobantes[comprobante].push($fila);
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

  function buscarPedidosDuplicados(tabla) {
    console.log('Buscando pedidos con números de teléfono duplicados...');
    const telefonos = {};
    let filasDuplicadas = [];

    // Primera pasada: recopilar todos los teléfonos
    tabla.find('tbody tr').each(function() {
      try {
        const $fila = $(this);
        // Buscar en la tercera columna (celular)
        const celular = $fila.find('td:eq(2)').text().trim();

        // Solo procesamos números no vacíos
        if (celular && celular !== '') {
          // Normalizar el número (eliminar espacios, guiones, etc.)
          const celularNormalizado = celular.replace(/[\s\-\(\)\.]/g, '');
          
          if (celularNormalizado) {
            if (!telefonos[celularNormalizado]) {
              telefonos[celularNormalizado] = [];
            }
            telefonos[celularNormalizado].push($fila);
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

  function buscarCartonesEliminados(tabla) {
    console.log('Buscando cartones eliminados (estado rechazado)...');
    let filasRechazadas = [];

    // Buscar filas con estado rechazado
    tabla.find('tbody tr').each(function() {
      try {
        const $fila = $(this);
        
        // Verificar si contiene "rechazado" en la columna de estado (columna 10)
        const estadoCell = $fila.find('td:eq(10)');
        
        if (estadoCell.text().toLowerCase().includes('rechazado') || 
            estadoCell.find('.badge.bg-danger').length > 0) {
          filasRechazadas.push($fila);
        }
      } catch (e) {
        console.warn('Error procesando fila para cartón eliminado:', e);
      }
    });

    console.log('Filas con cartones rechazados:', filasRechazadas.length);
    return filasRechazadas;
  }
  
  // Inicializar mejoras
  function inicializarMejoras() {
    console.log('Inicializando mejoras para la tabla de reservas...');
    
    // Añadir estilos CSS
    agregarEstilosCSS();
    
    // Esperar un poco para asegurarnos de que DataTables se haya inicializado
    setTimeout(function() {
      const selectorAgregado = agregarSelectorFiltros();
      
      if (selectorAgregado) {
        console.log('Mejoras de tabla instaladas correctamente');
      } else {
        console.warn('No se pudieron instalar todas las mejoras');
        
        // Intento alternativo: añadir los filtros directamente antes de la tabla
        const tabla = $('#reservas-table');
        if (tabla.length) {
          const filterDiv = $('<div class="mb-3"></div>');
          filterDiv.html(`
            <label for="filterType" class="me-2">Filtros rápidos:</label>
            <select id="filterType" class="form-select form-select-sm d-inline-block" style="width: auto;">
              <option value="todas" selected>Todas las reservas</option>
              <option value="comprobantes-duplicados">Comprobantes duplicados</option>
              <option value="pedidos-duplicados">Celulares duplicados</option>
              <option value="cartones-eliminados">Cartones rechazados</option>
            </select>
          `);
          
          tabla.before(filterDiv);
          
          // Añadir evento al selector
          $('#filterType').on('change', function() {
            const tipoFiltro = $(this).val();
            aplicarFiltro(tipoFiltro);
          });
          
          console.log('Filtros añadidos en ubicación alternativa');
        }
      }
    }, 500);
  }
  
  // Iniciar mejoras
  inicializarMejoras();
});

// Función global para exportar resultados filtrados (necesita estar disponible globalmente)
function exportarResultadosFiltrados(tipoFiltro) {
  // Verificar si la librería XLSX está disponible
  if (typeof XLSX === 'undefined') {
    alert('La librería XLSX no está disponible. No se pueden exportar los resultados.');
    return;
  }
  
  console.log('Exportando resultados filtrados...');
  
  // Crear un nuevo libro de trabajo
  const wb = XLSX.utils.book_new();
  
  // Obtener encabezados (excluyendo la columna de acciones)
  const encabezados = [];
  $('#reservas-table thead th').each(function(index) {
    if (index < 11) { // Excluir la columna de acciones
      encabezados.push($(this).text().trim());
    }
  });
  
  // Obtener datos de filas visibles
  const filas = [encabezados];
  $('#reservas-table tbody tr:not(.d-none)').each(function() {
    const fila = [];
    $(this).find('td').each(function(index) {
      if (index < 11) { // Excluir la columna de acciones
        // Obtener el texto sin HTML
        let texto = $(this).clone().children().remove().end().text().trim();
        
        // Si es la columna de comprobante, tomar el valor del input si existe
        if (index === 9) {
          const input = $(this).find('input');
          if (input.length) {
            texto = input.val().trim();
          }
        }
        
        fila.push(texto);
      }
    });
    filas.push(fila);
  });
  
  // Crear hoja de cálculo
  const ws = XLSX.utils.aoa_to_sheet(filas);
  
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
  
  // Añadir fecha actual al nombre
  const fecha = new Date();
  const fechaStr = fecha.getFullYear() + '-' + 
                  ('0' + (fecha.getMonth() + 1)).slice(-2) + '-' + 
                  ('0' + fecha.getDate()).slice(-2);
  nombreArchivo = `${nombreArchivo}_${fechaStr}.xlsx`;
  
  // Descargar el archivo
  XLSX.writeFile(wb, nombreArchivo);
  
  console.log(`Exportando ${filas.length - 1} filas a ${nombreArchivo}`);
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