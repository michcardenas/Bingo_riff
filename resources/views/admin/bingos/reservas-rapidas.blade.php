@extends('layouts.admin')
@section('content')
<div class="container">
    <h4 class="mb-4">Reservas Bingo #{{ $bingoId }}</h4>

    <table id="tabla-reservas" class="table table-hover table-bordered table-striped text-center align-middle small shadow-sm rounded">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Celular</th>
            <th>Fecha</th>
            <th>Cartones</th>
            <th>Series</th>
            <th>Total</th>
            <th>Comprobante</th>
            <th># Comp.</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($reservas as $reserva)
            <tr>
                <td>{{ $reserva->id }}</td>
                <td>{{ $reserva->nombre }}</td>
                <td>{{ $reserva->celular }}</td>
                <td>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y H:i') }}</td>
                <td>{{ $reserva->cartones }}</td>
                @php $series = json_decode($reserva->series, true); @endphp
                <td>{{ is_array($series) ? implode(', ', $series) : $reserva->series }}</td>
                <td>${{ number_format($reserva->total, 0, ',', '.') }}</td>
                <td>
                    @if($reserva->comprobante)
                        <a href="{{ asset(json_decode($reserva->comprobante)[0]) }}" target="_blank" class="btn btn-sm btn-outline-light">Ver</a>
                    @else
                        —
                    @endif
                </td>
                <td><input type="text" value="{{ $reserva->numero_comprobante }}" class="form-control form-control-sm"></td>
                <td><span class="badge bg-warning text-dark">{{ ucfirst($reserva->estado) }}</span></td>
                <td>
                    <div class="d-grid gap-1">
                        <button class="btn btn-sm btn-outline-warning">Editar</button>
                        <button class="btn btn-sm btn-outline-success">Aprobar</button>
                        <button class="btn btn-sm btn-outline-danger">Rechazar</button>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>



    {{ $reservas->links() }}
</div>
@endsection
