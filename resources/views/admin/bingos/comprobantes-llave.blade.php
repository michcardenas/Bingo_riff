@extends('layouts.admin')

@section('content')
<div class="container container-xl-custom my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="text-white mb-0">
            <i class="bi bi-key-fill text-info"></i>
            @if($general ?? false)
                Comprobantes Llave Bre-B (General)
            @else
                Comprobantes Llave Bre-B - <strong>{{ $bingo->nombre }}</strong>
            @endif
        </h4>
        <div class="d-flex gap-2">
            @if($general ?? false)
                {{-- desde el general no hay un bingo de regreso --}}
            @else
                <a href="{{ route('admin.comprobantes-llave-general') }}" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-globe"></i> Ver todos los pagos
                </a>
                <a href="{{ route('bingos.reservas.rapidas', $bingoId) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            @endif
        </div>
    </div>

    {{-- Filtros avanzados --}}
    <div class="card bg-dark border-0 shadow-sm mb-3 rounded-3" style="border-left: 3px solid #0dcaf0 !important;">
        <div class="card-body py-3">
            <form method="GET" action="{{ ($general ?? false) ? route('admin.comprobantes-llave-general') : route('bingos.reservas.comprobantes-llave', $bingoId) }}">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="text-light small mb-1">Búsqueda general</label>
                        <input type="text" name="search" class="form-control form-control-sm bg-dark border-info text-light"
                               placeholder="Buscar por cualquier campo" value="{{ $filtros['search'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="text-light small mb-1">Estado</label>
                        <select name="estado" class="form-select form-select-sm bg-dark border-info text-light">
                            <option value="">Todos</option>
                            <option value="aprobado" {{ ($filtros['estado'] ?? '') == 'aprobado' ? 'selected' : '' }}>Aprobado</option>
                            <option value="no_match" {{ ($filtros['estado'] ?? '') == 'no_match' ? 'selected' : '' }}>Sin match</option>
                            <option value="error" {{ ($filtros['estado'] ?? '') == 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                    </div>
                    @if($general ?? false)
                    <div class="col-md-3">
                        <label class="text-light small mb-1">Bingo</label>
                        <select name="bingo_id" class="form-select form-select-sm bg-dark border-info text-light">
                            <option value="">Todos los bingos</option>
                            @foreach($bingosDisponibles ?? [] as $b)
                                <option value="{{ $b->id }}" {{ ($filtros['bingo_id'] ?? '') == $b->id ? 'selected' : '' }}>
                                    {{ $b->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="text-light small mb-1">Cliente del bingo</label>
                        <input type="text" name="cliente" class="form-control form-control-sm bg-dark border-info text-light"
                               placeholder="Nombre del cliente" value="{{ $filtros['cliente'] ?? '' }}">
                    </div>
                </div>

                <div class="row g-2 mt-1">
                    <div class="col-md-3">
                        <label class="text-light small mb-1">Pagador (correo)</label>
                        <input type="text" name="pagador" class="form-control form-control-sm bg-dark border-info text-light"
                               placeholder="Nombre del pagador" value="{{ $filtros['pagador'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="text-light small mb-1">Código de operación</label>
                        <input type="text" name="codigo" class="form-control form-control-sm bg-dark border-info text-light"
                               placeholder="Código" value="{{ $filtros['codigo'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="text-light small mb-1">Monto mín</label>
                        <input type="number" step="0.01" name="monto_min" class="form-control form-control-sm bg-dark border-info text-light"
                               value="{{ $filtros['monto_min'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="text-light small mb-1">Monto máx</label>
                        <input type="number" step="0.01" name="monto_max" class="form-control form-control-sm bg-dark border-info text-light"
                               value="{{ $filtros['monto_max'] ?? '' }}">
                    </div>
                </div>

                <div class="row g-2 mt-1">
                    <div class="col-md-3">
                        <label class="text-light small mb-1">Fecha desde</label>
                        <input type="datetime-local" name="desde" class="form-control form-control-sm bg-dark border-info text-light"
                               value="{{ $filtros['desde'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="text-light small mb-1">Fecha hasta</label>
                        <input type="datetime-local" name="hasta" class="form-control form-control-sm bg-dark border-info text-light"
                               value="{{ $filtros['hasta'] ?? '' }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-info btn-sm">
                            <i class="bi bi-search"></i> Aplicar filtros
                        </button>
                        <a href="{{ ($general ?? false) ? route('admin.comprobantes-llave-general') : route('bingos.reservas.comprobantes-llave', $bingoId) }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Total: <strong>{{ $paginador->total() }}</strong> registro(s)
    </div>

    <div class="card bg-dark border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-dark table-hover table-bordered border-secondary mb-0 align-middle small">
                <thead>
                    <tr class="bg-black text-white">
                        <th>ID</th>
                        @if($general ?? false)<th>Bingo</th>@endif
                        <th>Cliente</th>
                        <th>Celular</th>
                        <th>Pagador (correo)</th>
                        <th>Monto</th>
                        <th>Fecha correo</th>
                        <th>Código operación</th>
                        <th>Estado</th>
                        <th>Match</th>
                        <th>Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginador as $pago)
                        <tr id="reserva-{{ $pago->reserva_id }}">
                            <td><strong>{{ $pago->id }}</strong></td>
                            @if($general ?? false)
                                <td>
                                    @if($pago->bingo)
                                        <span class="badge bg-secondary">{{ $pago->bingo->nombre }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                @if($pago->reserva)
                                    <a href="{{ route('bingos.reservas.rapidas', $pago->bingo_id) }}" class="text-info text-decoration-none">
                                        {{ $pago->reserva->nombre }}
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">Sin asignar</span>
                                @endif
                            </td>
                            <td>{{ $pago->reserva->celular ?? '—' }}</td>
                            <td>
                                @if($pago->nombre_pagador)
                                    <span class="text-warning">{{ $pago->nombre_pagador }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-success fw-semibold">
                                ${{ number_format($pago->monto, 0, ',', '.') }}
                            </td>
                            <td>
                                @if($pago->fecha_correo)
                                    <small class="text-light">{{ $pago->fecha_correo->format('Y-m-d H:i:s') }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="font-monospace small text-warning" style="word-break: break-all; max-width: 200px;">
                                {{ $pago->codigo_operacion ?? '—' }}
                            </td>
                            <td>
                                @php
                                    $colorEstado = match($pago->estado) {
                                        'aprobado' => 'bg-success',
                                        'no_match' => 'bg-warning text-dark',
                                        'error' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $colorEstado }}">{{ ucfirst($pago->estado) }}</span>
                                @if($pago->mensaje)
                                    <div class="mt-1 small text-light opacity-75" style="font-size: 0.75rem; line-height: 1.2;">
                                        {{ $pago->mensaje }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($pago->metodo_match)
                                    <span class="badge bg-dark border border-info text-info">{{ $pago->metodo_match }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($pago->reserva)
                                    @php
                                        $comprobantes = is_array($pago->reserva->comprobante) ? $pago->reserva->comprobante : (json_decode($pago->reserva->comprobante, true) ?? []);
                                    @endphp
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
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($general ?? false) ? 11 : 10 }}" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox"></i> No se encontraron registros
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#reserva-')) {
            const fila = document.querySelector(hash);
            if (fila) {
                fila.style.transition = 'background-color 0.5s';
                fila.style.backgroundColor = 'rgba(255, 193, 7, 0.3)';
                fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => {
                    fila.style.backgroundColor = 'rgba(255, 193, 7, 0.15)';
                }, 2000);
            }
        }
    });
</script>
@endsection
