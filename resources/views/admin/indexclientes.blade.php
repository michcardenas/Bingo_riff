@extends('layouts.admin')
@section('content')
<div class="container-fluid p-0">
    <!-- Encabezado Panel verde -->
    <div style="background-color: #00bf63;" class="text-white p-3 mb-4">
        <h1 class="display-4 text-center m-0">Gestión de Reservas</h1>
    </div>

    <!-- Barra de opciones (botones) -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-auto">
                <button id="btnOriginal" class="btn btn-sm btn-secondary me-2">
                    Reservas Originales
                </button>
                <button id="btnComprobanteDuplicado" class="btn btn-sm btn-secondary me-2">
                    # Comprobante Duplicado
                </button>
                <button id="btnPedidoDuplicado" class="btn btn-sm btn-secondary me-2">
                    Pedido Duplicado
                </button>
                <button id="btnCartonesEliminados" class="btn btn-sm btn-secondary">
                    Cartones Eliminados
                </button>
            </div>
        </div>
    </div>

    <!-- Contenedor para la tabla (se actualizará dinámicamente) -->
    <div class="container" id="tableContent">
        {{-- Incluimos inicialmente la tabla original --}}
        @include('admin.table', ['reservas' => $reservas])
    </div>
</div>

<script>
    // Función para cargar contenido en el contenedor 'tableContent'
    function loadTableContent(url) {
        fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('tableContent').innerHTML = html;
            })
            .catch(error => console.error('Error cargando contenido:', error));
    }


    // Asignar eventos a los botones para actualizar la tabla sin repetir el header
    document.getElementById('btnOriginal').addEventListener('click', function() {
        loadTableContent("{{ route('reservas.index') }}");
    });

    document.getElementById('btnComprobanteDuplicado').addEventListener('click', function() {
        loadTableContent("{{ route('admin.comprobantesDuplicados') }}");
    });

    document.getElementById('btnPedidoDuplicado').addEventListener('click', function() {
        loadTableContent("{{ route('admin.pedidosDuplicados') }}");
    });

    document.getElementById('btnCartonesEliminados').addEventListener('click', function() {
        loadTableContent("{{ route('admin.cartonesEliminados') }}");
    });
</script>
@endsection