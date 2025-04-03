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
        <td class="fw-bold">{{ $reserva->orden_bingo ?? 'N/A' }}</td>
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
            <span class="badge bg-warning text-dark">Disponible</span>
          @elseif($reserva->estado == 'aprobado')
            <span class="badge bg-success">Aprobado</span>
          @elseif($reserva->estado == 'rechazado')
            <span class="badge bg-danger">Rechazado</span>
          @else
            <span class="badge bg-secondary">{{ ucfirst($reserva->estado) }}</span>
          @endif
        </td>
        <td>
         <!-- Cambiado de "revision o aprobado" a solo "revision" -->
         @if($reserva->estado == 'revision')
                <button type="button" class="btn btn-sm btn-warning mb-1 edit-series"
                    data-id="{{ $reserva->id }}"
                    data-update-url="{{ route('reservas.update-series', $reserva->id) }}"
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
                <form action="{{ route('reservas.aprobar', $reserva->id) }}" method="POST" class="d-inline">
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
                <form action="{{ route('reservas.rechazar', $reserva->id) }}" method="POST" class="d-inline">
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
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable con procesamiento del lado del servidor
    $('#reservas-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reservas.bingo.tabla', $bingo->id) }}",
            data: function(d) {
                d.tipo = "{{ request('tipo', 'todas') }}";
                d.nombre = $('#nombre').val();
                d.celular = $('#celular').val();
                d.serie = $('#serie').val();
            }
        },
        columns: [
            // Columna oculta para ordenación por "orden_bingo"
            { data: 'orden_bingo', name: 'orden_bingo', visible: false },
            { data: 'id', name: 'id' },
            { data: 'nombre', name: 'nombre' },
            { data: 'celular', name: 'celular' },
            { data: 'series', name: 'series' },
            { data: 'estado', name: 'estado' },
            { data: 'cantidad', name: 'cantidad' },
            { data: 'total', name: 'total' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        // Optimizaciones de rendimiento
        deferRender: true,
        scroller: true,
        scrollY: '60vh',
        scrollCollapse: true,
        // Ordenar por "orden_bingo" (columna oculta en el índice 0) de forma ascendente
        order: [[0, 'asc']],
        // Configuración de idioma
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });

      // Ejemplo: Manejar la actualización del número de comprobante mediante AJAX (comentado)
      $('.comprobante-input').on('blur', function() {
        const reservaId = $(this).data('id');
        const numeroComprobante = $(this).val();
        // Aquí puedes implementar el guardado vía AJAX
      });
    });
  </script>