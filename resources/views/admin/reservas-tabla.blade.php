<!-- Tabla con ID para DataTables -->
<table id="reservas-table" class="table table-dark table-striped align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Celular</th>
        <th>Fecha</th>
        <th># Cartones</th>
        <th>Series</th>
        <th>Bingo</th>
        <th>Total</th>
        <th>Comprobante</th>
        <th># Comprobante</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody id="reservas-tbody">
      @forelse($reservas as $reserva)
      <tr class="reserva-row" 
          data-estado="{{ $reserva->estado }}" 
          data-nombre="{{ $reserva->nombre }}" 
          data-celular="{{ $reserva->celular }}"
          data-series="{{ is_string($reserva->series) ? $reserva->series : json_encode($reserva->series) }}">
        <td>{{ $reserva->id }}</td>
        <td>{{ $reserva->nombre }}</td>
        <td>{{ $reserva->celular }}</td>
        <td>{{ $reserva->created_at->format('d/m/Y H:i') }}</td>
        <td>{{ $reserva->cantidad }}</td>
        <td>
          @php
          $seriesData = $reserva->series;
          if (is_string($seriesData) && json_decode($seriesData) !== null) {
            $seriesData = json_decode($seriesData, true);
          }
          @endphp
          @if(is_array($seriesData))
            {{ implode(', ', $seriesData) }}
          @else
            {{ $seriesData }}
          @endif
        </td>
        <td>
          @if($reserva->bingo)
            {{ $reserva->bingo->nombre }}
          @else
            <span class="text-warning">Sin asignar</span>
          @endif
        </td>
        <td>${{ number_format($reserva->total, 0, ',', '.') }} Pesos</td>
        <td>
          @if($reserva->comprobante)
            @php
              $comprobantes = is_array($reserva->comprobante) ? $reserva->comprobante : json_decode($reserva->comprobante, true);
            @endphp
            @if(is_array($comprobantes) && count($comprobantes) > 0)
              @foreach($comprobantes as $index => $comprobante)
                <a href="{{ asset('storage/' . $comprobante) }}" target="_blank" class="btn btn-sm btn-light">
                  Ver comprobante {{ $index + 1 }}
                </a>
              @endforeach
            @else
              <span class="text-danger">Sin comprobante</span>
            @endif
          @else
            <span class="text-danger">Sin comprobante</span>
          @endif
        </td>
        <td>
          @if($reserva->estado == 'revision')
            <input type="text" class="form-control form-control-sm bg-dark text-white border-light comprobante-input" 
                   value="{{ $reserva->numero_comprobante ?? '' }}" 
                   data-id="{{ $reserva->id }}">
          @else
            <input type="text" class="form-control form-control-sm bg-dark text-white border-light" 
                   value="{{ $reserva->numero_comprobante ?? '' }}">
          @endif
        </td>
        <td>
          @if($reserva->estado == 'revision')
            <span class="badge bg-warning text-dark">Revisión</span>
          @elseif($reserva->estado == 'aprobado')
            <span class="badge bg-success">Aprobado</span>
          @elseif($reserva->estado == 'rechazado')
            <span class="badge bg-danger">Rechazado</span>
          @else
            <span class="badge bg-secondary">{{ ucfirst($reserva->estado) }}</span>
          @endif
        </td>
        <td>
          @if($reserva->estado == 'revision' || $reserva->estado == 'aprobado')
            <button type="button" class="btn btn-sm btn-warning mb-1 edit-series"
              data-id="{{ $reserva->id }}"
              data-nombre="{{ $reserva->nombre }}"
              data-series="{{ is_string($reserva->series) ? $reserva->series : json_encode($reserva->series) }}"
              data-cantidad="{{ $reserva->cantidad }}"
              data-total="{{ $reserva->total }}"
              data-bingo-id="{{ $reserva->bingo_id }}"
              data-bingo-precio="{{ $reserva->bingo ? $reserva->bingo->precio : 0 }}">
              <i class="bi bi-pencil-square"></i> Editar Series
            </button>
          @endif

          @if($reserva->estado == 'revision')
            <form action="{{ route('reservas.aprobar', $reserva->id) }}" method="POST" class="d-inline aprobar-form mt-1">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn btn-sm btn-success me-1">Aprobar</button>
            </form>
            <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn btn-sm btn-danger">Rechazar</button>
            </form>
          @elseif($reserva->estado == 'aprobado')
            <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline mt-1">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn btn-sm btn-danger">Rechazar</button>
            </form>
          @elseif($reserva->estado == 'rechazado')
            <span class="text-white">Rechazado</span>
          @endif
        </td>
      </tr>
      @empty
      <tr id="empty-results-row">
        <td colspan="12" class="text-center">No hay reservas registradas.</td>
      </tr>
      @endforelse
    </tbody>
  </table>

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

  <!-- Scripts de jQuery y DataTables -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.0/js/responsive.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.3/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.3/js/buttons.print.min.js"></script>

  <script>
$(document).ready(function() {
  // Inicializar DataTable
  const table = $('#reservas-table').DataTable({
    responsive: true,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
    },
    dom: 'Bfrtip',
    buttons: [
      {
        text: '<i class="bi bi-eye"></i> Mostrar Todos',
        className: 'btn btn-secondary',
        action: function(e, dt, node, config) {
          // Reiniciar búsquedas y quitar clases de duplicados
          dt.search('').columns().search('').draw();
          dt.rows().nodes().each(function(row) {
            $(row).removeClass('duplicated-comprobante duplicated-price');
          });
          // Limpiar filtros personalizados
          $('#filter-estado').val('');
          $('#filter-nombre').val('');
          $('#filter-celular').val('');
          $('#filter-serie').val('');
        }
      },
      {
        extend: 'excel',
        text: '<i class="bi bi-file-excel"></i> Excel',
        className: 'btn btn-success',
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 9, 10]
        }
      },
      {
        extend: 'pdf',
        text: '<i class="bi bi-file-pdf"></i> PDF',
        className: 'btn btn-danger',
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 9, 10]
        }
      },
      {
        extend: 'print',
        text: '<i class="bi bi-printer"></i> Imprimir',
        className: 'btn btn-primary',
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 9, 10]
        }
      }
    ],
    order: [[0, 'desc']],
    columnDefs: [
      { orderable: true, targets: [0, 1, 2, 3, 7] },
      { orderable: false, targets: '_all' },
      { targets: 11, searchable: false }
    ],
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
    stateSave: true,
    searching: false
  });

  // Manejar la actualización del número de comprobante mediante AJAX
  $('.comprobante-input').on('blur', function() {
    const reservaId = $(this).data('id');
    const numeroComprobante = $(this).val();
    // Aquí puedes implementar el guardado vía AJAX
  });

  // Manejar el evento de clic para editar series
  $('.edit-series').on('click', function() {
    const modal = $('#editSeriesModal');
    const seriesData = $(this).data('series');
    let series = [];

    try {
      series = typeof seriesData === 'object' ? seriesData : JSON.parse(seriesData);
    } catch (e) {
      console.error('Error al parsear series:', e);
      // Si las series no están en formato JSON, intentar convertirlas desde string
      if (typeof seriesData === 'string') {
        series = seriesData.split(',').map(item => item.trim());
      }
    }

    const reservaId = $(this).data('id');
    const bingoId = $(this).data('bingo-id');
    const cantidad = parseInt($(this).data('cantidad'));
    const total = parseInt($(this).data('total'));
    const bingoPrice = parseInt($(this).data('bingo-precio'));

    // Completar datos del formulario
    $('#reserva_id').val(reservaId);
    $('#bingo_id').val(bingoId);
    $('#clientName').text($(this).data('nombre'));
    $('#newQuantity').val(cantidad);
    $('#newQuantity').attr('max', Array.isArray(series) ? series.length : 1);
    $('#currentTotal').text(new Intl.NumberFormat('es-CL').format(total));

    // Establecer URL del formulario
    const form = $('#editSeriesForm');
    
    // Construir URL basada en el pathname actual
    const currentPath = window.location.pathname;
    const baseUrl = currentPath.includes('/admin') 
      ? currentPath.substring(0, currentPath.indexOf('/admin')) 
      : '';
    
    // Usar esta URL para asegurar compatibilidad en todos los entornos
    form.attr('action', `${baseUrl}/reservas/${reservaId}/update-series`);
    
    // Para depurar
    console.log('URL del formulario:', form.attr('action'));
    console.log('ID de Reserva:', reservaId);
    console.log('Series:', series);

    // Mostrar series actuales y crear checkboxes
    const currentSeriesDiv = $('#currentSeries');
    const seriesCheckboxesDiv = $('#seriesCheckboxes');

    // Limpiar contenido previo
    currentSeriesDiv.empty();
    seriesCheckboxesDiv.empty();

    // Mostrar y crear checkboxes para cada serie
    if (Array.isArray(series) && series.length > 0) {
      const seriesList = $('<ul class="list-group"></ul>');

      series.forEach((serie, index) => {
        // Crear elemento de lista
        const listItem = $(`<li class="list-group-item bg-dark text-white border-light">Serie ${serie}</li>`);
        seriesList.append(listItem);

        // Crear checkbox
        const col = $('<div class="col-md-4 mb-2"></div>');
        const checkDiv = $('<div class="form-check"></div>');
        const checkbox = $(`<input type="checkbox" id="serie_${index}" name="selected_series[]" value="${serie}" class="form-check-input" checked>`);
        const label = $(`<label for="serie_${index}" class="form-check-label">Serie ${serie}</label>`);

        checkDiv.append(checkbox).append(label);
        col.append(checkDiv);
        seriesCheckboxesDiv.append(col);
      });

      currentSeriesDiv.append(seriesList);
    } else {
      currentSeriesDiv.text('No hay series disponibles');
    }

    // Función para actualizar contador de seleccionados
    function updateSelectedCounter() {
      const checkedCount = $('input[name="selected_series[]"]:checked').length;
      const newQuantity = parseInt($('#newQuantity').val());

      // Verificar si se están seleccionando más series de las permitidas
      if (checkedCount > newQuantity) {
        // Desmarcar los últimos checkboxes seleccionados para que coincida con la cantidad
        let toUncheck = checkedCount - newQuantity;
        $($('input[name="selected_series[]"]:checked').get().reverse()).each(function() {
          if (toUncheck > 0) {
            $(this).prop('checked', false);
            toUncheck--;
          }
        });
      }
    }

    // Manejar cambio en la cantidad de cartones
    $('#newQuantity').off('change').on('change', function() {
      const newQuantity = parseInt($(this).val());
      
      // Actualizar el total estimado
      const newTotal = newQuantity * bingoPrice;
      $('#currentTotal').text(new Intl.NumberFormat('es-CL').format(newTotal));

      // Actualizar contador
      updateSelectedCounter();
    });

    // Añadir listeners a los checkboxes
    $('input[name="selected_series[]"]').off('change').on('change', function() {
      const newQuantity = parseInt($('#newQuantity').val());
      const checkedCount = $('input[name="selected_series[]"]:checked').length;

      // Si se excede la cantidad permitida, desmarcar este checkbox
      if (checkedCount > newQuantity && $(this).is(':checked')) {
        $(this).prop('checked', false);
        alert(`Solo puedes seleccionar ${newQuantity} series.`);
      }
    });

    // Inicializar contador
    updateSelectedCounter();

    // Mostrar modal
    modal.modal('show');

    // Manejar clic en el botón de guardar
    $('#saveSeriesChanges').off('click').on('click', function() {
      const selectedCheckboxes = $('input[name="selected_series[]"]:checked');
      const newQuantity = parseInt($('#newQuantity').val());

      if (selectedCheckboxes.length !== newQuantity) {
        alert(`Debes seleccionar exactamente ${newQuantity} series.`);
        return;
      }

      // Mostrar indicador de carga
      $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');

      // Enviar formulario
      $('#editSeriesForm').submit();
    });

    // Manejar errores de envío del formulario
    $('#editSeriesForm').off('submit').on('submit', function(e) {
      // No es necesario prevenir el comportamiento predeterminado, 
      // pero podríamos usar AJAX para manejar errores específicos si es necesario
      
      // Ejemplo de cómo manejar con AJAX si prefieres:
      /*
      e.preventDefault();
      
      $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
          modal.modal('hide');
          // Mostrar mensaje de éxito
          alert('Series actualizadas correctamente');
          // Recargar página para ver cambios
          window.location.reload();
        },
        error: function(xhr) {
          // Habilitar botón nuevamente
          $('#saveSeriesChanges').prop('disabled', false).text('Guardar Cambios');
          
          // Mostrar mensaje de error
          let errorMsg = 'Error al actualizar series';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = xhr.responseJSON.message;
          }
          alert(errorMsg);
        }
      });
      */
    });
  });

  // Manejar evento de formularios de aprobación/rechazo
  $('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]').on('submit', function() {
    // Encuentra la fila que contiene el formulario
    const row = $(this).closest('tr');
    // Busca el input editable del número de comprobante en la misma fila
    const input = row.find('.comprobante-input');
    if (input.length) {
      // Crea un campo oculto para enviar el valor
      $('<input>').attr({
        type: 'hidden',
        name: 'numero_comprobante',
        value: input.val()
      }).appendTo(this);
    }
  });
});
  </script>