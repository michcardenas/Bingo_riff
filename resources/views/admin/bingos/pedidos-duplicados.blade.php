@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <h4 class="text-white mb-4">
        <i class="bi bi-files"></i> Pedidos Duplicados – {{ $bingo->nombre }}
    </h4>

    @forelse ($reservas as $numero => $grupo)
        @php
            $rechazados = $grupo->filter(fn($r) => $r->estado === 'rechazado' && $r->eliminado == 1)->count();
            $aprobados = $grupo->filter(fn($r) => $r->estado === 'aprobado' && $r->eliminado == 0)->count();
            $pendientes = $grupo->count() - ($rechazados + $aprobados);

            $bordeClase = 'border-secondary';

            if ($pendientes === 1) {
                $bordeClase = 'border-success';
            } elseif ($rechazados === $grupo->count()) {
                $bordeClase = 'border-danger';
            }
        @endphp

        <div class="card bg-dark text-white {{ $bordeClase }} mb-4">
            <div class="card-header bg-black d-flex justify-content-between align-items-center">
                <strong>
                    Comprobante duplicado: <span class="text-warning">#{{ $numero }}</span>
                </strong>
                <span class="badge bg-light text-dark">
                    Total: {{ $grupo->count() }} | Rechazados: {{ $rechazados }} | Aprobados: {{ $aprobados }}
                </span>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    @foreach ($grupo as $reserva)
                        @php
                            // Usar directamente comprobante (ya es array por cast)
                            $ruta = str_replace('\/', '/', $reserva->comprobante[0] ?? '');
                            $esRechazado = $reserva->estado === 'rechazado' && $reserva->eliminado == 1;
                            $esAprobado = $reserva->estado === 'aprobado' && $reserva->eliminado == 0;
                        @endphp

                        <div class="col-md-4">
                            <div class="card h-100 bg-secondary text-white {{ $esRechazado ? 'border-danger border-3 position-relative' : 'border-light' }} shadow-sm">

                                {{-- Sello de rechazado --}}
                                @if ($esRechazado)
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.6); z-index: 1;">
                                        <span class="text-danger fw-bold fs-3 bg-white px-4 py-2 rounded">❌ RECHAZADO</span>
                                    </div>
                                @endif

                                {{-- Sello de aprobado --}}
                                @if ($esAprobado)
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,255,0,0.1); z-index: 1;">
                                        <span class="text-success fw-bold fs-3 bg-white px-4 py-2 rounded">✅ APROBADO</span>
                                    </div>
                                @endif

                                <div class="card-header small" style="z-index: 2;">
                                    <strong>Reserva ID: {{ $reserva->id }}</strong><br>
                                    {{ $reserva->nombre }}<br>
                                    Teléfono: {{ $reserva->celular }}
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

                                    <strong>
                                        Comprobante duplicado: <span class="text-warning">#{{ $reserva->numero_comprobante }}</span>
                                    </strong>

                                    {{-- Botones de acción --}}
                                    <div class="mt-3 d-flex justify-content-center flex-column align-items-center gap-2">
                                        @unless($esRechazado)
                                            <form method="POST" action="{{ route('reservas.rechazar', $reserva->id) }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-outline-danger px-4 py-2 fw-bold shadow-sm border-2">
                                                    <i class="bi bi-x-lg fs-5 me-2"></i> Rechazar
                                                </button>
                                            </form>
                                        @endunless

                                        @unless($esAprobado)
                                            <form method="POST" action="{{ route('reservas.aprobar', $reserva->id) }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-outline-success px-4 py-2 fw-bold shadow-sm border-2">
                                                    <i class="bi bi-check-lg fs-5 me-2"></i> Aprobar
                                                </button>
                                            </form>
                                        @endunless
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">No se encontraron pedidos con número de comprobante duplicado.</div>
    @endforelse

    <div class="text-end mt-4">
        <a href="{{ route('bingos.reservas.rapidas', $bingo->id) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>
@endsection
