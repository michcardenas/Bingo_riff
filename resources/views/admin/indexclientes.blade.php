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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variable global para la tabla
    let dataTable;
    
    // Inicializar DataTable con procesamiento del lado del servidor
    function initializeDataTable() {
        // Destruir la tabla existente si hay una
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
        
        // Mostrar indicador de carga
        const tableContainer = document.getElementById('tableContent');
        if (tableContainer) {
            tableContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div><p class="mt-2 text-light">Cargando...</p></div>';
        }
        
        // Inicializar DataTable con server-side processing
        dataTable = $('#reservas-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false, // Desactivamos búsqueda integrada, usamos nuestros filtros
            autoWidth: false, // Mejora rendimiento
            deferRender: true, // Solo renderiza filas visibles
            ajax: {
                url: "{{ route('bingos.reservas-tabla', $bingo->id) }}",
                type: 'GET',
                data: function(d) {
                    // Añadir filtros personalizados
                    d.nombre = $('#nombre').val();
                    d.celular = $('#celular').val();
                    d.serie = $('#serie').val();
                    d.tipo = $('#filter-estado').val();
                },
                error: function(xhr, status, error) {
                    console.error('Error en la solicitud AJAX:', error);
                    // Mostrar mensaje de error amigable
                    $('#reservas-table tbody').html('<tr><td colspan="12" class="text-center text-danger">Error al cargar los datos. Por favor, inténtelo de nuevo más tarde.</td></tr>');
                }
            },
            columns: [
                { data: 'id', name: 'orden_bingo' },
                { data: 'nombre', name: 'nombre' },
                { data: 'celular', name: 'celular' },
                { data: 'created_at', name: 'created_at' },
                { data: 'cantidad', name: 'cantidad' },
                { data: 'series', name: 'series' },
                { data: 'bingo', name: 'bingo' },
                { data: 'total', name: 'total' },
                { data: 'comprobante', name: 'comprobante', orderable: false },
                { data: 'numero_comprobante', name: 'numero_comprobante', orderable: false },
                { data: 'estado', name: 'estado' },
                { data: 'acciones', name: 'acciones', orderable: false }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                processing: '<div class="spinner-border text-light" role="status"></div>',
                emptyTable: "No hay datos disponibles",
                zeroRecords: "No hay resultados que concuerden con tu filtro"
            },
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
            createdRow: function(row, data, dataIndex) {
                // Aplicar clases y atributos a la fila
                if (data.DT_RowClass) {
                    $(row).addClass(data.DT_RowClass);
                }
                
                if (data.DT_RowAttr) {
                    $.each(data.DT_RowAttr, function(key, value) {
                        $(row).attr(key, value);
                    });
                }
            },
            drawCallback: function() {
                // Aplicar estilos de tema oscuro a los elementos de DataTables
                $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').addClass('text-white');
                $('.dataTables_wrapper .form-control').addClass('bg-dark text-white border-secondary');
                $('.dataTables_wrapper .page-link').addClass('bg-dark text-white border-secondary');
                
                // Configurar delegación de eventos para los elementos dinámicos
                setupEventDelegation();
            },
            initComplete: function() {
                console.log('DataTable inicializado correctamente');
            }
        });
    }
    
    // Configurar delegación de eventos para elementos dinámicos
    function setupEventDelegation() {
        // Delegación para inputs de comprobante
        $(document).off('blur', '.comprobante-input').on('blur', '.comprobante-input', function() {
            const reservaId = $(this).data('id');
            const numeroComprobante = $(this).val().trim();
            const originalValue = $(this).data('original-value') || '';
            
            // Solo hacer la solicitud si el valor ha cambiado
            if (originalValue !== numeroComprobante) {
                updateNumeroComprobante(reservaId, numeroComprobante, $(this));
            }
        });
        
        // Delegación para botones de editar series
        $(document).off('click', '.edit-series').on('click', '.edit-series', function(e) {
            e.preventDefault();
            handleEditSeries(this);
        });
        
        // Delegación para formularios de aprobar/rechazar
        $(document).off('submit', '.aprobar-form, .rechazar-form').on('submit', '.aprobar-form, .rechazar-form', function(e) {
            e.preventDefault();
            handleFormSubmit(this);
        });
    }
    
    // Manejar actualización del número de comprobante
    function updateNumeroComprobante(reservaId, numeroComprobante, input) {
        // Guardar el valor original y desactivar el input
        input.data('original-value', numeroComprobante);
        input.prop('disabled', true);
        
        $.ajax({
            url: "{{ route('reservas.update-comprobante', ['id' => '_id_']) }}".replace('_id_', reservaId),
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                numero_comprobante: numeroComprobante
            },
            success: function(response) {
                input.addClass('border-success');
                setTimeout(function() {
                    input.removeClass('border-success');
                }, 2000);
            },
            error: function() {
                input.addClass('border-danger');
                setTimeout(function() {
                    input.removeClass('border-danger');
                }, 2000);
            },
            complete: function() {
                input.prop('disabled', false);
            }
        });
    }
    
    // Manejar el envío de formularios de aprobar/rechazar
    function handleFormSubmit(form) {
        const $form = $(form);
        const url = $form.attr('action');
        const isApprove = $form.hasClass('aprobar-form');
        const buttonText = isApprove ? 'Aprobar' : 'Rechazar';
        const $button = $form.find('button[type="submit"]');
        
        // Añadir número de comprobante si existe
        const $row = $form.closest('tr');
        const $input = $row.find('.comprobante-input');
        if ($input.length > 0) {
            const comprobante = $input.val();
            // Añadir o actualizar campo oculto
            if (!$form.find('input[name="numero_comprobante"]').length) {
                $form.append(`<input type="hidden" name="numero_comprobante" value="${comprobante}">`);
            } else {
                $form.find('input[name="numero_comprobante"]').val(comprobante);
            }
        }
        
        // Deshabilitar botón y mostrar indicador de carga
        $button.prop('disabled', true);
        $button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        
        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                // Recargar tabla para reflejar cambios
                dataTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                console.error('Error al procesar la solicitud:', xhr);
                alert('Error al procesar la solicitud');
            },
            complete: function() {
                // Restaurar botón
                $button.prop('disabled', false);
                $button.html(buttonText);
            }
        });
    }
    
    // Manejar edición de series
    function handleEditSeries(button) {
        const modal = document.getElementById('editSeriesModal');
        if (!modal) return;
        
        const seriesData = button.getAttribute('data-series');
        let series = [];
        
        try {
            series = JSON.parse(seriesData);
        } catch (e) {
            console.error('Error al parsear series:', e);
            // Si no es JSON, intentar como string separado por comas
            if (typeof seriesData === 'string') {
                series = seriesData.split(',').map(item => item.trim());
            }
        }
        
        // Extraer datos del botón
        const reservaId = button.getAttribute('data-id');
        const bingoId = button.getAttribute('data-bingo-id');
        const cantidad = parseInt(button.getAttribute('data-cantidad'));
        const total = parseInt(button.getAttribute('data-total'));
        const bingoPrice = parseInt(button.getAttribute('data-bingo-precio'));
        const nombre = button.getAttribute('data-nombre');
        const updateUrl = button.getAttribute('data-update-url');
        
        // Actualizar formulario
        const form = document.getElementById('editSeriesForm');
        if (form) {
            form.action = updateUrl;
            form.querySelector('#reserva_id').value = reservaId;
            form.querySelector('#bingo_id').value = bingoId;
        }
        
        // Actualizar campos del modal
        document.getElementById('clientName').textContent = nombre;
        document.getElementById('newQuantity').value = cantidad;
        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);
        
        // Mostrar series actuales
        const currentSeriesDiv = document.getElementById('currentSeries');
        currentSeriesDiv.innerHTML = '';
        
        if (Array.isArray(series) && series.length > 0) {
            const seriesList = document.createElement('div');
            seriesList.className = 'row g-2';
            
            series.forEach(serie => {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-6';
                
                const badge = document.createElement('span');
                badge.className = 'badge bg-info d-block py-2';
                badge.textContent = serie;
                
                col.appendChild(badge);
                seriesList.appendChild(col);
            });
            
            currentSeriesDiv.appendChild(seriesList);
        } else {
            currentSeriesDiv.innerHTML = '<div class="alert alert-warning">No hay series disponibles</div>';
        }
        
        // Generar checkboxes para selección
        const seriesCheckboxesDiv = document.getElementById('seriesCheckboxes');
        seriesCheckboxesDiv.innerHTML = '';
        
        if (Array.isArray(series) && series.length > 0) {
            series.forEach((serie, index) => {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-6 mb-2';
                
                const checkDiv = document.createElement('div');
                checkDiv.className = 'form-check';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.id = `serie_${index}`;
                checkbox.name = 'selected_series[]';
                checkbox.value = serie;
                checkbox.className = 'form-check-input';
                checkbox.checked = true;
                
                const label = document.createElement('label');
                label.htmlFor = `serie_${index}`;
                label.className = 'form-check-label';
                label.textContent = serie;
                
                checkDiv.appendChild(checkbox);
                checkDiv.appendChild(label);
                col.appendChild(checkDiv);
                seriesCheckboxesDiv.appendChild(col);
            });
        }
        
        // Configurar el evento de cambio de cantidad
        const newQuantityInput = document.getElementById('newQuantity');
        $(newQuantityInput).off('change').on('change', function() {
            const newQuantity = parseInt(this.value) || 0;
            
            // Actualizar total estimado
            const newTotal = newQuantity * bingoPrice;
            document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(newTotal);
            
            // Validar selección de series
            validateCheckboxSelection(newQuantity);
        });
        
        // Configurar eventos de checkbox
        const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
        checkboxes.forEach(cb => {
            $(cb).off('change').on('change', function() {
                validateCheckboxSelection(parseInt(newQuantityInput.value) || 0);
            });
        });
        
        // Configurar botón de guardar
        const saveButton = document.getElementById('saveSeriesChanges');
        $(saveButton).off('click').on('click', function() {
            const selectedCheckboxes = document.querySelectorAll('input[name="selected_series[]"]:checked');
            const newQuantity = parseInt(newQuantityInput.value) || 0;
            
            // Validaciones
            if (newQuantity < 1) {
                alert('La cantidad de cartones debe ser un número mayor a cero');
                return;
            }
            
            if (selectedCheckboxes.length !== newQuantity) {
                alert(`Debes seleccionar exactamente ${newQuantity} series`);
                return;
            }
            
            // Enviar via AJAX
            const formData = new FormData(form);
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            
            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    // Cerrar modal y recargar tabla
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    bsModal.hide();
                    dataTable.ajax.reload(null, false);
                },
                error: function(xhr) {
                    alert('Error al actualizar series');
                    console.error(xhr);
                },
                complete: function() {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Guardar Cambios';
                }
            });
        });
        
        // Abrir modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }
    
    // Validar selección de checkboxes
    function validateCheckboxSelection(newQuantity) {
        const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
        const selected = document.querySelectorAll('input[name="selected_series[]"]:checked');
        
        // Si hay más seleccionados que la cantidad permitida
        if (selected.length > newQuantity) {
            let toDeselect = selected.length - newQuantity;
            
            // Desmarcar los últimos seleccionados
            for (let i = checkboxes.length - 1; i >= 0 && toDeselect > 0; i--) {
                if (checkboxes[i].checked) {
                    checkboxes[i].checked = false;
                    toDeselect--;
                }
            }
        }
    }
    
    // Eventos de filtros
    $('#btnFiltrar').on('click', function() {
        dataTable.ajax.reload();
    });
    
    $('#btnLimpiar').on('click', function() {
        $('#nombre, #celular, #serie, #filter-estado').val('');
        dataTable.ajax.reload();
    });
    
    // Permitir filtrar con tecla Enter
    $('#nombre, #celular, #serie, #filter-estado').on('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnFiltrar').click();
        }
    });
    
    // Inicializar DataTable al cargar la página
    initializeDataTable();
});
</script>
@endsection