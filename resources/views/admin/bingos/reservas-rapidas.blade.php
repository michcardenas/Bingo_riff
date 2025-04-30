@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <!-- Header with improved styling -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-white">
            <i class="bi bi-grid-3x3"></i> Reservas Bingo #{{ $bingoId }}
        </h4>
        <div>
            <button class="btn btn-sm btn-outline-light">
                <i class="bi bi-plus-circle"></i> Nueva Reserva
            </button>
        </div>
    </div>

    <!-- Card container for the table -->
    <div class="card bg-dark border-0 shadow">
        <div class="card-header bg-dark border-bottom border-secondary py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-light">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="search-input" class="form-control form-control-sm bg-dark border-secondary text-light" placeholder="Buscar...">
                    </div>
                </div>
                <div class="col-md-6 d-flex justify-content-end gap-2">
                    <select id="estado-filter" class="form-select form-select-sm bg-dark border-secondary text-light w-auto">
                        <option selected>Estado</option>
                        <option>Pendiente</option>
                        <option>Aprobado</option>
                        <option>Rechazado</option>
                    </select>
                    <button class="btn btn-sm btn-outline-light">
                        <i class="bi bi-file-earmark-excel"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <table id="tabla-reservas" class="table table-dark table-hover table-bordered border-secondary mb-0 align-middle small">
                <thead>
                    <tr class="bg-black text-white">
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Celular</th>
                        <th>Fecha</th>
                        <th>Cartones</th>
                        <th>Series</th>
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
                            <td>{{ $reserva->id }}</td>
                            <td>{{ $reserva->nombre }}</td>
                            <td>{{ $reserva->celular }}</td>
                            <td>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y H:i') }}</td>
                            <td>{{ $reserva->cartones }}</td>
                            @php $series = json_decode($reserva->series, true); @endphp
                            <td>{{ is_array($series) ? implode(', ', $series) : $reserva->series }}</td>
                            <td>${{ number_format($reserva->total, 0, ',', '.') }}</td>
                            <td>
                                @if($reserva->comprobante)
                                    <a href="{{ asset(json_decode($reserva->comprobante)[0]) }}" target="_blank" class="btn btn-sm btn-dark">
                                        <i class="bi bi-download"></i> Descargar comprobante
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td><input type="text" value="{{ $reserva->numero_comprobante }}" class="form-control form-control-sm bg-dark border-secondary text-light" style="min-width: 120px;"></td>
                            <td>
                                @if($reserva->estado == 'pendiente')
                                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">Pendiente</span>
                                @elseif($reserva->estado == 'aprobado')
                                    <span class="badge bg-success fs-6 px-3 py-2">Aprobado</span>
                                @elseif($reserva->estado == 'rechazado')
                                    <span class="badge bg-danger fs-6 px-3 py-2">Rechazado</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-info px-3" title="Editar">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-outline-success px-3" title="Aprobar">
                                        <i class="bi bi-check-lg"></i> Aprobar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger px-3" title="Rechazar">
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
                    Mostrando {{ $reservas->firstItem() }} - {{ $reservas->lastItem() }} de {{ $reservas->total() }} reservas
                </div>
                <div>
                    {{ $reservas->links('pagination::bootstrap-5') }}
                </div>
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
        min-width: 300px;
    }
    
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
        // Configuración para buscar en todos los datos, no solo en los paginados
        $.extend($.fn.dataTable.defaults, {
            serverSide: true,
            processing: true,
            ajax: {
                url: '{{ route("admin.reservas.search", $bingoId) }}', // Debes crear esta ruta
                type: 'POST',
                data: function(data) {
                    data._token = '{{ csrf_token() }}';
                    // Añadir filtros personalizados
                    data.estado = $('#estado-filter').val() !== 'Estado' ? $('#estado-filter').val() : '';
                }
            }
        });
        
        // Initialize DataTable with improved configuration
        const table = $('#tabla-reservas').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ordering: true,
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
            dom: '<"top"lf>rt<"bottom"ip>',
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
        
        // Connect the custom search input (búsqueda en todos los datos)
        $('#search-input').on('keyup', function() {
            table.search(this.value).draw();
        });
        
        // Handle state filter
        $('#estado-filter').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
@endsection