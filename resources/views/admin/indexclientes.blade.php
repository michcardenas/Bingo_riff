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
                <button id="btnOriginal" class="btn btn-sm btn-primary me-2">
                    Todas las Reservas
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

    <!-- Sección de filtros (persistente) -->
    <div class="container mb-4" id="filtrosContainer">
        <div class="card bg-dark text-white">
            <div class="card-header bg-secondary">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" id="nombre" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-3">
                        <label for="celular" class="form-label">Celular:</label>
                        <input type="text" id="celular" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-3">
                        <label for="serie" class="form-label">Serie:</label>
                        <input type="text" id="serie" class="form-control bg-dark text-white border-light">
                    </div>
                    <div class="col-md-12 mt-3 d-flex justify-content-between">
                        <button type="button" id="btnFiltrar" class="btn btn-success">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                        <button type="button" id="btnLimpiar" class="btn btn-warning">
                            <i class="bi bi-x-circle"></i> Limpiar filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container mb-4">
    <div class="row">
        <div class="col-12 d-flex justify-content-between">
            <a href="{{ route('bingos.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Panel
            </a>
            <button type="button" id="btnBorrarClientes" class="btn btn-danger">
                <i class="bi bi-trash"></i> Borrar Clientes
            </button>
        </div>
    </div>
</div>

<!-- Modal para editar series -->
<div class="modal fade" id="editSeriesModal" tabindex="-1" aria-labelledby="editSeriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="editSeriesModalLabel">Editar Series</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSeriesForm" action="" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="reserva_id" id="reserva_id">
                    <input type="hidden" name="bingo_id" id="bingo_id">

                    <div class="mb-3">
                        <label class="form-label">Nombre del cliente:</label>
                        <p id="clientName" class="fs-5"></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Series actuales:</label>
                        <div id="currentSeries" class="mb-3">
                            <!-- Series serán mostradas aquí -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="newQuantity" class="form-label">Nueva cantidad de cartones:</label>
                        <input type="number" class="form-control bg-dark text-white" id="newQuantity" name="new_quantity" min="1">
                        <small class="text-muted">Total actual: <span id="currentTotal"></span> pesos</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Seleccionar series a mantener:</label>
                        <div id="seriesCheckboxes" class="row">
                            <!-- Checkboxes serán generados dinámicamente -->
                        </div>
                    </div>

                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Importante:</strong> Las series que no selecciones quedarán disponibles para nuevas compras. El total a pagar se actualizará automáticamente.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveSeriesChanges">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para borrar clientes -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header bg-danger">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>ADVERTENCIA:</strong> Esta acción es irreversible.
                </div>
                <p>Estás a punto de eliminar <strong>TODOS</strong> los registros de la tabla clientes.</p>
                <p>Esta acción no se puede deshacer y resultará en la pérdida permanente de todos los datos de clientes.</p>
                
                <!-- Campo de confirmación para mayor seguridad -->
                <div class="form-group mt-3">
                    <label for="confirmText">Para confirmar, escribe "BORRAR TODOS LOS CLIENTES" en el campo de abajo:</label>
                    <input type="text" class="form-control bg-dark text-white border-danger mt-2" id="confirmText" placeholder="Escribe aquí para confirmar">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteClientsForm" action="{{ route('admin.borrarClientes') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="confirmDeleteBtn" class="btn btn-danger" disabled>
                        <i class="bi bi-trash"></i> Eliminar Todos los Clientes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


    <!-- Contenedor para la tabla (se actualizará dinámicamente) -->
    <div class="container" id="tableContent">
        {{-- Incluimos inicialmente la tabla original --}}
        @include('admin.table', ['reservas' => $reservas])
    </div>
</div>


@endsection