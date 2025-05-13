<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>RIFFY Bingo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/RiffyLogo.png') }}">

    <!-- Bootstrap CSS (puedes usar Tailwind o tu framework preferido) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.3.3/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">


    <style>
        body {
            background-color: #000000;
            color: #fff;
        }

        .navbar-admin {
            background-color: #00bf63;
            color: #ffffff;
        }

       
        /* Estilos para filas destacadas */
        .duplicated-comprobante {
            background-color: rgba(220, 53, 69, 0.7) !important;
        }

        .duplicated-price {
            background-color: rgba(255, 193, 7, 0.5) !important;
        }

        /* Ajustes para botones */
        .dt-buttons {
            margin-bottom: 15px;
        }

        /* Ajuste para los filtros personalizados */
        #custom-filters {
            margin-bottom: 20px;
        }
   
        <style>
    /* Ajusta tamaño de la paginación */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 2px 6px !important;
        font-size: 0.8rem !important;
        margin: 0 2px !important;
        border-radius: 0.25rem;
    }

    /* Quita el "peso" de los botones activos */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0d6efd !important;
        color: #fff !important;
    }

    /* Centrar la paginación */
    .dataTables_wrapper .dataTables_paginate {
        text-align: center !important;
        margin-top: 15px;
    }
    .dataTables_wrapper .paginate_button i {
    font-size: 0.75rem;
    vertical-align: middle;
}

</style>

    </style>
</head>

<body>
    <!-- Navbar de administración -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin">
        <div class="container">
            <a href="{{ route('home') }}">
                <img src="{{ asset('images/RiffyLogo.png') }}" alt="RIFFY Bingo" id="riffy-logo" style="height: 90px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('bingos.index') }}">Crear Bingo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('reservas.index') }}">Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('enlaces.edit') }}">Enlaces</a>
                    </li>
                    
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('home') }}">Salir</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="container my-4 container-xl-custom">
        @yield('content')
    </div>

    <!-- Bootstrap JS (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script para inicializar DataTables (colocar al final del documento, justo antes del </body>) -->
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


 
</body>

</html>