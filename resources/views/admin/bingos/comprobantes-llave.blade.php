@extends('layouts.admin')

@section('content')
<div class="container container-xl-custom my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-white mb-0">
            <i class="bi bi-key-fill text-info"></i> Comprobantes Llave Bre-B - <strong>{{ $bingo->nombre }}</strong>
        </h4>
        <a href="{{ route('bingos.reservas.rapidas', $bingoId) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    {{-- Filtro --}}
    <div class="card bg-dark border-0 shadow-sm mb-3 rounded-3" style="border-left: 3px solid #0dcaf0 !important;">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('bingos.reservas.comprobantes-llave', $bingoId) }}">
                <div class="input-group input-group-sm">
                    <input type="text" name="search"
                           class="form-control bg-dark border-info text-light"
                           placeholder="Buscar por nombre, celular, código operación o pagador"
                           value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="{{ route('bingos.reservas.comprobantes-llave', $bingoId) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Total: <strong>{{ $paginador->total() }}</strong> comprobante(s) de Llave Bre-B procesado(s)
    </div>

    {{-- Tabla --}}
    <div class="card bg-dark border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered border-secondary mb-0 align-middle small">
                <thead>
                    <tr class="bg-black text-white">
                        <th>ID</th>
                        <th>Cliente del bingo</th>
                        <th>Celular</th>
                        <th>Pagador (correo BBVA)</th>
                        <th>Monto</th>
                        <th>Fecha y hora</th>
                        <th>Código operación</th>
                        <th>Estado</th>
                        <th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginador as $reserva)
                        @php
                            $ocr = is_array($reserva->ocr_data) ? $reserva->ocr_data : (json_decode($reserva->ocr_data, true) ?? []);
                            $nombrePagador = $ocr['correo_nombre_pagador'] ?? '—';
                            $monto = $ocr['correo_monto'] ?? $ocr['monto'] ?? $reserva->total;
                            $fechaCorreo = $ocr['correo_fecha'] ?? $ocr['fecha'] ?? null;
                            $codigoOp = $ocr['correo_codigo_operacion'] ?? $ocr['referencia'] ?? '—';
                            $comprobantes = is_array($reserva->comprobante) ? $reserva->comprobante : (json_decode($reserva->comprobante, true) ?? []);
                        @endphp
                        <tr>
                            <td><strong>{{ $reserva->id }}</strong></td>
                            <td>
                                <strong class="text-info">{{ $reserva->nombre }}</strong>
                            </td>
                            <td>{{ $reserva->celular }}</td>
                            <td>
                                @if($nombrePagador !== '—')
                                    <span class="text-warning">{{ $nombrePagador }}</span>
                                @else
                                    <span class="text-muted">No detectado</span>
                                @endif
                            </td>
                            <td class="text-success fw-semibold">
                                ${{ number_format($monto, 0, ',', '.') }}
                            </td>
                            <td>
                                @if($fechaCorreo)
                                    <small class="text-light">{{ $fechaCorreo }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="font-monospace small text-warning" style="word-break: break-all; max-width: 200px;">
                                {{ $codigoOp }}
                            </td>
                            <td>
                                <span class="badge {{ $reserva->estado == 'aprobado' ? 'bg-success' : ($reserva->estado == 'rechazado' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ ucfirst($reserva->estado) }}
                                </span>
                            </td>
                            <td>
                                @foreach($comprobantes as $idx => $comp)
                                    @php
                                        $ruta = str_replace(['\\', '//'], '/', $comp);
                                        if (!str_starts_with($ruta, 'http')) {
                                            $ruta = asset($ruta);
                                        }
                                    @endphp
                                    <a href="{{ $ruta }}" target="_blank" class="btn btn-sm btn-outline-info mb-1 d-block">
                                        <i class="bi bi-image"></i> Ver {{ count($comprobantes) > 1 ? ($idx + 1) : '' }}
                                    </a>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox"></i> No se encontraron comprobantes de Llave Bre-B
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginador->hasPages())
            <div class="card-footer bg-dark border-top border-secondary py-3">
                <div class="d-flex justify-content-center">
                    {{ $paginador->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
