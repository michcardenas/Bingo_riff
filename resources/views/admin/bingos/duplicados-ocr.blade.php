@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <h4 class="text-white mb-4">
        <i class="bi bi-exclamation-triangle"></i> Comprobantes con Referencia OCR Duplicada - {{ $bingo->nombre }}
    </h4>

    @php $grupoIndex = 1; @endphp

    @forelse ($paginador as $referencia => $reservas)
        @php
            $rechazados = collect($reservas)->filter(fn($r) => $r->estado === 'rechazado')->count();
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
                    <strong>Referencia duplicada:</strong>
                    <code class="text-warning fs-6">{{ $referencia }}</code>
                    <span class="badge bg-danger ms-2">{{ $total }} reservas</span>
                </div>
                @if ($numeroGrupo)
                    <span class="badge bg-primary fs-6 px-3 py-2">Grupo #{{ $numeroGrupo }}</span>
                @endif
            </div>

            <div class="card-body">
                <div class="row g-3">
                    @foreach ($reservas as $reserva)
                        @php
                            $ocr = is_string($reserva->ocr_data) ? json_decode($reserva->ocr_data, true) : (is_array($reserva->ocr_data) ? $reserva->ocr_data : []);
                            $comprobantes = is_array($reserva->comprobante) ? $reserva->comprobante : json_decode($reserva->comprobante, true);
                            $ruta = str_replace('\/', '/', $comprobantes[0] ?? '');
                            $esRechazado = $reserva->estado === 'rechazado';
                        @endphp

                        <div class="col-md-4">
                            <div class="card h-100 bg-secondary text-white {{ $esRechazado ? 'border-danger border-3 position-relative' : 'border-light' }} shadow-sm">

                                {{-- Sello de rechazado --}}
                                @if ($esRechazado)
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.6); z-index: 1;">
                                        <span class="text-danger fw-bold fs-3 bg-white px-4 py-2 rounded">RECHAZADO</span>
                                    </div>
                                @endif

                                <div class="card-header small" style="z-index: 2;">
                                    <strong>Reserva ID: {{ $reserva->id }}</strong><br>
                                    {{ $reserva->nombre }}<br>
                                    Celular: {{ $reserva->celular }}
                                </div>

                                <div class="card-body" style="z-index: 2;">
                                    {{-- Imagen del comprobante --}}
                                    @if($ruta)
                                    <div class="text-center mb-3">
                                        <a href="{{ asset($ruta) }}" target="_blank">
                                            <img src="{{ asset($ruta) }}" class="img-fluid rounded" style="max-height: 200px;">
                                        </a>
                                    </div>
                                    @endif

                                    {{-- Datos OCR --}}
                                    <div class="small">
                                        <div class="d-flex justify-content-between border-bottom border-dark py-1">
                                            <span class="text-light opacity-75">Banco:</span>
                                            <span class="badge bg-info">{{ strtoupper($ocr['banco'] ?? '-') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-bottom border-dark py-1">
                                            <span class="text-light opacity-75">Monto:</span>
                                            <span class="text-success fw-semibold">${{ isset($ocr['monto']) ? number_format($ocr['monto'], 0, ',', '.') : '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-bottom border-dark py-1">
                                            <span class="text-light opacity-75">Referencia:</span>
                                            <span class="text-warning">{{ $ocr['referencia'] ?? '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-bottom border-dark py-1">
                                            <span class="text-light opacity-75">Fecha:</span>
                                            <span>{{ $ocr['fecha'] ?? '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-bottom border-dark py-1">
                                            <span class="text-light opacity-75">Tel. Emisor:</span>
                                            <span>{{ $ocr['telefono_emisor'] ?? '-' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span class="text-light opacity-75">Cartones:</span>
                                            <span>{{ $reserva->cantidad }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span class="text-light opacity-75">Total:</span>
                                            <span>${{ number_format($reserva->total, 0, ',', '.') }}</span>
                                        </div>
                                    </div>

                                    {{-- Estado --}}
                                    <div class="text-center mt-2">
                                        <span class="badge {{ $reserva->estado == 'aprobado' ? 'bg-success' : ($reserva->estado == 'rechazado' ? 'bg-danger' : 'bg-warning text-dark') }} fs-6 px-3 py-1">
                                            {{ ucfirst($reserva->estado) }}
                                        </span>
                                    </div>

                                    {{-- Boton rechazar --}}
                                    @unless($esRechazado)
                                        <form method="POST" action="{{ route('reservas.rechazar', $reserva->id) }}" class="text-center mt-2">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
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
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> No se encontraron comprobantes con referencia OCR duplicada.
        </div>
    @endforelse

    <div class="mt-4 d-flex justify-content-center">
        <div class="pagination-sm">
            {{ $paginador->links() }}
        </div>
    </div>

    <div class="text-end mt-4">
        <a href="{{ route('bingos.reservas.rapidas', $bingoId) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>
@endsection
