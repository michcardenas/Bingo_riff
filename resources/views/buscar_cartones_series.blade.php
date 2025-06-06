<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>RIFFY Bingo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/RiffyLogo.png') }}">

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

        .whatsapp-float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 40px;
            right: 40px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50px;
            text-align: center;
            font-size: 40px;
            box-shadow: 3px 3px 6px rgba(0,0,0,0.3);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .whatsapp-float:hover {
            background-color: #128C7E;
            color: white;
            transform: scale(1.1);
            box-shadow: 4px 4px 10px rgba(0,0,0,0.4);
        }

        /* Media query para dispositivos móviles */
        @media screen and (max-width: 767px) {
            .nav-link-custom {
            font-size: 14px;
        }

            .whatsapp-float {
                width: 65px;
                height: 65px;
                bottom: 30px;
                right: 30px;
                font-size: 35px;
            }
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
    
    // Usar el nuevo campo telefono_atencion con respaldo al número de contacto antiguo
    $telefonoAtencion = $enlaces->telefono_atencion ?? ($enlaces->numero_contacto ?? '3235903774');
    @endphp
    <!-- Cabecera -->
    <header class="py-2 border-bottom border-secondary" style="background-color: #00bf63;">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Logo -->
            <div class="logo-container">
                <a href="https://bingoriffy.com">
                    <img src="{{ asset('images/RiffyLogo.png') }}" alt="RIFFY Bingo" id="riffy-logo" style="height: 70px;">
                </a>
            </div>
            <!-- Enlaces -->
            <div>
                <a href="{{ route('home') }}" class="text-white text-decoration-none me-3 nav-link-custom">Comprar</a>
                <a href="{{ route('cartones.index') }}" class="text-white text-decoration-none me-3 nav-link-custom">Buscar mi cartón</a>
                <a href="{{ route('cartones.serie') }}" class="text-white text-decoration-none me-3 nav-link-custom">Buscar numero de serie</a>

                @if($enlaces->grupo_whatsapp)
                <a href="{{ $enlaces->grupo_whatsapp }}" target="_blank" class="text-white text-decoration-none nav-link-custom d-none d-md-inline">Grupo Whatsapp</a>
                <a href="{{ $enlaces->grupo_whatsapp }}" target="_blank" class="text-white text-decoration-none nav-link-custom d-inline d-md-none">Grupo WA</a>
                @else
                <a href="#" class="text-white text-decoration-none nav-link-custom d-none d-md-inline">Grupo Whatsapp</a>
                <a href="#" class="text-white text-decoration-none nav-link-custom d-inline d-md-none">Grupo WA</a>
                @endif
            </div>
        </div>
    </header>

    <div class="container mt-4">
    <h2 class="text-center mb-4">Buscar Cartones por Celular</h2>

    <form action="{{ route('cartones.buscarserie') }}" method="POST" class="mb-4">
        @csrf
        <div class="row justify-content-center">
            <div class="col-md-6">
                <input type="text" name="celular" class="form-control" placeholder="Número de celular" value="{{ old('celular', $celular ?? '') }}" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </div>
    </form>

    @if(isset($reservas) && count($reservas))
        <h5 class="mb-3">Resultados para: <strong>{{ $celular }}</strong></h5>
        @if(isset($seriesDetalladas) && count($seriesDetalladas))
    <h4 class="mt-4">Series por Cartón</h4>
    <table class="table table-bordered">
        <thead class="table-secondary">
            <tr>
                <th>#</th>
                <th>Número de Hoja</th>
                <th>Series</th>
            </tr>
        </thead>
        <tbody>
            @foreach($seriesDetalladas as $serie)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $serie->carton }}</td>
                    <td>{{ implode(', ', $serie->series ?? []) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

    @elseif(isset($reservas))
        <div class="alert alert-warning text-center">
            No se encontraron reservas con ese número de celular.
        </div>
    @endif
</div>


    <!-- Botón flotante de WhatsApp que usa el teléfono de atención al cliente -->
@if($enlaces->mostrar_boton_whatsapp ?? true)
    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $telefonoAtencion) }}" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
@endif

    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>
</body>

</html>