<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>RIFFY Bingo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/RiffyLogo.png') }}">

    <!-- Bootstrap CSS (puedes usar Tailwind o tu framework preferido) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000000;
            color: #fff;
        }

        .navbar-admin {
            background-color: #00bf63;
            color: #ffffff;
        }
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
    <div class="container my-4">
        @yield('content')
    </div>

    <!-- Bootstrap JS (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>