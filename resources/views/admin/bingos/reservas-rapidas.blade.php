@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <!-- Header with improved styling -->
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-white">
    <i class="bi bi-grid-3x3"></i> Reservas del Bingo :  <strong>{{ $bingo->nombre }}</strong>
</h4>


        
    </div>

 <!-- Card container for the table -->
<div class="card bg-dark border-0 shadow-sm mb-4 rounded-3">
    <div class="card-header bg-dark border-bottom border-secondary py-3">
        <form action="{{ route('bingos.reservas.filtro', $bingoId) }}" method="GET" id="filtro-form">
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

    <a href="{{ route('bingos.reservas.create', $bingoId) }}" class="btn btn-sm btn-outline-success">
        <i class="bi bi-plus-circle"></i> Crear Nuevo
    </a>

    <a href="{{ route('bingos.reservas.duplicadas', $bingoId) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-exclamation-triangle"></i> Comprobantes Duplicados
    </a>

    <a href="{{ route('bingos.reservas.pedidos-duplicados', $bingoId) }}" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-files"></i> Pedido Duplicado
    </a>

    <a href="{{ route('bingos.reservas.buscar-ganador', $bingoId) }}" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-search"></i> Buscar Ganador
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

                            <td>{{ $reserva->nombre }}</td>
                            <td>{{ $reserva->celular }}</td>
                            <td>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y H:i') }}</td>
                            <td>{{ $reserva->cartones }}</td>
                                                        @php 
                                // Intenta decodificar el JSON
                                $series = json_decode($reserva->series, true);

                                // Si no es un arreglo, forzamos a tratarlo como texto
                                if (!is_array($series)) {
                                    // Por si viniera como string malformado, lo separamos por comillas o espacios
                                    $series = preg_split('/[",\s]+/', $reserva->series);
                                }

                                // Limpiar cada entrada y quitar vacíos
                                $seriesClean = array_filter(array_map(function($s) {
                                    return trim(preg_replace('/[^0-9]/', '', $s));
                                }, $series));
                            @endphp

                            <td title="{{ implode(', ', $seriesClean) }}">
                                @foreach ($seriesClean as $serie)
                                    <div>{{ $serie }}</div>
                                @endforeach
                            </td>

                            <td>${{ number_format($reserva->total, 0, ',', '.') }}</td>
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
                                        </td>

                            <td>
                            <div class="d-grid gap-2">
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
        min-height: 300px;
    }
    
    /* Establecer anchos de columna para mejor visualización */
    #tabla-reservas th:nth-child(1) { width: 50px; }  /* ID */
    #tabla-reservas th:nth-child(2) { width: 150px; } /* Nombre */
    #tabla-reservas th:nth-child(3) { width: 100px; } /* Celular */
    #tabla-reservas th:nth-child(4) { width: 120px; } /* Fecha */
    #tabla-reservas th:nth-child(5) { width: 60px; }  /* Cartones */
    #tabla-reservas th:nth-child(6),
    #tabla-reservas td:nth-child(6) { 
        width: 60px; 
        max-width: 65px;
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
</script>
@endsection