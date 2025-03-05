<table class="table table-dark table-striped align-middle">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Celular</th>
            <th># Cartones</th>
            <th>Series</th>
            <th>Bingo</th>
            <th>Total</th>
            <th>Comprobante</th>
            <th># Comprobante</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reservas as $reserva)
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
                        $comprobantes = is_array($reserva->comprobante) ? $reserva->comprobante : json_decode($reserva->comprobante, true);
                    @endphp
                    @if(is_array($comprobantes) && count($comprobantes) > 0)
                        @foreach($comprobantes as $index => $comprobante)
                            <a href="{{ asset('storage/' . $comprobante) }}" target="_blank" class="btn btn-sm btn-light">
                                Ver comprobante {{ $index + 1 }}
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
                @if($reserva->estado == 'revision')
                    <input type="text" class="form-control form-control-sm bg-dark text-white border-light comprobante-input" value="{{ $reserva->numero_comprobante ?? '' }}">
                @else
                    <input type="text" class="form-control form-control-sm bg-dark text-white border-light" value="{{ $reserva->numero_comprobante ?? '' }}" readonly>
                @endif
            </td>
            <td>
                @if($reserva->estado == 'revision')
                    <span class="badge bg-warning text-dark">Revisión</span>
                @elseif($reserva->estado == 'aprobado')
                    <span class="badge bg-success">Aprobado</span>
                @elseif($reserva->estado == 'rechazado')
                    <span class="badge bg-danger">Rechazado</span>
                @else
                    <span class="badge bg-secondary">{{ ucfirst($reserva->estado) }}</span>
                @endif
            </td>
            <td>
                @if($reserva->estado == 'revision')
                    <form action="{{ route('reservas.aprobar', $reserva->id) }}" method="POST" class="d-inline aprobar-form">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-success me-1">Aprobar</button>
                    </form>
                    <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-danger">Rechazar</button>
                    </form>
                @elseif($reserva->estado == 'aprobado')
                    <span class="text-white">Aprobado</span>
                @elseif($reserva->estado == 'rechazado')
                    <span class="text-white">Rechazado</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="text-center">No hay reservas registradas.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<script>
document.querySelectorAll('.aprobar-form').forEach(form => {
    form.addEventListener('submit', function(event) {
        // Encuentra la fila que contiene el formulario
        const row = form.closest('tr');
        // Busca el input editable del número de comprobante en la misma fila
        const input = row.querySelector('.comprobante-input');
        if(input) {
            // Crea un campo oculto para enviar el valor
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'numero_comprobante';
            hiddenInput.value = input.value;
            form.appendChild(hiddenInput);
        }
    });
});
</script>