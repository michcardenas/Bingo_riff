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

      // Ejemplo: Manejar la actualización del número de comprobante mediante AJAX (comentado)
      $('.comprobante-input').on('blur', function() {
        const reservaId = $(this).data('id');
        const numeroComprobante = $(this).val();
        // Aquí puedes implementar el guardado vía AJAX
      });
    });
  </script>