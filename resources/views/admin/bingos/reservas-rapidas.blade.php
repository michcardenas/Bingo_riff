@extends('layouts.admin')

@section('content')
<div class="container container-xl-custom my-4">
    <!-- Header with improved styling -->
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-white">
    <i class="bi bi-grid-3x3"></i> Reservas del Bingo :  <strong>{{ $bingo->nombre }}</strong>
</h4>


        
    </div>

 <!-- Buscador OCR independiente -->
<div class="card bg-dark border-0 shadow-sm mb-3 rounded-3" style="border-left: 3px solid #0dcaf0 !important;">
    <div class="card-header bg-dark border-bottom border-secondary py-3">
        <form action="{{ route('bingos.reservas.filtro', $bingoId) }}" method="GET" id="filtro-ocr-form">
            {{-- Preservar filtros existentes --}}
            <input type="hidden" name="campo" value="{{ $campoFiltro ?? 'nombre' }}">
            <input type="hidden" name="search" value="{{ $searchTerm ?? '' }}">
            <input type="hidden" name="estado" value="{{ $estadoFilter ?? 'todos' }}">
            <input type="hidden" name="fecha_desde" value="{{ $fechaDesde ?? '' }}">
            <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta ?? '' }}">

            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label text-info mb-1 fw-semibold">
                        <i class="bi bi-eye"></i> Buscar por datos OCR:
                    </label>
                    <div class="input-group input-group-sm">
                        <select name="campo_ocr" class="form-select bg-dark border-info text-light w-auto">
                            <option value="referencia_ocr" {{ ($campoOcr ?? '') === 'referencia_ocr' ? 'selected' : '' }}>Referencia</option>
                            <option value="fecha_ocr" {{ ($campoOcr ?? '') === 'fecha_ocr' ? 'selected' : '' }}>Fecha / Hora</option>
                            <option value="banco_ocr" {{ ($campoOcr ?? '') === 'banco_ocr' ? 'selected' : '' }}>Banco</option>
                        </select>
                        <input type="text" name="search_ocr"
                               class="form-control bg-dark border-info text-light"
                               placeholder="Ej: M12345678, 2026-03-05, nequi"
                               value="{{ $searchOcr ?? '' }}">
                        <button type="submit" class="btn btn-sm btn-info px-3">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4 d-flex justify-content-end">
                    <a href="{{ route('bingos.reservas.rapidas', $bingoId) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar todo
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Filtro de fecha de registro -->
<div class="card bg-dark border-0 shadow-sm mb-3 rounded-3" style="border-left: 3px solid #ffc107 !important;">
    <div class="card-header bg-dark border-bottom border-secondary py-3">
        <form action="{{ route('bingos.reservas.filtro', $bingoId) }}" method="GET" id="filtro-fecha-form">
            {{-- Preservar otros filtros --}}
            <input type="hidden" name="campo" value="{{ $campoFiltro ?? 'nombre' }}">
            <input type="hidden" name="search" value="{{ $searchTerm ?? '' }}">
            <input type="hidden" name="estado" value="{{ $estadoFilter ?? 'todos' }}">
            <input type="hidden" name="campo_ocr" value="{{ $campoOcr ?? '' }}">
            <input type="hidden" name="search_ocr" value="{{ $searchOcr ?? '' }}">

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-warning mb-1 fw-semibold">
                        <i class="bi bi-calendar-range"></i> Fecha registro desde:
                    </label>
                    <input type="datetime-local" name="fecha_desde"
                           class="form-control form-control-sm bg-dark border-warning text-light"
                           value="{{ $fechaDesde ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-warning mb-1 fw-semibold">
                        <i class="bi bi-calendar-range"></i> Hasta:
                    </label>
                    <input type="datetime-local" name="fecha_hasta"
                           class="form-control form-control-sm bg-dark border-warning text-light"
                           value="{{ $fechaHasta ?? '' }}">
                </div>
                <div class="col-md-4 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-sm btn-warning px-3">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <a href="{{ route('bingos.reservas.rapidas', $bingoId) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Card container for the table -->
<div class="card bg-dark border-0 shadow-sm mb-4 rounded-3">
    <div class="card-header bg-dark border-bottom border-secondary py-3">
        <form action="{{ route('bingos.reservas.filtro', $bingoId) }}" method="GET" id="filtro-form">
            {{-- Preservar filtros OCR y fecha --}}
            <input type="hidden" name="campo_ocr" value="{{ $campoOcr ?? '' }}">
            <input type="hidden" name="search_ocr" value="{{ $searchOcr ?? '' }}">
            <input type="hidden" name="fecha_desde" value="{{ $fechaDesde ?? '' }}">
            <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta ?? '' }}">
            <div class="row g-3 align-items-end justify-content-between">

                <!-- Campo de búsqueda con tipo -->
                <div class="col-md-6">
                    <label for="search-input" class="form-label text-light mb-1 fw-semibold">
                        Buscar por:
                    </label>
                    <div class="input-group input-group-sm">
                        <select name="campo" id="campo" class="form-select bg-dark border-secondary text-light w-auto">
                            <option value="nombre" {{ ($campoFiltro ?? 'nombre') === 'nombre' ? 'selected' : '' }}>Nombre</option>
                            <option value="celular" {{ ($campoFiltro ?? '') === 'celular' ? 'selected' : '' }}>Celular</option>
                            <option value="series" {{ ($campoFiltro ?? '') === 'series' ? 'selected' : '' }}>Serie</option>
                        </select>
                        <input type="text" name="search" id="search-input"
                               class="form-control bg-dark border-secondary text-light"
                               placeholder="Ej: Maria, 313xxx, 000123"
                               value="{{ $searchTerm ?? '' }}">
                        <button type="submit" class="btn btn-sm btn-primary px-3">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Filtro por estado -->
                <div class="col-md-6 d-flex justify-content-end gap-2 flex-wrap">
                    <div>
                        <label for="estado-filter" class="form-label text-light mb-1 fw-semibold">Estado</label>
                        <select name="estado" id="estado-filter"
                                class="form-select form-select-sm bg-dark border-secondary text-light w-auto">
                            <option value="todos" {{ ($estadoFilter ?? 'todos') == 'todos' ? 'selected' : '' }}>Todos</option>
                            <option value="revision" {{ ($estadoFilter ?? '') == 'revision' ? 'selected' : '' }}>Revisión</option>
                            <option value="aprobado" {{ ($estadoFilter ?? '') == 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                            <option value="rechazado" {{ ($estadoFilter ?? '') == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-filter"></i> Aplicar
                        </button>
                        <a href="{{ route('bingos.reservas.rapidas', $bingoId) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
<div class="mt-3 d-flex justify-content-end gap-2">

    @if(strtolower($bingo->estado) !== 'archivado' && strtolower($bingo->estado) !== 'cerrado')
        <a href="{{ route('bingos.reservas.create', $bingoId) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-plus-circle"></i> Crear Nuevo
        </a>
    @endif

    <a href="{{ route('bingos.reservas.duplicadas', $bingoId) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-exclamation-triangle"></i> Comprobantes Duplicados
    </a>

    <a href="{{ route('bingos.reservas.duplicadas-ocr', $bingoId) }}" class="btn btn-sm btn-outline-info">
        <i class="bi bi-search"></i> Duplicados por Referencia
    </a>

    <a href="{{ route('bingos.reservas.pedidos-duplicados', $bingoId) }}" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-files"></i> Pedido Duplicado
    </a>

    <a href="{{ route('bingos.reservas.buscar-ganador', $bingoId) }}" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-search"></i> Buscar Ganador
</a>
<a href="{{ route('bingos.reservas.rechazados.excel', $bingoId) }}" class="btn btn-sm btn-outline-dark">
    <i class="bi bi-download"></i> Excel Rechazados
</a>
<a href="{{ route('bingos.reservas.rechazados.view', $bingoId) }}" class="btn btn-sm btn-outline-danger">
    <i class="bi bi-eye"></i> Ver Rechazados
</a>

<a href="{{ route('bingos.reservas.aprobados.view', $bingoId) }}" class="btn btn-sm btn-outline-success">
    <i class="bi bi-eye"></i> Ver Aprobados
</a>


</div>



        
        <div class="card-body p-0">
            <div class="table-responsive-lg">
                <table id="tabla-reservas" class="table table-dark table-hover table-bordered border-secondary mb-0 align-middle small w-100">
                    <thead>
                        <tr class="bg-black text-white">
                            <th>Id Bingo</th>
                            <th>Nombre</th>
                            <th>Celular</th>
                            <th>Fecha</th>
                            <th>Cantidad Cartones</th>
                            <th># Carton</th>
                            <th>Total</th>
                            <th>Comprobante</th>
                            <th># Comp.</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($reservas as $reserva)
                        <tr>
                        <td>
                            @if (!is_null($reserva->orden_bingo))
                                {{ $reserva->orden_bingo }}
                            @else
                                -
                            @endif
                        </td> <!-- Este es el Id Bingo (orden de compra) -->

                        <td><input type="text" class="form-control form-control-sm bg-dark text-light campo-nombre" value="{{ $reserva->nombre }}" data-id="{{ $reserva->id }}"></td>
                        <td><input type="text" class="form-control form-control-sm bg-dark text-light campo-celular" value="{{ $reserva->celular }}" data-id="{{ $reserva->id }}"></td>
                        <td>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y H:i') }}</td>
                            <td>{{ $reserva->cartones }}</td>
                            @php 
    $series = json_decode($reserva->series, true);

    if (!is_array($series)) {
        // Por si viniera como string malformado
        $series = preg_split('/[",\s]+/', $reserva->series);
    }

    $seriesClean = array_filter(array_map(function($s) {
        return trim(preg_replace('/[^0-9]/', '', $s));
    }, $series));
@endphp

<td title="{{ implode(', ', $seriesClean) }}">
    @foreach ($seriesClean as $serie)
        <div class="d-flex justify-content-between align-items-center mb-1 bg-secondary rounded px-2 py-1">
            <span class="serie-numero" 
                  data-serie="{{ $serie }}" 
                  data-bingo-id="{{ $bingoId }}"
                  title="Clic para descargar cartón">
                {{ $serie }}
            </span>
            <button 
                class="btn btn-sm btn-danger btn-eliminar-serie ms-2 py-0 px-2"
                data-id="{{ $reserva->id }}"
                data-serie="{{ $serie }}"
                title="Eliminar serie"
            >
                <i class="bi bi-x"></i>
            </button>
        </div>
    @endforeach
</td>



                            <td><input type="number" class="form-control form-control-sm bg-dark text-light campo-total" value="{{ $reserva->total }}" data-id="{{ $reserva->id }}"></td>
                            <td>
    @php
        $comprobantes = [];

        if (!empty($reserva->ruta_comprobante)) {
            // Caso especial si hay ruta comprobante directa
            $comprobantes[] = $reserva->ruta_comprobante;
        } elseif (!empty($reserva->comprobante)) {
            // Eliminar comillas extras al inicio y final si existen
            $comprobanteRaw = trim($reserva->comprobante, "\"");
            
            // Intentar decodificar como JSON
            $decoded = json_decode($comprobanteRaw, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Si es un JSON válido y es un array
                
                // Validar si el primer elemento es un string largo con comas
                if (count($decoded) === 1 && str_contains($decoded[0], ',')) {
                    // Separar por comas
                    $partes = explode(',', $decoded[0]);
                    foreach ($partes as $parte) {
                        $parte = trim($parte);
                        if (!empty($parte)) {
                            $comprobantes[] = $parte;
                        }
                    }
                } else {
                    // Si no, es un array normal
                    $comprobantes = $decoded;
                }
            } else {
                // Si no es JSON, puede estar separado por comas directamente
                $partes = explode(',', $comprobanteRaw);
                foreach ($partes as $parte) {
                    $parte = trim($parte);
                    if (!empty($parte)) {
                        $comprobantes[] = $parte;
                    }
                }
            }
        }
    @endphp

    @if($reserva->ocr_data && $reserva->ocr_status === 'procesado')
    @php
        $ocrRaw = is_string($reserva->ocr_data) ? json_decode($reserva->ocr_data, true) : (is_array($reserva->ocr_data) ? $reserva->ocr_data : null);
        if ($ocrRaw) {
            unset($ocrRaw['texto_crudo']);
            $ocrSafe = json_encode($ocrRaw, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG);
        } else {
            $ocrSafe = '{}';
        }
    @endphp
    <div class="text-center">
        <button class="btn btn-sm ver-ocr mb-1"
            data-id="{{ $reserva->id }}"
            data-ocr="{{ $ocrSafe }}"
            data-status="{{ $reserva->ocr_status }}"
            style="border-color: #0dcaf0; color: #0dcaf0;">
            <i class="bi bi-eye"></i>
        </button>
    </div>
    @endif

    @if (count($comprobantes))
        @foreach ($comprobantes as $index => $comprobante)
            @php
                // Limpiar la ruta eliminando barras duplicadas o invertidas
                $rutaComprobante = str_replace(['\\', '//'], '/', $comprobante);
                
                // Asegurarse que la URL esté completa (por si acaso viene con o sin el prefijo de dominio)
                $fullUrl = $rutaComprobante;
                if (!str_starts_with($rutaComprobante, 'http')) {
                    $fullUrl = asset($rutaComprobante);
                }
            @endphp
            <a href="{{ $fullUrl }}" target="_blank" class="btn btn-sm btn-dark mb-1 d-block">
                <i class="bi bi-download"></i> Descargar comprobante {{ count($comprobantes) > 1 ? ($index + 1) : '' }}
            </a>
        @endforeach
    @else
        <span class="text-danger">Sin comprobante</span>
    @endif
</td>





                            <td><input type="text"
                                                value="{{ $reserva->numero_comprobante }}"
                                                class="form-control form-control-sm bg-dark border-secondary text-light numero-comprobante"
                                                data-id="{{ $reserva->id }}"
                                                style="min-width: 120px;">
                                            </td>
                                            <td>
                                            <span class="estado-badge badge
                                                {{ $reserva->estado == 'revision' ? 'bg-warning text-dark' : '' }}
                                                {{ $reserva->estado == 'aprobado' ? 'bg-success' : '' }}
                                                {{ $reserva->estado == 'rechazado' ? 'bg-danger' : '' }}
                                                fs-6 px-3 py-2"
                                                data-id="{{ $reserva->id }}">
                                                {{ ucfirst($reserva->estado) }}
                                            </span>
                                            @if(in_array($reserva->id, $refsDuplicadas ?? []))
                                                <br>
                                                <span class="badge bg-danger mt-1 px-2 py-1">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> Duplicado
                                                </span>
                                            @endif
                                        </td>

                            <td>
                            <div class="d-grid gap-2">
                            <button class="btn btn-sm btn-primary actualizar-reserva" data-id="{{ $reserva->id }}">
                            <i class="bi bi-save"></i> Actualizar
                        </button>
                        <button class="btn btn-sm btn-outline-info subir-comprobante" data-id="{{ $reserva->id }}">
    <i class="bi bi-upload"></i>   Comprobante
</button>

                                    <button class="btn btn-sm btn-outline-warning cambiar-estado" data-id="{{ $reserva->id }}" data-estado="revision">
                                        <i class="bi bi-pencil"></i> Revision
                                    </button>
                                    <button class="btn btn-sm btn-outline-success cambiar-estado" data-id="{{ $reserva->id }}" data-estado="aprobado">
                                        <i class="bi bi-check-lg"></i> Aprobar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger cambiar-estado" data-id="{{ $reserva->id }}" data-estado="rechazado">
                                        <i class="bi bi-x-lg"></i> Rechazar
                                    </button>
                                </div>

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="card-footer bg-dark border-top border-secondary py-3">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            @if ($reservas instanceof \Illuminate\Pagination\LengthAwarePaginator)
                Mostrando {{ $reservas->firstItem() }} - {{ $reservas->lastItem() }} de {{ $reservas->total() }} reservas
            @else
                Total reservas: {{ $reservas->count() }}
            @endif
        </div>
        <div>
            @if ($reservas instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $reservas->links('pagination::bootstrap-5') }}
            @endif
        </div>
    </div>
</div>
<!-- Modal de subida de comprobante -->
<div class="modal fade" id="modalComprobante" tabindex="-1" aria-labelledby="modalComprobanteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formComprobante" enctype="multipart/form-data" action="javascript:void(0);">
      @csrf
      <input type="hidden" name="reserva_id" id="comprobanteReservaId">
      <div class="modal-content bg-dark text-light">
        <div class="modal-header">
          <h5 class="modal-title" id="modalComprobanteLabel">Actualizar Comprobante</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="archivoComprobante" class="form-label">Seleccionar archivo</label>
            <input class="form-control" type="file" name="comprobante" id="archivoComprobante" accept="image/*,.pdf" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>


<!-- Modal de datos OCR -->
<div class="modal fade" id="modalOCR" tabindex="-1" aria-labelledby="modalOCRLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="modalOCRLabel"><i class="bi bi-cpu"></i> Datos del Comprobante (OCR)</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label text-light small mb-0 opacity-75">Banco / App</label>
              <div id="ocr-banco" class="fs-5 fw-semibold text-info">-</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-light small mb-0 opacity-75">Monto</label>
              <div id="ocr-monto" class="fs-5 fw-semibold text-success">-</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-light small mb-0 opacity-75">Referencia</label>
              <div id="ocr-referencia" class="fs-5 fw-semibold text-warning">-</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label text-light small mb-0 opacity-75">Fecha</label>
              <div id="ocr-fecha" class="fs-5 fw-semibold">-</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-light small mb-0 opacity-75">Teléfono Emisor</label>
              <div id="ocr-telefono" class="fs-5 fw-semibold">-</div>
            </div>
          </div>
        </div>
        <hr class="border-secondary">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-2">
              <label class="form-label text-light small mb-0 opacity-75">Estado Transacción</label>
              <div id="ocr-estado-tx" class="fw-semibold">-</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-2">
              <label class="form-label text-light small mb-0 opacity-75">Método / Confianza</label>
              <div><span id="ocr-metodo" class="badge bg-secondary">-</span> <span id="ocr-confianza" class="badge bg-secondary">-</span></div>
            </div>
          </div>
        </div>
        <!-- Alertas de validación -->
        <div id="ocr-alertas" class="mt-3" style="display:none;">
          <label class="form-label text-muted small mb-1">Alertas</label>
          <div id="ocr-alertas-list"></div>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<style>
    /* Main styles */
    body {
        background-color: #121212;
        color: #e0e0e0;
    }

    .navbar-admin {
        background-color: #00bf63;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        border-bottom: none;
    }
    
    /* Aumentar el espacio para los botones de acción */
    td:last-child {
        min-width: 190px;
    }
    
    /* Asegurar que toda la tabla sea visible */
    .table-responsive {
        overflow-x: auto;
    }
    @media (min-width: 1200px) {
    .container-xl-custom {
        max-width: 1357px; /* o lo que necesites */
    }
}

    /* Establecer anchos de columna para mejor visualización */
    #tabla-reservas th:nth-child(1) { width: 50px; }  /* ID */
    #tabla-reservas th:nth-child(2) { width: 300px; } /* Nombre */
    #tabla-reservas th:nth-child(3) { width: 100px; } /* Celular */
    #tabla-reservas th:nth-child(4) { width: 120px; } /* Fecha */
    #tabla-reservas th:nth-child(5) { width: 60px; }  /* Cartones */
    #tabla-reservas th:nth-child(6),
    #tabla-reservas td:nth-child(6) { 
        width: 90px; 
        max-width: 90px;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.75rem;
    } /* Series */
    #tabla-reservas th:nth-child(7) { width: 70px; }  /* Total */
    #tabla-reservas th:nth-child(8) { width: 150px; } /* Comprobante */
    #tabla-reservas th:nth-child(9) { width: 120px; } /* # Comp. */
    #tabla-reservas th:nth-child(10) { width: 100px; } /* Estado */
    #tabla-reservas th:nth-child(11) { width: 120px; } /* Acciones */
    
    /* Table styles */
    .table {
        margin-bottom: 0;
    }
    
    .table th {
        font-weight: 500;
        border-bottom-width: 1px;
    }
    
    /* Clean pagination styling */
    .pagination {
        margin-bottom: 0;
    }
    
    .page-link {
        background-color: #212529;
        border-color: #495057;
        color: #e0e0e0;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .page-link:hover {
        background-color: #343a40;
        border-color: #6c757d;
        color: #fff;
    }
    
    .page-item.active .page-link {
        background-color: #00bf63;
        border-color: #00bf63;
    }
    
    /* Highlighted rows */
    .duplicated-comprobante {
        background-color: rgba(220, 53, 69, 0.3) !important;
    }
    
    .duplicated-price {
        background-color: rgba(255, 193, 7, 0.3) !important;
    }
    
    /* Form controls in dark mode */
    .form-control:focus, .form-select:focus {
        border-color: #00bf63;
        box-shadow: 0 0 0 0.25rem rgba(0, 191, 99, 0.25);
    }
    
    /* Button hover effects */
    .btn-outline-light:hover {
        background-color: #00bf63;
        border-color: #00bf63;
    }
    
    /* Card custom styling */
    .card {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    #tabla-reservas th:nth-child(2),
#tabla-reservas td:nth-child(2) {
    width: 220px;
    max-width: 220px;
}

#tabla-reservas th:nth-child(3),
#tabla-reservas td:nth-child(3) {
    width: 140px;
    max-width: 140px;
}

#tabla-reservas th:nth-child(7),
#tabla-reservas td:nth-child(7) {
    width: 100px;
    max-width: 100px;
}

/* Ajustar inputs dentro de la tabla */
.table input.form-control-sm {
    width: 100%;
    min-width: 100px;
    max-width: 100%;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    background-color: #1e1e1e;
    color: #e0e0e0;
    border: 1px solid #343a40;
}
</style>

<script>
    $(document).ready(function () {

        // Ajustar ancho de la tabla cuando se redimensiona la ventana
        $(window).resize(function() {
            if ($.fn.dataTable.isDataTable('#tabla-reservas')) {
                $('#tabla-reservas').DataTable().columns.adjust();
            }
        });
        
      
        // Initialize DataTable with improved configuration
        const table = $('#tabla-reservas').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ordering: true,
            autoWidth: false,
            stateSave: true,
            responsive: false,
            language: {
                paginate: {
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>'
                },
                search: "",
                searchPlaceholder: "Buscar...",
                lengthMenu: "Mostrar _MENU_",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                zeroRecords: "No se encontraron registros coincidentes",
                processing: '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Cargando...</span></div>'
            },
            dom: 'rt',

            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                    className: 'btn btn-sm btn-outline-light',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 8, 9]
                    }
                }
            ]
        });
        
        console.log('Total de registros en DataTable:', table.rows().count());

    });

    document.querySelectorAll('.numero-comprobante').forEach(input => {
        input.addEventListener('change', function () {
            const reservaId = this.dataset.id;
            const numero = this.value;

            fetch("{{ route('reservas.guardar-comprobante') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    reserva_id: reservaId,
                    numero_comprobante: numero
                })
            }).then(res => res.json())
              .then(data => {
                if (data.success) {
                        this.classList.add('border-success');
                        setTimeout(() => this.classList.remove('border-success'), 1000);

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Comprobante actualizado',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }

              }).catch(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Error al actualizar',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

                  this.classList.add('border-danger');
                  setTimeout(() => this.classList.remove('border-danger'), 2000);
              });
        });
    });
    document.querySelectorAll('.cambiar-estado').forEach(btn => {
        btn.addEventListener('click', function () {
            const reservaId = this.dataset.id;
            const nuevoEstado = this.dataset.estado;

            fetch("{{ route('reservas.cambiar-estado') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    reserva_id: reservaId,
                    estado: nuevoEstado
                })
            }).then(res => res.json())
              .then(data => {
                  if (data.success) {
                      Swal.fire({
                          toast: true,
                          position: 'top-end',
                          icon: 'success',
                          title: 'Estado actualizado',
                          showConfirmButton: false,
                          timer: 2000
                      });
                      // Puedes actualizar visualmente el badge aquí o recargar la tabla si deseas
                      const badge = document.querySelector(`.estado-badge[data-id="${reservaId}"]`);

                    if (badge) {
                        // Limpiar clases previas
                        badge.classList.remove('bg-warning', 'bg-success', 'bg-danger', 'text-dark');

                        if (nuevoEstado === 'revision') {
                            badge.textContent = 'Revision';
                            badge.classList.add('bg-warning', 'text-dark');
                        } else if (nuevoEstado === 'aprobado') {
                            badge.textContent = 'Aprobado';
                            badge.classList.add('bg-success');
                        } else if (nuevoEstado === 'rechazado') {
                            badge.textContent = 'Rechazado';
                            badge.classList.add('bg-danger');
                        }
                    }

                  }
              }).catch(() => {
                  Swal.fire({
                      toast: true,
                      position: 'top-end',
                      icon: 'error',
                      title: 'Error al actualizar estado',
                      showConfirmButton: false,
                      timer: 2000
                  });
              });
        });
    });
    
    document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.actualizar-reserva').forEach(button => {
    button.addEventListener('click', () => {
        const id = button.dataset.id;
        const nombre = document.querySelector(`.campo-nombre[data-id="${id}"]`).value;
        const celular = document.querySelector(`.campo-celular[data-id="${id}"]`).value;
        const total = document.querySelector(`.campo-total[data-id="${id}"]`).value;
        fetch(`/admin/admin/reservas/${id}/actualizar`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ nombre, celular, total })
})

        .then(response => response.json())
        .then(data => {
            Swal.fire({
                icon: 'success',
                title: 'Reserva actualizada',
                toast: true,
                position: 'top-end',
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'Error al actualizar',
                toast: true,
                position: 'top-end',
                timer: 2000,
                showConfirmButton: false
            });
        });
    });
});
});
// Mostrar el modal y cargar el ID
document.addEventListener('click', function (e) {
    if (e.target.closest('.subir-comprobante')) {
        const btn = e.target.closest('.subir-comprobante');
        const id = btn.dataset.id;
        document.getElementById('comprobanteReservaId').value = id;
        document.getElementById('archivoComprobante').value = '';
        const modal = new bootstrap.Modal(document.getElementById('modalComprobante'));
        modal.show();
    }
});

// Manejar envío del formulario
document.getElementById('formComprobante').addEventListener('submit', function (e) {
    e.preventDefault();
    console.log("🔁 Enviando comprobante vía fetch...");

    const form = document.getElementById('formComprobante');
    const formData = new FormData(form);

    // Mostrar loading mientras sube
    Swal.fire({
        title: 'Subiendo comprobante...',
        text: 'Esto puede tardar unos segundos...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('/admin/comprobantes/' + formData.get('reserva_id'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData,
        credentials: 'same-origin'
    })
    .then(async res => {
        if (res.redirected) {
            Swal.close();
            console.warn("⚠️ Redirigido a:", res.url);
            Swal.fire({
                icon: 'warning',
                title: 'Sesión expirada',
                text: 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.',
            }).then(() => {
                window.location.href = res.url;
            });
            return;
        }

        const text = await res.text();

        try {
            const data = JSON.parse(text);

            Swal.close();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Comprobante actualizado correctamente',
                    toast: true,
                    position: 'top-end',
                    timer: 2000,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('modalComprobante')).hide();

                // Opción: recargar DataTable u otra parte si lo necesitas
                // $('#tablaReservas').DataTable().ajax.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: data.message || 'Ocurrió un error inesperado',
                });
            }
        } catch (e) {
            console.error("❌ La respuesta no es JSON válido. HTML recibido:");
            console.error(text);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error de sesión',
                text: 'Parece que tu sesión expiró. Por favor inicia sesión de nuevo.',
            });
        }
    })
    .catch(error => {
        console.error("❌ Error general:", error);
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error inesperado',
            text: error.message,
            toast: true,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false
        });
    });
});


document.querySelectorAll('.btn-eliminar-serie').forEach(btn => {
    btn.addEventListener('click', function () {
        const reservaId = this.dataset.id;
        const serie = this.dataset.serie;

        Swal.fire({
            title: '¿Eliminar serie?',
            text: `Serie: ${serie}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch("{{ route('reservas.eliminar-serie') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        reserva_id: reservaId,
                        serie: serie
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Serie eliminada',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: data.message || 'No se pudo eliminar',
                            toast: true,
                            position: 'top-end',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al eliminar serie',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
            }
        });
    });
});



// Funcionalidad para descargar cartones al hacer clic en el número de serie
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('serie-numero')) {
        e.preventDefault();
        e.stopPropagation();
        
        const serie = e.target.dataset.serie;
        const bingoId = e.target.dataset.bingoId;
        
        // Mostrar loading
        Swal.fire({
            title: 'Generando cartón...',
            text: `Serie: ${serie}`,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
// JAVASCRIPT SÚPER SIMPLE - Solo redirigir como enlace normal
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('serie-numero')) {
        e.preventDefault();
        e.stopPropagation();
        
        const serie = e.target.dataset.serie;
        const bingoId = e.target.dataset.bingoId;
        
        // Ir directo a la descarga sin complicaciones
        window.open(`/admin/bingos/${bingoId}/carton/${serie}/descargar`, '_blank');
    }
});
    }
});

// Prevenir que el hover del número interfiera con el botón X
document.addEventListener('mouseover', function(e) {
    if (e.target.classList.contains('btn-eliminar-serie')) {
        // Remover temporalmente el hover del número cuando se hace hover en X
        const serieSpan = e.target.parentElement.querySelector('.serie-numero');
        if (serieSpan) {
            serieSpan.style.pointerEvents = 'none';
        }
    }
});

document.addEventListener('mouseout', function(e) {
    if (e.target.classList.contains('btn-eliminar-serie')) {
        // Restaurar el hover del número
        const serieSpan = e.target.parentElement.querySelector('.serie-numero');
        if (serieSpan) {
            serieSpan.style.pointerEvents = 'auto';
        }
    }
});



// Funcionalidad para descargar cartones al hacer clic en el número de serie
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('serie-numero')) {
        e.preventDefault();
        e.stopPropagation();
        
        const serie = e.target.dataset.serie;
        // Obtener el reservaId del botón eliminar del mismo contenedor
        const contenedor = e.target.closest('.d-flex');
        const btnEliminar = contenedor.querySelector('.btn-eliminar-serie');
        const reservaId = btnEliminar.dataset.id;
        
        if (reservaId && serie) {
            // Mostrar loading
            Swal.fire({
                title: 'Generando cartón...',
                text: `Serie: ${serie}`,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirigir a la descarga
            window.open(`/cartones/descargar/${reservaId}/${serie}`, '_blank');
            
            // Cerrar el loading después de un momento
            setTimeout(() => {
                Swal.close();
            }, 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo obtener la información necesaria para la descarga',
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false
            });
        }
    }
});

// Modal OCR
document.addEventListener('click', function(e) {
    if (e.target.closest('.ver-ocr')) {
        const btn = e.target.closest('.ver-ocr');
        try {
            const ocrData = JSON.parse(btn.dataset.ocr);
            const status = btn.dataset.status;

            document.getElementById('ocr-banco').textContent = ocrData.banco ? ocrData.banco.toUpperCase() : 'No detectado';
            document.getElementById('ocr-monto').textContent = ocrData.monto ? '$' + Number(ocrData.monto).toLocaleString('es-CO') : 'No detectado';
            document.getElementById('ocr-referencia').textContent = ocrData.referencia || 'No detectada';
            document.getElementById('ocr-fecha').textContent = ocrData.fecha || 'No detectada';

            document.getElementById('ocr-telefono').textContent = ocrData.telefono_emisor || 'No detectado';

            // Estado transacción
            const estadoTx = document.getElementById('ocr-estado-tx');
            if (ocrData.estado_transaccion) {
                estadoTx.textContent = ocrData.estado_transaccion;
                const estado = ocrData.estado_transaccion.toLowerCase();
                estadoTx.className = 'fw-semibold ' + (estado === 'exitosa' ? 'text-success' : estado === 'fallida' ? 'text-danger' : 'text-warning');
            } else {
                estadoTx.textContent = 'No detectado';
                estadoTx.className = 'fw-semibold text-muted';
            }

            // Método y confianza
            document.getElementById('ocr-metodo').textContent = (ocrData.metodo || status || 'N/A').toUpperCase();
            document.getElementById('ocr-confianza').textContent = ocrData.confianza ? ocrData.confianza + '%' : '-';
            document.getElementById('ocr-confianza').className = 'badge ' + (ocrData.confianza >= 80 ? 'bg-success' : ocrData.confianza >= 50 ? 'bg-warning' : 'bg-danger');

            // Alertas
            const alertasContainer = document.getElementById('ocr-alertas');
            const alertasList = document.getElementById('ocr-alertas-list');
            alertasList.innerHTML = '';
            if (ocrData.validacion && ocrData.validacion.alertas && ocrData.validacion.alertas.length > 0) {
                alertasContainer.style.display = 'block';
                ocrData.validacion.alertas.forEach(alerta => {
                    const div = document.createElement('div');
                    div.className = 'alert alert-warning py-1 px-2 mb-1 small';
                    div.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + alerta;
                    alertasList.appendChild(div);
                });
            } else {
                alertasContainer.style.display = 'none';
            }

            const modal = new bootstrap.Modal(document.getElementById('modalOCR'));
            modal.show();
        } catch (err) {
            console.error('Error parseando OCR data:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron leer los datos OCR', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        }
    }
});

// Mejorar el hover visual para el número de serie
document.addEventListener('DOMContentLoaded', function() {
    // Agregar estilos CSS dinámicamente para el hover
    const style = document.createElement('style');
    style.textContent = `
        .serie-numero {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 3px;
            padding: 2px 4px;
        }
        .serie-numero:hover {
            background-color: rgba(255, 255, 255, 0.2) !important;
            transform: scale(1.05);
            text-decoration: underline;
        }
        .d-flex:hover .serie-numero {
            color: #fff !important;
        }
    `;
    document.head.appendChild(style);
});

// Prevenir que el hover del número interfiera con el botón X
document.addEventListener('mouseover', function(e) {
    if (e.target.classList.contains('btn-eliminar-serie')) {
        // Remover temporalmente el hover del número cuando se hace hover en X
        const serieSpan = e.target.parentElement.querySelector('.serie-numero');
        if (serieSpan) {
            serieSpan.style.pointerEvents = 'none';
            serieSpan.style.opacity = '0.7';
        }
    }
});

document.addEventListener('mouseout', function(e) {
    if (e.target.classList.contains('btn-eliminar-serie')) {
        // Restaurar el hover del número
        const serieSpan = e.target.parentElement.querySelector('.serie-numero');
        if (serieSpan) {
            serieSpan.style.pointerEvents = 'auto';
            serieSpan.style.opacity = '1';
        }
    }
});
</script>
@endsection