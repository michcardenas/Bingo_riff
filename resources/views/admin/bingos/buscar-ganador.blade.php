@extends('layouts.admin')

@section('content')
<div class="container py-5 text-center">

    <h3 class="mb-4 text-orange"><i class="bi bi-search"></i> Buscar número de serie</h3>

    <form method="GET" action="{{ route('bingos.reservas.buscar-ganador', $bingoId) }}" class="d-flex justify-content-center mb-5">
        <input type="text" name="serie" class="form-control form-control-lg w-25 rounded-pill text-center" placeholder="Ingrese la serie..." value="{{ $serieBuscada ?? '' }}" required>
        <button type="submit" class="btn btn-lg btn-primary ms-3 rounded-pill">Buscar</button>
    </form>

    @if ($infoSerie)
        <div class="card shadow-lg mx-auto mb-5" style="max-width: 800px;">
            <div class="card-header bg-dark text-white">
                <strong><i class="bi bi-card-text"></i> Información de la Serie</strong>
            </div>
            <div class="card-body bg-light">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary"><i class="bi bi-hash"></i> Serie Encontrada</h5>
                        <div class="alert alert-info">
                            <strong>Serie buscada:</strong> {{ $infoSerie['serie_buscada'] }}<br>
                            <strong>Cartón asociado:</strong> #{{ $infoSerie['carton_numero'] }}
                        </div>
                        
                        @if(count($infoSerie['todas_las_series']) > 0)
                            <h6 class="text-secondary">Todas las series en este cartón:</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach($infoSerie['todas_las_series'] as $serie)
                                    <span class="badge bg-secondary fs-6">{{ $serie }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    
                    <div class="col-md-6">
                        @if($infoSerie['propietario'])
                            <h5 class="text-success"><i class="bi bi-person-check"></i> Propietario</h5>
                            <div class="alert alert-success">
                                <strong>Nombre:</strong> {{ $infoSerie['propietario']['nombre'] }}<br>
                                <strong>Teléfono:</strong> {{ substr($infoSerie['propietario']['celular'], 0, 4) . '****' }}
                                
                                @if($infoSerie['propietario']['es_ganador'])
                                    <br><span class="badge bg-warning mt-2"><i class="bi bi-star-fill"></i> GANADOR - {{ $infoSerie['propietario']['premio'] }}</span>
                                @endif
                            </div>
                            
                            @if(!$infoSerie['propietario']['es_ganador'] && $datos)
                                <form method="POST" action="{{ route('bingos.marcar-ganador', $datos->id) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <input type="text" name="premio" class="form-control rounded-pill text-center" placeholder="Premio..." required>
                                    </div>
                                    <button type="submit" class="btn btn-success rounded-pill">
                                        <i class="bi bi-trophy"></i> Marcar como Ganador
                                    </button>
                                </form>
                            @endif
                        @else
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <strong>Serie encontrada pero sin propietario en este bingo</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($infoCarton)
            <div class="card shadow-lg mx-auto mb-5" style="max-width: 800px;">
                <div class="card-header bg-primary text-white">
                    <strong><i class="bi bi-grid-3x3"></i> Cartón #{{ $infoCarton['numero'] }}</strong>
                </div>
                <div class="card-body bg-white">
                    @if(isset($infoCarton['mensaje']))
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle"></i> {{ $infoCarton['mensaje'] }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-lg text-center" style="font-size: 1.2em;">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="py-3" style="width: 20%;">B</th>
                                        <th class="py-3" style="width: 20%;">I</th>
                                        <th class="py-3" style="width: 20%;">N</th>
                                        <th class="py-3" style="width: 20%;">G</th>
                                        <th class="py-3" style="width: 20%;">O</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($fila = 0; $fila < 5; $fila++)
                                        <tr>
                                            <td class="py-3 fw-bold">
                                                {{ $infoCarton['numeros_b'][$fila] ?? '-' }}
                                            </td>
                                            <td class="py-3 fw-bold">
                                                {{ $infoCarton['numeros_i'][$fila] ?? '-' }}
                                            </td>
                                            <td class="py-3 fw-bold">
                                                @if($fila == 2)
                                                    <span class="text-danger">★</span>
                                                @else
                                                    {{ $infoCarton['numeros_n'][$fila] ?? '-' }}
                                                @endif
                                            </td>
                                            <td class="py-3 fw-bold">
                                                {{ $infoCarton['numeros_g'][$fila] ?? '-' }}
                                            </td>
                                            <td class="py-3 fw-bold">
                                                {{ $infoCarton['numeros_o'][$fila] ?? '-' }}
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> 
                                La estrella (★) representa el espacio libre del cartón
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    @elseif($serieBuscada)
        <div class="alert alert-danger w-50 mx-auto">
            <i class="bi bi-x-circle"></i> No se encontró la serie "{{ $serieBuscada }}" en el sistema.
        </div>
    @endif

    <h4 class="mt-5 text-secondary"><i class="bi bi-trophy"></i> Ganadores del Bingo</h4>
    @if(count($ganadores) > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover mt-3 bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Celular</th>
                        <th>Premio</th>
                        <th>Fecha</th>
                        <th>Cartón Ganador</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ganadores as $ganadorData)
                        <tr>
                            <td>{{ $ganadorData['ganador']->nombre }}</td>
                            <td>{{ $ganadorData['ganador']->celular }}</td>
                            <td><span class="badge bg-success">{{ $ganadorData['ganador']->premio }}</span></td>
                            <td>{{ $ganadorData['ganador']->fecha_ganador ? $ganadorData['ganador']->fecha_ganador->format('d/m/Y H:i') : '-' }}</td>
                            <td>
                                @if($ganadorData['carton_ganador'])
                                    <div class="accordion" id="accordion-{{ $ganadorData['ganador']->id }}">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $ganadorData['ganador']->id }}" aria-expanded="false">
                                                    <strong>Cartón #{{ $ganadorData['carton_ganador']['numero'] }}</strong>
                                                    <span class="ms-2 badge bg-warning">Serie: {{ $ganadorData['carton_ganador']['serie_ganadora'] }}</span>
                                                </button>
                                            </h2>
                                            <div id="collapse-{{ $ganadorData['ganador']->id }}" class="accordion-collapse collapse" data-bs-parent="#accordion-{{ $ganadorData['ganador']->id }}">
                                                <div class="accordion-body p-3">
                                                    
                                                    <!-- Series del cartón ganador -->
                                                    @if(count($ganadorData['carton_ganador']['todas_las_series']) > 0)
                                                        <div class="mb-3">
                                                            <strong class="text-primary">Todas las series del cartón:</strong>
                                                            <div class="d-flex flex-wrap gap-1 mt-2">
                                                                @foreach($ganadorData['carton_ganador']['todas_las_series'] as $serie)
                                                                    <span class="badge {{ $serie == $ganadorData['carton_ganador']['serie_ganadora'] ? 'bg-success' : 'bg-secondary' }}">
                                                                        {{ $serie }}
                                                                        @if($serie == $ganadorData['carton_ganador']['serie_ganadora'])
                                                                            <i class="bi bi-trophy ms-1"></i>
                                                                        @endif
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                    
                                                    <!-- Cartón BINGO -->
                                                    @if($ganadorData['carton_ganador']['numeros_detalle'])
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-sm text-center" style="font-size: 0.85em;">
                                                                <thead class="table-secondary">
                                                                    <tr>
                                                                        <th style="width: 20%;">B</th>
                                                                        <th style="width: 20%;">I</th>
                                                                        <th style="width: 20%;">N</th>
                                                                        <th style="width: 20%;">G</th>
                                                                        <th style="width: 20%;">O</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @for($fila = 0; $fila < 5; $fila++)
                                                                        <tr>
                                                                            <td class="py-1 fw-bold">
                                                                                {{ $ganadorData['carton_ganador']['numeros_detalle']['numeros_b'][$fila] ?? '-' }}
                                                                            </td>
                                                                            <td class="py-1 fw-bold">
                                                                                {{ $ganadorData['carton_ganador']['numeros_detalle']['numeros_i'][$fila] ?? '-' }}
                                                                            </td>
                                                                            <td class="py-1 fw-bold">
                                                                                @if($fila == 2)
                                                                                    <span class="text-danger">★</span>
                                                                                @else
                                                                                    {{ $ganadorData['carton_ganador']['numeros_detalle']['numeros_n'][$fila] ?? '-' }}
                                                                                @endif
                                                                            </td>
                                                                            <td class="py-1 fw-bold">
                                                                                {{ $ganadorData['carton_ganador']['numeros_detalle']['numeros_g'][$fila] ?? '-' }}
                                                                            </td>
                                                                            <td class="py-1 fw-bold">
                                                                                {{ $ganadorData['carton_ganador']['numeros_detalle']['numeros_o'][$fila] ?? '-' }}
                                                                            </td>
                                                                        </tr>
                                                                    @endfor
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @else
                                                    
                                                    @endif
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">Cartón ganador no identificado</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Aún no hay ganadores en este bingo.
        </div>
    @endif

</div>
@endsection