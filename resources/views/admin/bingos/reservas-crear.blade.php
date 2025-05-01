@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <h4 class="text-white mb-4">
        <i class="bi bi-person-plus"></i> Crear Nuevo Participante en <strong>{{ $bingo->nombre }}</strong>
    </h4>

    <form action="{{ route('bingos.reservas.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="bingo_id" value="{{ $bingoId }}">
        <input type="hidden" name="desde_admin" value="1">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-white">Nombre Completo</label>
                <input type="text" name="nombre" class="form-control form-control-sm bg-dark text-white border-secondary" required>
            </div>
            <div class="col-md-6">
                <label class="form-label text-white">Número de Celular</label>
                <input type="text" name="celular" class="form-control form-control-sm bg-dark text-white border-secondary" required>
            </div>
            <div class="col-md-6">
                <label class="form-label text-white">Cantidad de Cartones</label>
                <input type="number" name="cartones" value="1" min="1" class="form-control form-control-sm bg-dark text-white border-secondary" required>
            </div>
            <div class="col-md-6">
                <label class="form-label text-white">Comprobante de Pago</label>
                <input type="file" name="comprobante[]" multiple accept="image/*" class="form-control form-control-sm bg-dark text-white border-secondary" required>
            </div>
            <div class="col-12 form-check mt-2">
                <input class="form-check-input" type="checkbox" name="auto_approve" value="1" id="autoApprove">
                <label class="form-check-label text-white" for="autoApprove">
                    Aprobar automáticamente
                </label>
            </div>
        </div>

        <div class="mt-4 text-end">
            <a href="{{ route('bingos.reservas.rapidas', $bingoId) }}" class="btn btn-sm btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-sm btn-success">Guardar Participante</button>
        </div>
    </form>
</div>
@endsection
