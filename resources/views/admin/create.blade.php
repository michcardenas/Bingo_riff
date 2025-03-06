@extends('layouts.admin')


@section('content')
<div class="my-4">
    <h1 class="mb-4">Crear Bingo</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('bingos.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Bingo</label>
            <input type="text" name="nombre" id="nombre" 
                   class="form-control @error('nombre') is-invalid @enderror"
                   value="{{ old('nombre') }}" required>
            @error('nombre')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" name="fecha" id="fecha" 
                   class="form-control @error('fecha') is-invalid @enderror"
                   value="{{ old('fecha') }}" required>
            @error('fecha')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="precio" class="form-label">Precio del Cartón</label>
            <input type="number" name="precio" id="precio" step="0.01"
                   class="form-control @error('precio') is-invalid @enderror"
                   value="{{ old('precio') }}" required>
            @error('precio')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-success">Crear Bingo</button>
    </form>
</div>
@endsection
