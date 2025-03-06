<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>RIFFY Bingo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/paraelico.png') }}">

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
        h6,
        .paso-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .paso-title {
            color: #fa9044;
            font-weight: bold;
            letter-spacing: 0.5px;
            font-size: 28px;
        }

        p {
            font-size: 18px;
            margin-bottom: 0.8rem;
        }

        .logo-container {
            height: 80px;
            display: flex;
            align-items: center;
        }

        .logo-container img {
            height: 100%;
            width: auto;
            display: block;
        }

        .btn-naranja {
            background-color: #fa9044;
            border-color: #fa9044;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 20px;
        }

        .btn-verde {
            background-color: #00bf63;
            border-color: #00bf63;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 20px;
        }

        .bingo-container {
            background-color: #121212;
            border-radius: 8px;
        }

        .nav-link-custom {
            font-size: 16px;
        }

        @media (min-width: 768px) {
            .bingo-container {
                max-width: 500px;
                margin: 0 auto;
            }

            body {
                font-size: 20px;
            }

            p {
                font-size: 20px;
            }

            .paso-title {
                font-size: 32px;
            }

            .logo-container {
                height: 50px;
            }
        }

        @media (min-width: 992px) {
            .bingo-container {
                max-width: 600px;
            }
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
    <div class="container py-4">
        <div class="bingo-container p-4 text-center">
            <h4 class="paso-title text-center mb-3">
                Tus cartones han sido reservados exitosamente
            </h4>
            <p class="mb-1">
                y están actualmente en proceso de <strong style="color: #fa9044;">REVISIÓN</strong>.
            </p>

            <!-- Botón: Buscar mis cartones -->
            <p class="mt-4 mb-2 fw-bold">Puedes buscar el estado de tus cartones aquí</p>
            <a href="{{ route('cartones.index') }}"
                class="btn btn-naranja text-white fw-bold w-100 py-2"
                style="margin-bottom: 20px;">
                BUSCAR MIS CARTONES
            </a>
            <!-- Información de Whatsapp -->
            <p class="mt-4 fw-bold">
                Toda la información de los sorteos se entregará a través de nuestro grupo de Whatsapp.
            </p>
            <p>
                Si aún no perteneces a un grupo de Whatsapp, ingresa a uno en el botón aquí debajo.
            </p>

            <!-- Botón: Ingresar a grupo de Whatsapp -->
            <a href="{{ $grupoWhatsapp }}"
                class="btn btn-verde text-white fw-bold w-100 py-2"
                style="margin-top: 10px;">
                INGRESAR A GRUPO DE WHATSAPP
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>
</body>

</html>