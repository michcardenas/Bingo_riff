<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>RIFFY Bingo - Descargar Mis Cartones</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 (CDN) -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        p {
            font-size: 18px;
            margin-bottom: 0.8rem;
        }

        .logo-container {
            height: 50px;
            display: flex;
            align-items: center;
        }

        .logo-container img {
            height: 100%;
            width: auto;
            display: block;
        }

        .main-title {
            color: #fa9044;
            font-weight: bold;
            font-size: 28px;
            text-align: center;
            margin: 20px 0;
        }

        .btn-orange {
            background-color: #fa9044;
            border-color: #fa9044;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
            margin: 15px 0;
            padding: 10px;
        }

        .form-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .form-text {
            color: #aaa;
            font-size: 14px;
            margin-top: 5px;
        }

        .header {
            background-color: #00bf63;
            padding: 10px 0;
        }

        .nav-link {
            color: white;
            font-weight: 500;
            padding: 5px 15px;
            font-family: 'Montserrat', sans-serif;
        }

        .cartones-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .cartones-table th {
            background-color: #666;
            color: white;
            padding: 8px;
            text-align: left;
        }

        .cartones-table td {
            padding: 8px;
            border-bottom: 1px solid #444;
        }

        .estado-aprobado {
            color: #00bf63;
            font-weight: bold;
        }

        .estado-revision {
            color: #fa9044;
            font-weight: bold;
        }

        .estado-rechazado {
            color: #ff3333;
            font-weight: bold;
        }

        .download-icon {
            color: white;
        }

        .nav-link-custom {
            font-size: 16px;
        }


        .help-container {
            text-align: center;
            margin-top: 30px;
        }

        .help-title {
            color: #fa9044;
            font-weight: bold;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .video-link {
            color: #fa9044;
            font-size: 20px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
    // Obtener los enlaces de la base de datos
    $enlaces = App\Models\Enlace::first() ?? new App\Models\Enlace();
    $grupoWhatsapp = $enlaces->grupo_whatsapp ?? '#'; // Valor por defecto
    $video2 = $enlaces->video_2 ?? '#'; // Valor por defecto para el video 2
    @endphp

    <!-- Cabecera -->
    <header class="py-2 border-bottom border-secondary" style="background-color: #00bf63;">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Logo -->
            <div class="logo-container">
                <!-- Ajusta la ruta de tu logo según tu proyecto -->
                <img src="{{ asset('images/RiffyLogo.png') }}" alt="Logo">
            </div>
            <!-- Enlaces -->
            <div>
                <a href="{{ route('cartones.index') }}" class="text-white text-decoration-none me-3 nav-link-custom">Buscar mi cartón</a>
                <a href="{{ $grupoWhatsapp }}" class="text-white text-decoration-none nav-link-custom d-none d-md-inline">Grupo Whatsapp</a>
                <a href="#" class="text-white text-decoration-none nav-link-custom d-inline d-md-none">Grupo WA</a>
            </div>
        </div>
    </header>


    <!-- Contenido principal -->
    <div class="container">
        <div class="form-container">
            <h1 class="main-title">Descargar mis cartones</h1>

            <!-- Mensajes de alerta -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Formulario de búsqueda -->
            <form id="searchForm" action="{{ route('cartones.buscar') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="telefono" class="form-label">Número de Celular</label>
                    <input type="text" class="form-control" id="celular" name="celular" placeholder="Ejemplo: 3234095109" required>
                    <div class="form-text">Ingresa el mismo número de celular con el que reservaste tus cartones.</div>
                </div>
                <button type="submit" class="btn btn-orange">BUSCAR MIS CARTONES</button>
            </form>

            <!-- Resultados de la búsqueda -->
            @if(isset($cartones) && count($cartones) > 0)
            <table class="cartones-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Bingo</th>
                        <th>#Cartón</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartones as $carton)
                    <tr>
                        <td>{{ $carton['nombre'] ?? 'Usuario' }}</td>
                        <td>{{ $carton['bingo_nombre'] ?? 'Sin asignar' }}</td>
                        <td>{{ $carton['numero'] }}</td>
                        <td>
                            @if($carton['estado'] == 'aprobado')
                            <span class="estado-aprobado">Aprobado</span>
                            <a href="{{ route('cartones.descargar', $carton['numero']) }}" class="ms-2">
                                <i class="fas fa-download download-icon"></i>
                            </a>
                            @elseif($carton['estado'] == 'revision')
                            <span class="estado-revision">Revisión</span>
                            @else
                            <span class="estado-rechazado">Rechazado</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @elseif(isset($cartones) && count($cartones) == 0)
            <div class="alert alert-warning mt-3">
                No se encontraron cartones para este número de celular. Verifica el número ingresado o contacta al administrador.
            </div>
            @endif

            <!-- Sección de ayuda -->
            <div class="help-container">
                <h2 class="help-title">¿Como descargar tus cartones y jugar?</h2>
                @if($video2 && $video2 != '#')
                <div class="ratio ratio-16x9 mt-2" style="max-width: 640px; margin: 0 auto;">
                    <iframe
                        src="{{ str_replace('watch?v=', 'embed/', $video2) }}"
                        title="Tutorial de descarga y juego"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
                @else
                <span class="video-link">video 2 aquí</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>