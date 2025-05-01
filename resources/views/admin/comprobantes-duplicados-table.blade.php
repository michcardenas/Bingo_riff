<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-exclamation-triangle-fill me-2"></i> Comprobantes potencialmente duplicados</h5>
            <div>
                <span class="badge bg-secondary">{{ count($duplicados) }} grupos encontrados</span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-dark table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Celular</th>
                    <th>Cantidad</th>
                    <th>Series</th>
                    <th>Bingo</th>
                    <th>Total</th>
                    <th>Comprobante</th>
                    <th># Comprobante</th>
                    <th>Similitud</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($duplicados as $grupo)
                    <!-- Determinar el tipo de duplicado -->
                    @php
                        $porNumero = isset($grupo[0]->numero_comprobante) && !empty($grupo[0]->numero_comprobante) &&
                                     collect($grupo)->filter(function($item) use ($grupo) {
                                         return $item->numero_comprobante === $grupo[0]->numero_comprobante;
                                     })->count() > 1;
                    @endphp
                    
                    <!-- Encabezado del grupo -->
                    <tr class="bg-dark">
                        <td colspan="12" class="bg-dark border-warning border-top border-bottom py-2">
                            <span class="badge bg-warning text-dark">Grupo de duplicados #{{ $loop->iteration }}</span>
                            @if($porNumero)
                                <span class="badge bg-info ms-2">Por número de comprobante: {{ $grupo[0]->numero_comprobante }}</span>
                            @else
                                <span class="badge bg-info ms-2">Por metadatos: similitud de imagen</span>
                            @endif
                        </td>
                    </tr>
                    
                    @foreach($grupo as $reserva)
                    <tr>
                        <td>{{ $reserva->id }}</td>
                        <td>{{ $reserva->nombre }}</td>
                        <td>{{ $reserva->celular }}</td>
                        <td>{{ $reserva->cantidad }}</td>
                        <td>
                            @php
                                $seriesData = $reserva->series;
                                
                                // Verificar si es una cadena JSON y convertirla a array si es necesario
                                if (is_string($seriesData) && json_decode($seriesData) !== null) {
                                    $seriesData = json_decode($seriesData, true);
                                }
                            @endphp
                            
                            @if(is_array($seriesData))
                                {{ implode(', ', $seriesData) }}
                            @else
                                {{ $seriesData }}
                            @endif
                        </td>
                        <td>
                            @if($reserva->bingo)
                                {{ $reserva->bingo->nombre }}
                            @else
                                <span class="text-warning">Sin asignar</span>
                            @endif
                        </td>
                        <td>${{ number_format($reserva->total, 0, ',', '.') }} Pesos</td>
                        <td>
                            @if($reserva->comprobante)
                                @php
                                // Decodifica el JSON; si ya es array, lo usa tal cual
                                $comprobantes = is_array($reserva->comprobante) ? $reserva->comprobante : json_decode($reserva->comprobante, true);
                                @endphp

                                @if(is_array($comprobantes) && count($comprobantes) > 0)
                                    @foreach($comprobantes as $index => $comprobante)
                                    <a href="{{ asset( $comprobante) }}" target="_blank" class="btn btn-sm btn-light mb-1">
                                        Ver comprobante {{ $index + 1 }}
                                    </a>
                                    <a href="{{ asset( $comprobante) }}" target="_blank" class="d-block mb-1">
                                        <img src="{{ asset( $comprobante) }}" 
                                            alt="Miniatura" 
                                            class="img-thumbnail" 
                                            style="max-height: 40px; max-width: 60px">
                                    </a>
                                    @endforeach
                                @else
                                    <span class="text-danger">Sin comprobante</span>
                                @endif
                            @else
                                <span class="text-danger">Sin comprobante</span>
                            @endif
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm bg-dark text-white border-light" 
                                value="{{ $reserva->numero_comprobante ?? '' }}" readonly>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar {{ $reserva->similaridad > 90 ? 'bg-danger' : 'bg-warning text-dark' }}" 
                                    role="progressbar" 
                                    style="width: {{ $reserva->similaridad ?? 0 }}%;" 
                                    aria-valuenow="{{ $reserva->similaridad ?? 0 }}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">{{ $reserva->similaridad ?? 0 }}%</div>
                            </div>
                        </td>
                        <td>
                            @if($reserva->estado == 'revision')
                                <span class="badge bg-warning text-dark">Disponible</span>
                            @elseif($reserva->estado == 'aprobado')
                                <span class="badge bg-success">Aprobado</span>
                            @elseif($reserva->estado == 'rechazado')
                                <span class="badge bg-danger">Rechazado</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($reserva->estado) }}</span>
                            @endif
                        </td>
                        <td>
                            <!-- Botón para ver metadatos del comprobante -->
                            <button type="button" class="btn btn-sm btn-info mb-1 w-100" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#metadatosModal{{ $reserva->id }}">
                                <i class="bi bi-info-circle"></i> Ver metadatos
                            </button>
                            
                            @if($reserva->estado == 'revision')
                            <form action="{{ route('reservas.aprobar', $reserva->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success mb-1 w-100">
                                    <i class="bi bi-check2"></i> Aprobar
                                </button>
                            </form>
                            
                            <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-danger w-100">
                                    <i class="bi bi-x-circle"></i> Rechazar
                                </button>
                            </form>
                            @elseif($reserva->estado == 'aprobado')
                            <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-danger w-100">
                                    <i class="bi bi-x-circle"></i> Rechazar
                                </button>
                            </form>
                            @elseif($reserva->estado == 'rechazado')
                            <form action="{{ route('reservas.aprobar', $reserva->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-check2"></i> Aprobar
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    
                    <!-- Separador entre grupos -->
                    @if(!$loop->last)
                    <tr>
                        <td colspan="12" class="bg-dark border-0" style="height: 10px;"></td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="12" class="text-center">No se encontraron comprobantes duplicados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>



<!-- Modales para los metadatos de cada comprobante -->
@foreach($duplicados as $grupo)
    @foreach($grupo as $reserva)

    <div class="modal fade" id="metadatosModal{{ $reserva->id }}" tabindex="-1" aria-labelledby="metadatosModalLabel{{ $reserva->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="metadatosModalLabel{{ $reserva->id }}">
                        Metadatos del comprobante - Reserva #{{ $reserva->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($reserva->comprobante_metadata)
                        @php
                            $metadatos = json_decode($reserva->comprobante_metadata, true);
                        @endphp
                        
                        @if(is_array($metadatos) && count($metadatos) > 0)
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Propiedad</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($metadatos[0]) && is_array($metadatos[0]))
                                            <!-- Caso de múltiples archivos -->
                                            @foreach($metadatos as $index => $metadatoArchivo)
                                                <tr class="bg-secondary">
                                                    <td colspan="2" class="fw-bold">Archivo #{{ $index + 1 }}</td>
                                                </tr>
                                                
                                                @if(is_array($metadatoArchivo))
                                                    @foreach($metadatoArchivo as $clave => $valor)
                                                    <tr>
                                                        <td>{{ $clave }}</td>
                                                        <td>
                                                            @if(is_array($valor))
                                                                <pre>{{ json_encode($valor, JSON_PRETTY_PRINT) }}</pre>
                                                            @else
                                                                {{ $valor }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="2">Sin metadatos detallados disponibles</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @else
                                            <!-- Caso de un solo archivo -->
                                            @foreach($metadatos as $clave => $valor)
                                            <tr>
                                                <td>{{ $clave }}</td>
                                                <td>
                                                    @if(is_array($valor))
                                                        <pre>{{ json_encode($valor, JSON_PRETTY_PRINT) }}</pre>
                                                    @else
                                                        {{ $valor }}
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                No hay metadatos detallados disponibles para este comprobante.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-danger">
                            No se encontraron metadatos para este comprobante.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endforeach