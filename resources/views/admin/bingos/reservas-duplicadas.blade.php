@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <h4 class="text-white mb-4">
        <i class="bi bi-exclamation-triangle"></i> Comprobantes Potencialmente Repetidos – {{ $bingo->nombre }}
    </h4>

    @php $grupoIndex = 1; @endphp

    @forelse ($paginador as $hash => $reservas)
    @php
            $rechazados = collect($reservas)->filter(fn($r) => $r->estado === 'rechazado' && $r->eliminado == 1)->count();
            $total = count($reservas);
            $restantes = $total - $rechazados;

            $bordeClase = 'border-secondary';

            if ($restantes === 1) {
                $bordeClase = 'border-success';
            } elseif ($rechazados === $total) {
                $bordeClase = 'border-danger';
            }

            $numeroGrupo = $restantes > 0 ? $grupoIndex++ : null;
        @endphp

        <div class="card bg-dark {{ $bordeClase }} mb-5">
            <div class="card-header bg-black text-white d-flex justify-content-between align-items-center">
                <div>
                    <strong>Grupo de coincidencia visual:</strong>
                    <code class="text-warning">Hash: {{ $hash }}</code>
                </div>
                @if ($numeroGrupo)
                    <span class="badge bg-primary fs-6 px-3 py-2">Grupo #{{ $numeroGrupo }}</span>
                @endif
            </div>

            <div class="card-body">
                <div class="row g-3">
                    @foreach ($reservas as $reserva)
                        @php
                            $metadatos = json_decode($reserva->comprobante_metadata, true);
                            $info = $metadatos[0] ?? [];
                            $comprobantes = is_array($reserva->comprobante) ? $reserva->comprobante : json_decode($reserva->comprobante, true);
                            $ruta = str_replace('\/', '/', $comprobantes[0] ?? '');
                            $esRechazado = $reserva->estado === 'rechazado' && $reserva->eliminado == 1;
                        @endphp

                        <div class="col-md-4">
                            <div class="card h-100 bg-secondary text-white {{ $esRechazado ? 'border-danger border-3 position-relative' : 'border-light' }} shadow-sm">

                                {{-- Sello de rechazado --}}
                                @if ($esRechazado)
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.6); z-index: 1;">
                                        <span class="text-danger fw-bold fs-3 bg-white px-4 py-2 rounded">❌ RECHAZADO</span>
                                    </div>
                                @endif

                                <div class="card-header small" style="z-index: 2;">
                                    <strong>Reserva ID: {{ $reserva->id }}</strong><br>
                                    {{ $reserva->nombre }}<br>
                                    Teléfono: {{ $reserva->celular }}<br>
                                    Similitud visual: <span class="text-warning">{{ $info['similaridad'] ?? '?' }}%</span>
                                </div>

                                <div class="card-body text-center" style="z-index: 2;">
                                    <a href="{{ asset( $ruta) }}" target="_blank">
                                        <img src="{{ asset( $ruta) }}" class="img-fluid rounded mb-2" style="max-height: 220px;">
                                    </a>

                                    <div class="text-white-50 small">
                                        Cartones: <strong>{{ $reserva->cantidad }}</strong><br>
                                        Series:
                                        @foreach($reserva->series as $serie)
                                            <div>{{ $serie }}</div>
                                        @endforeach
                                    </div>

                                    {{-- Botón de rechazar si no está rechazado --}}
                                    @unless($esRechazado)
                                        <form method="POST" action="{{ route('reservas.rechazar', $reserva->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-danger mt-2">
                                                <i class="bi bi-x-lg"></i> Rechazar
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">No se encontraron comprobantes potencialmente repetidos.</div>
    @endforelse
    <div class="mt-4 d-flex justify-content-center">
    <div class="pagination-sm">
        {{ $paginador->links() }}
    </div>
</div>

    <div class="text-end mt-4">
        <a href="{{ route('bingos.reservas.rapidas', $bingo->id) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>
@endsection
