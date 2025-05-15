<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechazados - Bingo: {{ $bingo->nombre }}</title>
    
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
                            Rechazados - Bingo: {{ $bingo->nombre }}
                            <a href="{{ route('bingos.reservas.rechazados.excel', $bingo->id) }}" class="btn btn-sm btn-light float-end">
                                <i class="bi bi-download"></i> Descargar Excel
                            </a>
                            <a href="{{ url()->previous() }}" class="btn btn-sm btn-light float-end me-2">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Celular</th>
                                        <th>Cartón</th>
                                        <th>Series</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalRegistros = 0;
                                    @endphp
                                    
                                    <!-- Cartones de reservas rechazadas -->
                                    @foreach($reservasRechazadas as $reserva)
                                        @foreach($reserva['cartones'] as $carton)
                                            @php
                                                $totalRegistros++;
                                            @endphp
                                            <tr>
                                                <td>{{ $reserva['reserva']->nombre }}</td>
                                                <td>{{ $reserva['reserva']->celular }}</td>
                                                <td>{{ $carton }}</td>
                                                <td>
                                                    @if(isset($reserva['cartonesSeries'][$carton]))
                                                        @if(is_array($reserva['cartonesSeries'][$carton]))
                                                            {{ implode(', ', $reserva['cartonesSeries'][$carton]) }}
                                                        @else
                                                            {{ $reserva['cartonesSeries'][$carton] }}
                                                        @endif
                                                    @else
                                                        No disponible
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                    
                                    <!-- Cartones rechazados individuales -->
                                    @foreach($cartonesRechazados as $item)
                                        @php
                                            $totalRegistros++;
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($item['reserva'])
                                                    {{ $item['reserva']->nombre }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($item['reserva'])
                                                    {{ $item['reserva']->celular }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $item['carton']->serie_rechazada }}</td>
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
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($totalRegistros == 0)
                            <div class="alert alert-info">
                                No hay cartones rechazados para este bingo.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>