@extends('layouts.admin')

@section('content')
<div class="container-fluid p-0">
    <!-- Encabezado Panel verde -->
    <div style="background-color: #00bf63;" class="text-white p-3 mb-4">
        <h1 class="display-4 text-center m-0">Panel</h1>
    </div>

    <!-- Formulario para crear bingo -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-md-6">
                <form action="{{ route('bingos.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label text-white fw-bold">Nombre del bingo</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white fw-bold">Fecha del bingo</label>
                        <input type="date" class="form-control" name="fecha" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white fw-bold">Precio</label>
                        <!-- Usamos un input para formatear el precio al momento de crearlo -->
                        <input type="text" class="form-control" name="precio" id="precio" required>
                    </div>

                    <button type="submit" class="btn btn-success px-4" style="background-color: #00bf63; margin-top: 10px;">
                        Crear
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Línea separadora -->
<hr class="bg-secondary mb-4">

<!-- Tabla de bingos -->
<div class="container mb-4">
    <div class="row">
        <div class="col-12">
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Nombre del bingo</th>
                        <th>Fecha</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bingos as $bingo)
                    <tr>
                        <td>{{ $bingo->nombre }}</td>
                        <td>{{ \Carbon\Carbon::parse($bingo->fecha)->format('d/m/Y') }}</td>
                        <td>
                            <!-- Visualización del precio y botón de editar -->
                            <span id="price-display-{{ $bingo->id }}">
                                ${{ number_format($bingo->precio, 0, ',', '.') }} Pesos
                            </span>
                            <button type="button" class="btn btn-sm btn-link text-white" onclick="editPrice({{ $bingo->id }})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zM10.5 3.207l1.293 1.293-8 8H2.5v-1.293l8-8z" />
                                </svg>
                            </button>

                            <!-- Formulario inline para editar el precio -->
                            <form action="{{ route('bingos.update', $bingo->id) }}" method="POST" id="price-form-{{ $bingo->id }}" style="display: none; margin-top: 5px;">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="precio" value="{{ $bingo->precio }}" class="form-control form-control-sm" style="width: 120px; display: inline-block;">
                                <button type="submit" class="btn btn-sm btn-success">Guardar</button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEdit({{ $bingo->id }})">Cancelar</button>
                            </form>
                        </td>
                        <td>{{ ucfirst($bingo->estado) }}</td>
                        <td>
                            @if(strtolower($bingo->estado) == 'abierto')
                            <!-- Si el bingo está abierto, mostrar botón para cerrarlo -->
                            <form action="{{ route('bingos.cerrar', $bingo->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm" style="background-color: #00bf63; color: white; font-weight: bold; border: 2px solid white;">
                                    Cerrar Bingo
                                </button>
                            </form>
                            @else
                            @if(!$bingo->reabierto)
                            <!-- Si está cerrado y aún no se reabrió, mostrar botón para abrirlo -->
                            <form action="{{ route('bingos.abrir', $bingo->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm" style="background-color: #00bf63; color: white; font-weight: bold; border: 2px solid white;">
                                    Abrir Bingo
                                </button>
                            </form>
                            @else
                            <!-- Si ya se reabrió una vez, mostrar un mensaje -->
                            <span class="badge bg-secondary">Cerrado (ya se cerró 1 vez)</span>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script para formatear el campo de precio en el formulario de creación -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const precioInput = document.getElementById('precio');

        precioInput.addEventListener('input', function(e) {
            // Elimina todo lo que no sea número
            let valor = this.value.replace(/\D/g, '');

            // Formatea el número con separador de miles
            if (valor.length > 0) {
                valor = parseInt(valor, 10).toLocaleString('es-CL');
            }

            // Actualiza el valor del campo
            this.value = valor;
        });

        // Para el envío del formulario, limpia el formato antes de enviar
        precioInput.form.addEventListener('submit', function() {
            precioInput.value = precioInput.value.replace(/\D/g, '');
        });
    });
</script>

<!-- Script para manejar la edición inline del precio -->
<script>
    function editPrice(id) {
        document.getElementById('price-display-' + id).style.display = 'none';
        document.getElementById('price-form-' + id).style.display = 'inline-block';
    }

    function cancelEdit(id) {
        document.getElementById('price-form-' + id).style.display = 'none';
        document.getElementById('price-display-' + id).style.display = 'inline';
    }
</script>
@endsection