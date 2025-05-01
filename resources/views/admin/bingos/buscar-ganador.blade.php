@extends('layouts.admin')

@section('content')
<div class="container py-5 text-center">

    <h3 class="mb-4 text-orange"><i class="bi bi-search"></i> Buscar número de serie</h3>

    <form method="GET" action="{{ route('bingos.reservas.buscar-ganador', $bingoId) }}" class="d-flex justify-content-center mb-5">
        <input type="text" name="serie" class="form-control form-control-lg w-25 rounded-pill text-center" placeholder="Ingrese la serie..." value="{{ $serieBuscada ?? '' }}" required>
        <button type="submit" class="btn btn-lg btn-primary ms-3 rounded-pill">Buscar</button>
    </form>

    @if ($datos)
        <div class="card shadow-lg mx-auto mb-5" style="max-width: 500px;">
            <div class="card-header bg-dark text-white">
                <strong>Resultado</strong>
            </div>
            <div class="card-body bg-black text-white">
                <table class="table table-dark table-bordered mb-4">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $datos->nombre }}</td>
                            <td>{{ substr($datos->celular, 0, 4) . '****' }}</td>
                        </tr>
                    </tbody>
                </table>

                @if ($datos->ganador)
                    <div class="alert alert-success">
                        <i class="bi bi-star-fill"></i> <strong>GANADOR</strong> - Premio: {{ $datos->premio }}
                    </div>
                @else
                    <form method="POST" action="{{ route('bingos.marcar-ganador', $datos->id) }}">
                        @csrf
                        <div class="mb-3">
                            <input type="text" name="premio" class="form-control form-control-lg rounded-pill text-center" placeholder="Premio..." required>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg rounded-pill">Ganador</button>
                    </form>
                @endif
            </div>
        </div>
    @elseif($serieBuscada)
        <div class="alert alert-danger w-50 mx-auto">
            <i class="bi bi-x-circle"></i> No se encontró la serie en las reservas.
        </div>
    @endif

    <h4 class="mt-5 text-secondary">Ganadores del Bingo</h4>
    <table class="table table-striped table-hover mt-3 bg-white">
        <thead class="table-dark">
            <tr>
                <th>Nombre</th>
                <th>Celular</th>
                <th>Premio</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ganadores as $ganador)
                <tr>
                    <td>{{ $ganador->nombre }}</td>
                    <td>{{ $ganador->celular }}</td>
                    <td>{{ $ganador->premio }}</td>
                    <td>{{ $ganador->fecha_ganador ? $ganador->fecha_ganador->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection
