<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartones Rechazados - Bingo: {{ $bingo->nombre }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container-fluid {
            max-width: 1400px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            Cartones Rechazados - Bingo: {{ $bingo->nombre }}
                            <a href="{{ route('bingos.reservas.rechazados.excel', $bingo->id) }}" class="btn btn-sm btn-light float-end">
                                <i class="bi bi-download"></i> Descargar Excel
                            </a>
                            <a href="{{ url()->previous() }}" class="btn btn-sm btn-light float-end me-2">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Pestañas para navegar entre las dos secciones -->
                        <ul class="nav nav-tabs" id="rechazadosTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="reservas-tab" data-bs-toggle="tab" 
                                        data-bs-target="#reservas" type="button" role="tab" 
                                        aria-controls="reservas" aria-selected="true">
                                    Reservas Rechazadas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="cartones-tab" data-bs-toggle="tab" 
                                        data-bs-target="#cartones" type="button" role="tab" 
                                        aria-controls="cartones" aria-selected="false">
                                    Cartones Individuales
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Contenido de las pestañas -->
                        <div class="tab-content mt-3" id="rechazadosTabsContent">
                            <!-- Pestaña 1: Reservas Rechazadas -->
                            <div class="tab-pane fade show active" id="reservas" role="tabpanel" aria-labelledby="reservas-tab">
                                @if(count($reservasRechazadas) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nombre</th>
                                                    <th>Celular</th>
                                                    <th>Cantidad</th>
                                                    <th>Total</th>
                                                    <th>Cartones</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($reservasRechazadas as $item)
                                                    <tr>
                                                        <td>{{ $item['reserva']->id }}</td>
                                                        <td>{{ $item['reserva']->nombre }}</td>
                                                        <td>{{ $item['reserva']->celular }}</td>
                                                        <td>{{ $item['reserva']->cantidad }}</td>
                                                        <td>${{ number_format($item['reserva']->total, 0, ',', '.') }}</td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-info" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#cartonesModal{{ $item['reserva']->id }}">
                                                                Ver {{ count($item['cartones']) }} cartones
                                                            </button>
                                                        </td>
                                                        <td>{{ date('d/m/Y H:i', strtotime($item['reserva']->created_at)) }}</td>
                                                    </tr>
                                                    
                                                    <!-- Modal para ver los cartones de esta reserva -->
                                                    <div class="modal fade" id="cartonesModal{{ $item['reserva']->id }}" tabindex="-1" 
                                                         aria-labelledby="cartonesModalLabel{{ $item['reserva']->id }}" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="cartonesModalLabel{{ $item['reserva']->id }}">
                                                                        Cartones de la Reserva #{{ $item['reserva']->id }}
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Cartón</th>
                                                                                    <th>Series</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($item['cartones'] as $carton)
                                                                                    <tr>
                                                                                        <td>{{ $carton }}</td>
                                                                                        <td>
                                                                                            @if(isset($item['cartonesSeries'][$carton]))
                                                                                                @if(is_array($item['cartonesSeries'][$carton]))
                                                                                                    {{ implode(', ', $item['cartonesSeries'][$carton]) }}
                                                                                                @else
                                                                                                    {{ $item['cartonesSeries'][$carton] }}
                                                                                                @endif
                                                                                            @else
                                                                                                No disponible
                                                                                            @endif
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        No hay reservas rechazadas para este bingo.
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Pestaña 2: Cartones Individuales -->
                            <div class="tab-pane fade" id="cartones" role="tabpanel" aria-labelledby="cartones-tab">
                                @if(count($cartonesRechazados) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Cartón</th>
                                                    <th>Reserva ID</th>
                                                    <th>Nombre</th>
                                                    <th>Series</th>
                                                    <th>Fecha Rechazo</th>
                                                    <th>Motivo</th>
                                                    <th>Usuario</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($cartonesRechazados as $item)
                                                    <tr>
                                                        <td>{{ $item['carton']->id }}</td>
                                                        <td>{{ $item['carton']->serie_rechazada }}</td>
                                                        <td>
                                                            @if($item['reserva'])
                                                                {{ $item['reserva']->id }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($item['reserva'])
                                                                {{ $item['reserva']->nombre }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($item['series']))
                                                                @if(is_array($item['series']))
                                                                    {{ implode(', ', $item['series']) }}
                                                                @else
                                                                    {{ $item['series'] }}
                                                                @endif
                                                            @else
                                                                No disponible
                                                            @endif
                                                        </td>
                                                        <td>{{ date('d/m/Y H:i', strtotime($item['carton']->fecha_rechazo)) }}</td>
                                                        <td>{{ $item['carton']->motivo ?? 'No especificado' }}</td>
                                                        <td>{{ $item['carton']->usuario ?? 'Sistema' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        No hay cartones individuales rechazados para este bingo.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>