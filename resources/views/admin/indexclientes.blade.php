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
    let currentTable = null;
    
    // Initialize DataTable with optimized settings
    function initializeDataTable() {
        // Make sure any existing DataTable is properly destroyed
        if ($.fn.DataTable.isDataTable('.table')) {
            $('.table').DataTable().destroy();
        }
        
        try {
            // Initialize DataTable with optimized settings
            currentTable = $('.table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                    emptyTable: "No hay datos disponibles",
                    zeroRecords: "No hay resultados que concuerden con tu filtro"
                },
                order: [[0, 'desc']], // Sort by ID in descending order
                responsive: true,
                // Enable server-side processing for large datasets
                serverSide: false, // Change to true when implementing server-side processing
                // Implement paging for better performance with large datasets
                paging: true,
                pageLength: 25, // Show 25 entries per page
                // Optimize DOM structure
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                // Defer rendering for better performance
                deferRender: true,
                // Process data in batches for better performance
                processing: true,
                // Initialize callbacks
                initComplete: function() {
                    // Add custom classes for dark theme
                    $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').addClass('text-white');
                    $('.dataTables_wrapper .form-control').addClass('bg-dark text-white border-secondary');
                    $('.dataTables_wrapper .page-link').addClass('bg-dark text-white border-secondary');
                    
                    // Setup table events only once after initialization
                    setupTableEvents();
                    
                    console.log('DataTable initialized successfully');
                }
            });
        } catch (error) {
            console.error('Error initializing DataTable:', error);
            showNoResultsMessage();
        }
    }

    // Optimized DataTables Configuration for Large Datasets
function initOptimizedDataTable() {
    // Ensure any existing DataTable is properly destroyed
    if ($.fn.DataTable.isDataTable('.table')) {
        $('.table').DataTable().destroy();
    }
    
    return $('.table').DataTable({
        // Language settings
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
            emptyTable: "No hay datos disponibles",
            zeroRecords: "No hay resultados que concuerden con tu filtro"
        },
        
        // Performance optimizations
        processing: true,        // Show processing indicator
        deferRender: true,       // Defer rendering for better performance
        scroller: true,          // Enable virtual scrolling
        scrollY: '50vh',         // Set height for vertical scroll
        scrollCollapse: true,    // Enable scroll collapse
        
        // Server-side processing (enable when backend API is ready)
        serverSide: false,       // Change to true when server endpoint is ready
        ajax: null,              // Set your API endpoint when serverSide: true
        
        // Data display settings
        order: [[0, 'desc']],    // Sort by ID in descending order
        responsive: true,        // Enable responsive design
        
        // Control page length options
        pageLength: 25,          // Default page size
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        
        // DOM structure optimization
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        
        // Optimize column rendering for large datasets
        columnDefs: [
            {
                // Add any column-specific optimizations here
                // Example: don't sort on action column
                targets: [-1], // Last column
                orderable: false
            }
        ],
        
        // Advanced callbacks
        createdRow: function(row, data, dataIndex) {
            // Add any row-specific customizations
            // Example: highlight specific rows based on status
            if (data.estado === 'pendiente') {
                $(row).addClass('bg-warning bg-opacity-25');
            }
        },
        
        // Callbacks for customization and debugging
        drawCallback: function(settings) {
            // Handle empty data states
            if (settings.bDestroying) return;
            
            // Check if there's data
            if (this.api().data().length === 0) {
                const tbody = document.querySelector('.table tbody');
                if (tbody && !document.querySelector('.dataTables_empty')) {
                    const tr = document.createElement('tr');
                    const td = document.createElement('td');
                    td.className = 'dataTables_empty';
                    td.textContent = "No hay resultados que concuerden con tu filtro";
                    td.setAttribute('colspan', '100%');
                    tr.appendChild(td);
                    tbody.innerHTML = '';
                    tbody.appendChild(tr);
                }
            }
        },
        
        // Final initialization callback
        initComplete: function() {
            // Add dark theme classes
            $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').addClass('text-white');
            $('.dataTables_wrapper .form-control').addClass('bg-dark text-white border-secondary');
            $('.dataTables_wrapper .page-link').addClass('bg-dark text-white border-secondary');
            
            // Set up event listeners for dynamic elements
            setupTableEvents();
            
            console.log('DataTable initialized successfully');
        }
    });
}
    
    // Show "No results" message if DataTable fails
    function showNoResultsMessage() {
        const table = document.querySelector('.table');
        if (table) {
            const tbody = table.querySelector('tbody');
            if (tbody) {
                const headerCells = table.querySelectorAll('thead th');
                const colCount = headerCells.length || 1;
                
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.setAttribute('colspan', colCount);
                td.className = 'text-center py-3';
                td.textContent = "No hay resultados que concuerden con tu filtro";
                tr.appendChild(td);
                
                tbody.innerHTML = '';
                tbody.appendChild(tr);
            }
        }
    }
    
    // Set up events in the table - called only once after DataTable initialization
    function setupTableEvents() {
        // Use event delegation instead of attaching to each element
        const tableElement = document.querySelector('.table');
        if (!tableElement) return;
        
        // Handle edit series buttons with event delegation
        tableElement.addEventListener('click', function(e) {
            const editButton = e.target.closest('.edit-series');
            if (editButton) {
                handleEditSeries.call(editButton);
            }
        });
        
        // Handle form submissions with event delegation
        tableElement.addEventListener('submit', function(e) {
            const form = e.target.closest('.aprobar-form, form[action*="aprobar"], form[action*="rechazar"]');
            if (form) {
                handleFormSubmit.call(form, e);
            }
        });
    }
    
    // Handler for editing series
    function handleEditSeries() {
        const modal = document.getElementById('editSeriesModal');
        const seriesData = this.getAttribute('data-series');
        let series = [];

        try {
            series = JSON.parse(seriesData);
        } catch (e) {
            console.error('Error parsing series:', e);
            if (typeof seriesData === 'string') {
                series = seriesData.split(',').map(item => item.trim());
            }
        }

        const reservaId = this.getAttribute('data-id');
        const bingoId = this.getAttribute('data-bingo-id');
        const cantidad = parseInt(this.getAttribute('data-cantidad'));
        const total = parseInt(this.getAttribute('data-total'));
        const bingoPrice = parseInt(this.getAttribute('data-bingo-precio'));

        // Populate form data
        document.getElementById('reserva_id').value = reservaId;
        document.getElementById('bingo_id').value = bingoId;
        document.getElementById('clientName').textContent = this.getAttribute('data-nombre');
        document.getElementById('newQuantity').value = cantidad;
        document.getElementById('newQuantity').setAttribute('max', Array.isArray(series) ? series.length : 1);
        document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(total);

        // Set form URL
        const form = document.getElementById('editSeriesForm');
        form.action = `/admin/reservas/${reservaId}/update-series`;

        // Get DOM elements
        const currentSeriesDiv = document.getElementById('currentSeries');
        const seriesCheckboxesDiv = document.getElementById('seriesCheckboxes');

        // Clear previous content
        currentSeriesDiv.innerHTML = '';
        seriesCheckboxesDiv.innerHTML = '';

        // Use DocumentFragment for better performance
        const seriesFragment = document.createDocumentFragment();
        const checkboxesFragment = document.createDocumentFragment();

        // Display and create checkboxes for each series
        if (Array.isArray(series) && series.length > 0) {
            const seriesList = document.createElement('ul');
            seriesList.className = 'list-group';

            series.forEach((serie, index) => {
                // Create list item for current series
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item bg-dark text-white border-light';
                listItem.textContent = `Serie ${serie}`;
                seriesList.appendChild(listItem);

                // Create checkbox
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-2';

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
                label.textContent = `Serie ${serie}`;

                checkDiv.appendChild(checkbox);
                checkDiv.appendChild(label);
                col.appendChild(checkDiv);
                checkboxesFragment.appendChild(col);
            });

            seriesFragment.appendChild(seriesList);
            currentSeriesDiv.appendChild(seriesFragment);
            seriesCheckboxesDiv.appendChild(checkboxesFragment);
        } else {
            currentSeriesDiv.textContent = 'No hay series disponibles';
        }

        // Handle quantity change - use a single event listener
        const newQuantityInput = document.getElementById('newQuantity');
        if (newQuantityInput) {
            // Remove existing listeners to prevent duplicates
            const newInput = newQuantityInput.cloneNode(true);
            newQuantityInput.parentNode.replaceChild(newInput, newQuantityInput);
            
            newInput.addEventListener('change', function() {
                const newQuantity = parseInt(this.value);
                
                // Update estimated total
                const newTotal = newQuantity * bingoPrice;
                document.getElementById('currentTotal').textContent = new Intl.NumberFormat('es-CL').format(newTotal);

                // Update counter
                updateSelectedCounter();
            });
        }

        // Function to update selected counter
        function updateSelectedCounter() {
            const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
            const newQuantity = parseInt(document.getElementById('newQuantity').value);
            let checkedCount = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) checkedCount++;
            });

            // Check if more series are selected than allowed
            if (checkedCount > newQuantity) {
                // Uncheck the last selected checkboxes to match the quantity
                let toUncheck = checkedCount - newQuantity;
                for (let i = checkboxes.length - 1; i >= 0 && toUncheck > 0; i--) {
                    if (checkboxes[i].checked) {
                        checkboxes[i].checked = false;
                        toUncheck--;
                    }
                }
            }
        }

        // Add event delegation for checkboxes
        seriesCheckboxesDiv.addEventListener('change', function(e) {
            if (e.target.matches('input[name="selected_series[]"]')) {
                const newQuantity = parseInt(document.getElementById('newQuantity').value);
                const checkboxes = document.querySelectorAll('input[name="selected_series[]"]');
                let checkedCount = 0;

                checkboxes.forEach(cb => {
                    if (cb.checked) checkedCount++;
                });

                // If exceeding allowed quantity, uncheck this checkbox
                if (checkedCount > newQuantity && e.target.checked) {
                    e.target.checked = false;
                    alert(`Solo puedes seleccionar ${newQuantity} series.`);
                }
            }
        });

        // Initialize counter
        updateSelectedCounter();

        // Show modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Handle save button click - use a single event listener
        const saveButton = document.getElementById('saveSeriesChanges');
        if (saveButton) {
            // Remove existing listeners to prevent duplicates
            const newButton = saveButton.cloneNode(true);
            saveButton.parentNode.replaceChild(newButton, saveButton);
            
            newButton.addEventListener('click', function() {
                const selectedCheckboxes = document.querySelectorAll('input[name="selected_series[]"]:checked');
                const newQuantity = parseInt(document.getElementById('newQuantity').value);

                if (selectedCheckboxes.length !== newQuantity) {
                    alert(`Debes seleccionar exactamente ${newQuantity} series.`);
                    return;
                }

                // Submit form
                document.getElementById('editSeriesForm').submit();
            });
        }
    }
    
    // Handler for form submission
    function handleFormSubmit(event) {
        // Find row containing the form
        const row = this.closest('tr');
        // Find editable input for receipt number in the same row
        const input = row.querySelector('.comprobante-input');
        if (input) {
            // Create hidden field to send value
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'numero_comprobante';
            hiddenInput.value = input.value;
            this.appendChild(hiddenInput);
        }
    }
    
    // Function to apply filters - optimized
    function aplicarFiltros() {
        const nombre = document.getElementById('nombre')?.value.trim() || '';
        const celular = document.getElementById('celular')?.value.trim() || '';
        const serie = document.getElementById('serie')?.value.trim() || '';
        
        console.log("Applying filters:", { nombre, celular, serie });
        
        // Filter using DataTable API
        if (currentTable) {
            try {
                // Clear current filters
                currentTable.search('').columns().search('').draw();
                
                // If series value exists, use it as global search
                if (serie) {
                    currentTable.search(serie).draw();
                    return;
                }
                
                // For other filters, apply by column
                let filtersApplied = false;
                
                if (nombre) {
                    // Verify column exists before trying to filter
                    if (currentTable.columns(1).nodes().length > 0) {
                        currentTable.columns(1).search(nombre, true, false);
                        filtersApplied = true;
                    }
                }
                
                if (celular) {
                    // Verify column exists before trying to filter
                    if (currentTable.columns(2).nodes().length > 0) {
                        currentTable.columns(2).search(celular, true, false);
                        filtersApplied = true;
                    }
                }
                
                // Draw table with applied filters
                currentTable.draw();
            } catch (error) {
                console.error("Error applying filters:", error);
                showNoResultsMessage();
            }
        } else {
            console.error("DataTable not properly initialized");
            showNoResultsMessage();
        }
    }
    
    // Function to clear filters
    function limpiarFiltros() {
        // Clear text fields
        document.querySelectorAll('#nombre, #celular, #serie').forEach(input => {
            if (input) input.value = '';
        });
        
        // Clear DataTable filters
        if (currentTable) {
            try {
                currentTable.search('').columns().search('').draw();
            } catch (error) {
                console.error("Error clearing filters:", error);
                initializeDataTable();
            }
        }
    }
    
    // Function to load content into 'tableContent' container - optimized with debounce
    const loadTableContent = (function() {
        let timer;
        return function(url) {
            // Clear any pending loadTableContent calls
            clearTimeout(timer);
            
            // Save current filter values
            const filtros = {
                nombre: document.getElementById('nombre')?.value || '',
                celular: document.getElementById('celular')?.value || '',
                serie: document.getElementById('serie')?.value || ''
            };
            
            // Show loading indicator
            const tableContainer = document.getElementById('tableContent');
            const loadingHTML = '<div class="text-center p-5"><div class="spinner-border text-light" role="status"></div><p class="mt-2 text-light">Cargando...</p></div>';
            tableContainer.innerHTML = loadingHTML;
            
            // Destroy existing DataTable if it exists
            if (currentTable !== null) {
                try {
                    currentTable.destroy();
                } catch (error) {
                    console.error("Error destroying DataTable:", error);
                }
                currentTable = null;
            }
            
            // Delay actual AJAX request to prevent multiple rapid calls
            timer = setTimeout(function() {
                // Make AJAX request
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    // Update content
                    tableContainer.innerHTML = html;
                    
                    // Check if table exists before initializing DataTable
                    if (document.querySelector('.table')) {
                        // Reinitialize DataTable
                        initializeDataTable();
                        
                        // Restore filter values
                        const nombreInput = document.getElementById('nombre');
                        const celularInput = document.getElementById('celular');
                        const serieInput = document.getElementById('serie');
                        
                        if (nombreInput) nombreInput.value = filtros.nombre;
                        if (celularInput) celularInput.value = filtros.celular;
                        if (serieInput) serieInput.value = filtros.serie;
                        
                        // Apply filters if any was active
                        if (filtros.nombre || filtros.celular || filtros.serie) {
                            setTimeout(aplicarFiltros, 100); // Small delay to ensure DataTable is ready
                        }
                    } else {
                        // If no table, show informational message
                        tableContainer.innerHTML = '<div class="alert alert-info">No hay datos disponibles para mostrar.</div>';
                    }
                    
                    // Update active buttons
                    updateActiveButtons(url);
                })
                .catch(error => {
                    console.error('Error loading content:', error);
                    tableContainer.innerHTML = '<div class="alert alert-danger">Error al cargar los datos. Por favor, intenta nuevamente.</div>';
                });
            }, 300); // Debounce delay
        };
    })();
    
    // Update active button state based on URL
    function updateActiveButtons(url) {
        // Reset all buttons to inactive state
        document.querySelectorAll('#btnOriginal, #btnComprobanteDuplicado, #btnPedidoDuplicado, #btnCartonesEliminados').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        });
        
        // Activate corresponding button based on URL
        if (url.includes('comprobantesDuplicados')) {
            document.getElementById('btnComprobanteDuplicado').classList.add('btn-primary');
            document.getElementById('btnComprobanteDuplicado').classList.remove('btn-secondary');
        } else if (url.includes('pedidosDuplicados')) {
            document.getElementById('btnPedidoDuplicado').classList.add('btn-primary');
            document.getElementById('btnPedidoDuplicado').classList.remove('btn-secondary');
        } else if (url.includes('cartonesEliminados')) {
            document.getElementById('btnCartonesEliminados').classList.add('btn-primary');
            document.getElementById('btnCartonesEliminados').classList.remove('btn-secondary');
        } else {
            document.getElementById('btnOriginal').classList.add('btn-primary');
            document.getElementById('btnOriginal').classList.remove('btn-secondary');
        }
    }
    
    // Initialize DataTable when page loads
    initializeDataTable();
    
    // Set up button event listeners for different views
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
    
    // Set up filter button events
    document.getElementById('btnFiltrar').addEventListener('click', aplicarFiltros);
    document.getElementById('btnLimpiar').addEventListener('click', limpiarFiltros);
    
    // Enable filtering with Enter in text fields
    document.querySelectorAll('#nombre, #celular, #serie').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                aplicarFiltros();
            }
        });
    });
    
    // Set up delete clients button and confirmation modal - simplified approach
    const btnBorrarClientes = document.getElementById('btnBorrarClientes');
    if (btnBorrarClientes) {
        btnBorrarClientes.addEventListener('click', function() {
            // Use bootstrap.Modal consistently for reliable modal handling
            const modalElement = document.getElementById('confirmDeleteModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else {
                console.error('Modal element or Bootstrap not found');
            }
        });
    }
    
    // Set up confirmation text validation
    const confirmText = document.getElementById('confirmText');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (confirmText && confirmDeleteBtn) {
        confirmText.addEventListener('input', function() {
            confirmDeleteBtn.disabled = (this.value !== 'BORRAR TODOS LOS CLIENTES');
        });
    }
    
    // Set up delete form submission
    const deleteClientsForm = document.getElementById('deleteClientsForm');
    if (deleteClientsForm) {
        deleteClientsForm.addEventListener('submit', function(event) {
            // Final verification before submitting
            if (confirmText.value !== 'BORRAR TODOS LOS CLIENTES') {
                event.preventDefault();
                alert('Por favor, confirma la acción escribiendo el texto exacto.');
                return false;
            }
            
            // If everything is correct, show loading indicator
            confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...';
            confirmDeleteBtn.disabled = true;
            return true;
        });
    }
    
    // Remove duplicate script execution by removing the second script block entirely
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit to ensure all DOM elements are fully loaded and rendered
    setTimeout(function() {
        // Find the delete button by ID or selector
        const btnBorrarClientes = document.getElementById('btnBorrarClientes') || 
                                  document.querySelector('[data-action="borrar-clientes"]');
        
        if (btnBorrarClientes) {
            console.log('Delete button found');
            
            btnBorrarClientes.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Delete button clicked');
                
                // Find the modal element
                const modalElement = document.getElementById('confirmDeleteModal');
                
                if (!modalElement) {
                    console.error('Modal element not found in DOM');
                    alert('Error: Modal de confirmación no encontrado.');
                    return;
                }
                
                // Try to open modal based on available libraries
                if (typeof bootstrap !== 'undefined') {
                    try {
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                        console.log('Modal opened with Bootstrap');
                    } catch (error) {
                        console.error('Error opening modal with Bootstrap:', error);
                    }
                } else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                    try {
                        $(modalElement).modal('show');
                        console.log('Modal opened with jQuery');
                    } catch (error) {
                        console.error('Error opening modal with jQuery:', error);
                    }
                } else {
                    // Fallback method: manually show the modal
                    try {
                        modalElement.style.display = 'block';
                        modalElement.classList.add('show');
                        document.body.classList.add('modal-open');
                        
                        // Add backdrop
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                        
                        console.log('Modal opened manually');
                        
                        // Handle close buttons within the modal
                        const closeButtons = modalElement.querySelectorAll('[data-dismiss="modal"], .btn-close, .close');
                        closeButtons.forEach(button => {
                            button.addEventListener('click', function() {
                                modalElement.style.display = 'none';
                                modalElement.classList.remove('show');
                                document.body.classList.remove('modal-open');
                                const existingBackdrop = document.querySelector('.modal-backdrop');
                                if (existingBackdrop) {
                                    existingBackdrop.remove();
                                }
                            });
                        });
                    } catch (error) {
                        console.error('Error manually opening modal:', error);
                    }
                }
            });
        } else {
            console.error('Delete button not found in DOM');
            // List all buttons to help identify the correct one
            console.log('Available buttons:', 
                Array.from(document.querySelectorAll('button'))
                    .map(b => ({ id: b.id, text: b.textContent, classes: b.className }))
            );
        }
        
        // Set up confirmation text validation
        const confirmText = document.getElementById('confirmText');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        
        if (confirmText && confirmDeleteBtn) {
            confirmText.addEventListener('input', function() {
                confirmDeleteBtn.disabled = (this.value !== 'BORRAR TODOS LOS CLIENTES');
            });
            
            // Initialize button state
            confirmDeleteBtn.disabled = true;
        }
        
        // Set up delete form submission
        const deleteClientsForm = document.getElementById('deleteClientsForm');
        if (deleteClientsForm) {
            deleteClientsForm.addEventListener('submit', function(event) {
                if (!confirmText || confirmText.value !== 'BORRAR TODOS LOS CLIENTES') {
                    event.preventDefault();
                    alert('Por favor, confirma la acción escribiendo el texto exacto.');
                    return false;
                }
                
                // Show loading indicator
                if (confirmDeleteBtn) {
                    confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...';
                    confirmDeleteBtn.disabled = true;
                }
                
                return true;
            });
        }
    }, 500); // Short delay to ensure DOM is fully loaded
});
    </script>
@endsection