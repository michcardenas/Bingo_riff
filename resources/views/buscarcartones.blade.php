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

        .estado-archivado {
            color: #FFB700;
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

        /* Estilos para los indicadores de tiempo */
        .tiempo-descarga {
            font-size: 11px;
            display: block;
            margin-top: 4px;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            max-width: fit-content;
        }

        .tiempo-verde {
            background-color: #e8f5e9;
            color: #28a745;
        }

        .tiempo-naranja {
            background-color: #fff3e0;
            color: #ff8c00;
        }

        .tiempo-rojo {
            background-color: #ffdddd;
            color: #ff3333;
        }

        .tiempo-amarillo {
            background-color: #FFF8E1;
            color: #FFB700;
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
            box-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
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
            box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.4);
        }

        /* Estilos para el contenedor de video vertical - FORMATO TELÉFONO */
        .video-vertical-container {
            width: 100%;
            /* Ancho del 95% del contenedor para dejar un pequeño margen */
            max-width: 400px;
            /* Ancho máximo para simular un teléfono */
            height: auto;
            /* Auto para mantener proporción */
            aspect-ratio: 9/16;
            /* Proporción de aspecto de teléfono vertical (16:9 invertido) */
            margin: 0 auto;
            margin-top: 10px;
            margin-bottom: 20px;
            position: relative;
            padding: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .video-vertical-container iframe {
            width: 100%;
            height: 100%;
            border: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        /* Estilos para el indicador de carga */
        #loadingIndicator {
            padding: 6px;
            text-align: center;
            color: #fa9044;
            font-weight: 500;
            font-size: 16px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        /* Spinner para indicar carga */
        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            vertical-align: text-bottom;
            border: .2em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border .75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
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

            .video-vertical-container {
                width: 100%;
                max-width: 467px;
                /* Ligeramente más estrecho en móviles */
                min-height: 600px;
                /* Altura mínima para asegurar que se vea bien */
            }
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

            .video-vertical-container {
                width: 100%;
                max-width: 467px;
                /* Ligeramente más estrecho en móviles */
                min-height: 600px;
                /* Altura mínima para asegurar que se vea bien */
            }
        }

        @media (min-width: 768px) {
            .video-vertical-container {
                max-width: 458px;
                /* Tamaño ideal para simular un teléfono */
                min-height: 650px;
                /* Altura mínima para tablets */
            }
        }

        @media (min-width: 992px) {
            .video-vertical-container {
                max-width: 568px;
                /* Ligeramente más ancho en desktop */
                min-height: 700px;
                /* Altura mínima para desktop */
            }
        }

        @media (max-width: 480px) {


            .video-vertical-container {
                width: 103%;
                /* Casi todo el ancho en móviles pequeños */
                max-width: 400px;
                /* Limitado para mantener proporción */
                min-height: 550px;
                /* Altura mínima para móviles pequeños */
            }
        }
    </style>

    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1291210271938936');
        fbq('track', 'Purchase');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=1291210271938936&ev=PageView&noscript=1" /></noscript>

</head>

<body>
    @php
    // Obtener los enlaces de la base de datos
    $enlaces = App\Models\Enlace::first() ?? new App\Models\Enlace();
    $grupoWhatsapp = $enlaces->grupo_whatsapp ?? '#'; // Valor por defecto
    $video2 = $enlaces->video_2 ?? '#'; // Valor por defecto para el video 2

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
                    <input type="text" class="form-control" id="celular" name="celular"
                        value="{{ session('celular_comprador') ?? request()->old('celular') }}"
                        placeholder="Ejemplo: 3234095109" required>
                    <div class="form-text">Ingresa el mismo número de celular con el que reservaste tus cartones.</div>
                </div>
                <button type="submit" class="btn btn-orange">BUSCAR MIS CARTONES</button>
            </form>

            @if(isset($cartones) && count($cartones) > 0)
            @php
            $cartonesRechazados = 0;
            foreach($cartones as $carton) {
            if($carton['estado'] == 'rechazado') {
            $cartonesRechazados++;
            }
            }
            @endphp

            @if($cartonesRechazados > 0)
            <div class="alert alert-warning mt-3">
                <a href="#" class="contactar-admin" data-carton="{{ $carton['numero'] }}" data-whatsapp="{{ $numeroContacto }}" style="text-decoration: none;">
                    <i class="fab fa-whatsapp text-success"></i>
                </a>
                Su {{ $cartonesRechazados == 1 ? 'cartón ha' : 'cartones han' }} sido {{ $cartonesRechazados == 1 ? 'rechazado' : 'rechazados' }}, contacta al administrador por medio del botón de Whatsapp.
            </div>
            @endif

            <!-- Reemplaza la parte de la tabla en la vista -->
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
                    <tr class="carton-row {{ $carton['estado'] }}">
                        <td>{{ $carton['nombre'] ?? 'Usuario' }}</td>
                        <td data-bingo-id="{{ $carton['bingo_id'] ?? '' }}" data-bingo-estado="{{ $carton['bingo_estado'] ?? '' }}">{{ $carton['bingo_nombre'] ?? 'Sin asignar' }}</td>
                        <td>{{ $carton['numero'] }}</td>
                        <td class="carton-estado" data-estado="{{ $carton['estado'] }}">
                            @if($carton['estado'] == 'aprobado')
                            <span class="estado-aprobado">Aprobado</span>
                            <a href="{{ route('cartones.descargar', $carton['numero']) }}" class="btn btn-sm ms-2 download-link" title="Descargar cartón" style="background-color: #00bf63; color: white;">
                                Descargar
                            </a>
                            @elseif($carton['estado'] == 'revision')
                            <span class="estado-revision">Disponible</span>
                            <a href="{{ route('cartones.descargar', $carton['numero']) }}" class="btn btn-sm ms-2 download-link" title="Descargar cartón" style="background-color: #00bf63; color: white;">
                                Descargar
                            </a>
                            @elseif($carton['estado'] == 'rechazado')
                            <span class="estado-rechazado">Rechazado</span>
                            <a href="#" class="ms-2 contactar-admin" data-carton="{{ $carton['numero'] }}" data-whatsapp="{{ $numeroContacto }}">
                                <i class="fab fa-whatsapp text-success"></i>
                            </a>
                            @else
                            <span class="estado-desconocido">{{ ucfirst($carton['estado'] ?? 'Desconocido') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @elseif(isset($cartones) && count($cartones) == 0)
            <div class="alert alert-warning mt-3">
                <a href="#" class="contactar-admin" data-whatsapp="{{ $numeroContacto }}" style="text-decoration: none;">
                    <i class="fab fa-whatsapp text-success"></i>
                </a>
                No se encontraron cartones asociados. Si crees que esto es un error, contacta al administrador por medio del botón de Whatsapp.
            </div>

            @endif

            <!-- Sección de ayuda -->
            <div class="help-container">
                <h2 class="help-title">¿Como descargar tus cartones y jugar?</h2>
                @if($video2 && $video2 != '#')
                <div class="video-vertical-container">
                    <iframe
                        src="{{ str_replace('watch?v=', 'embed/', $video2) }}"
                        title="Tutorial de descarga y juego"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
                @else
                <span class="video-link">video 2 aquí</span>
                @endif
            </div>

            <!-- Botón flotante de WhatsApp que usa el teléfono de atención al cliente -->
            @if($enlaces->mostrar_boton_whatsapp ?? true)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $telefonoAtencion) }}" class="whatsapp-float" target="_blank">
                <i class="fab fa-whatsapp"></i>
            </a>
            @endif

            <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Inicializar verificación de cartones con indicador visual
                    verificarEstadoBingos();

                    // Agregar evento a los botones de contactar admin
                    const contactarBtns = document.querySelectorAll('.contactar-admin');
                    contactarBtns.forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const cartonNumero = this.getAttribute('data-carton');
                            const adminWhatsapp = this.getAttribute('data-whatsapp') || "3235903774";
                            let mensaje = '';

                            // Determinar qué tipo de alerta contiene este botón para personalizar el mensaje
                            if (this.closest('.alert-warning') && this.closest('.alert-warning').textContent.includes('rechazado')) {
                                mensaje = `Hola, necesito ayuda con mi cartón rechazado #${cartonNumero}.`;
                            } else if (this.closest('.alert-warning') && this.closest('.alert-warning').textContent.includes('No se encontraron cartones')) {
                                mensaje = `Hola, no puedo encontrar mis cartones asociados #${cartonNumero}. ¿Podrías ayudarme?`;
                            } else if (this.closest('.alert-info')) {
                                mensaje = `Hola, quisiera consultar sobre el estado de aprobación de mis cartones.`;
                            } else {
                                // Mensaje por defecto en caso de que no coincida con ninguno de los anteriores
                                mensaje = `Hola, necesito información sobre mi cartón.`;
                            }

                            // Limpiar número de WhatsApp de cualquier carácter no numérico
                            const whatsappNumero = adminWhatsapp.replace(/[^0-9]/g, '');
                            const whatsappUrl = `https://wa.me/${whatsappNumero}?text=${encodeURIComponent(mensaje)}`;
                            window.open(whatsappUrl, '_blank');
                        });
                    });
                });

                /**
                 * Verifica el estado de los bingos asociados a los cartones
                 * Prioriza mostrar el estado "archivado" sobre cualquier otro estado
                 */
                function verificarEstadoBingos() {
                    const tablaCartones = document.querySelector('.cartones-table');
                    if (!tablaCartones) return;

                    const filas = tablaCartones.querySelectorAll('tbody tr');

                    filas.forEach(function(fila) {
                        const celdaBingo = fila.querySelector('td:nth-child(2)');
                        const celdaEstado = fila.querySelector('td:nth-child(4)');

                        if (!celdaBingo || !celdaEstado) return;

                        const bingoId = celdaBingo.getAttribute('data-bingo-id');
                        const nombreBingo = celdaBingo.textContent.trim();

                        // Guardar estado original del cartón
                        const estadoOriginal = celdaEstado.getAttribute('data-estado');

                        // Verificar el bingo para todos los cartones, sin importar el estado
                        if (bingoId) {
                            consultarEstadoBingoPorId(bingoId)
                                .then(infoBingo => procesarEstadoBingo(infoBingo, celdaEstado, estadoOriginal));
                        } else if (nombreBingo && nombreBingo !== 'Sin asignar') {
                            consultarEstadoBingo(nombreBingo)
                                .then(infoBingo => procesarEstadoBingo(infoBingo, celdaEstado, estadoOriginal));
                        }
                    });
                }

                function procesarEstadoBingo(infoBingo, celdaEstado, estadoOriginal) {
                    if (!infoBingo) return;

                    const bingoEstado = infoBingo.estado ? infoBingo.estado.toLowerCase() : '';
                    const estadoSpan = celdaEstado.querySelector('span[class^="estado-"]');
                    const enlaceDescarga = celdaEstado.querySelector('.download-link');
                    const enlaceWhatsapp = celdaEstado.querySelector('.contactar-admin');

                    // Verificar si el bingo está archivado (no debería aparecer, pero por si acaso)
                    if (bingoEstado === 'archivado') {
                        // 1. Cambiar el texto y la clase del estado
                        if (estadoSpan) {
                            estadoSpan.textContent = 'Archivado';
                            estadoSpan.className = '';
                            estadoSpan.classList.add('estado-archivado');
                        }

                        // 2. Ocultar enlace de WhatsApp para cartones rechazados
                        if (enlaceWhatsapp) {
                            enlaceWhatsapp.style.display = 'none';
                        }

                        // 3. Para cartones aprobados, cambiar el botón de descarga
                        if (enlaceDescarga) {
                            enlaceDescarga.setAttribute('data-original-href', enlaceDescarga.href);
                            enlaceDescarga.href = 'javascript:void(0)';
                            enlaceDescarga.textContent = 'Archivado';
                            enlaceDescarga.style.backgroundColor = '#808080'; // Gris para archivado
                            enlaceDescarga.title = 'Este cartón pertenece a un bingo archivado y no puede ser descargado';
                            enlaceDescarga.onclick = function(e) {
                                e.preventDefault();
                                alert('Este cartón pertenece a un bingo archivado y no puede ser descargado.');
                            };
                        }

                        // 4. Agregar indicador visual de archivado
                        agregarIndicadorVisual(celdaEstado, 'archivado');

                        return;
                    }

                    // Para cartones aprobados, asegurarse de que sean descargables
                    if (estadoOriginal === 'aprobado' && enlaceDescarga) {
                        // Verificar si hay que restaurar el enlace original
                        const originalHref = enlaceDescarga.getAttribute('data-original-href');
                        if (originalHref) {
                            enlaceDescarga.href = originalHref;
                            enlaceDescarga.removeAttribute('data-original-href');
                            enlaceDescarga.textContent = 'Descargar';
                            enlaceDescarga.style.backgroundColor = '#00bf63'; // Verde de aprobado
                            enlaceDescarga.style.color = 'white';
                            enlaceDescarga.title = 'Descargar cartón';
                            enlaceDescarga.onclick = null; // Eliminar cualquier onclick previo
                        }

                        // Quitar cualquier indicador visual previo
                        const indicadorExistente = celdaEstado.querySelector('.tiempo-descarga');
                        if (indicadorExistente) {
                            indicadorExistente.remove();
                        }
                    }
                }

                /**
                 * Consulta el estado de un bingo por su nombre
                 */
                function consultarEstadoBingo(nombreBingo) {
                    const url = `/api/bingos/by-name?nombre=${encodeURIComponent(nombreBingo)}`;

                    return fetch(url)
                        .then(function(response) {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error("Error al consultar bingo por nombre:", text);
                                    throw new Error('Error al consultar bingo');
                                });
                            }
                            return response.json();
                        })
                        .catch(function(error) {
                            console.error("Error detallado:", error);
                            return null;
                        });
                }

                /**
                 * Consulta el estado de un bingo por su ID
                 */
                function consultarEstadoBingoPorId(bingoId) {
                    const url = `/api/bingos/${bingoId}`;

                    return fetch(url)
                        .then(function(response) {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error("Error al consultar bingo por ID:", text);
                                    throw new Error('Error al consultar bingo');
                                });
                            }
                            return response.json();
                        })
                        .catch(function(error) {
                            console.error("Error detallado:", error);
                            return null;
                        });
                }
            </script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const celularInput = document.getElementById('celular');
    const searchForm = document.getElementById('searchForm');
    let typingTimer;
    const doneTypingInterval = 1200; // tiempo en ms (1.2 segundos)
    let lastSubmittedValue = '';
    let isFirstLoad = true;
    let hasSearched = false; // Bandera para controlar si ya se ha realizado una búsqueda
    
    // Al cargar la página, verificamos si ya hay un valor en el campo
    const hasCelularValue = celularInput.value.length >= 10;
    const hasResults = document.querySelector('.cartones-table') !== null;
    
    // Si hay un mensaje de "no se encontraron resultados", consideramos que ya se ha realizado la búsqueda
    const noResultsMessage = document.querySelector('.alert-info, .alert-warning');
    if (noResultsMessage && noResultsMessage.textContent.includes('No se encontraron')) {
        hasSearched = true;
        lastSubmittedValue = celularInput.value; // Guardar el valor que ya fue buscado
    }
    
    // Solo autoenviar en la carga inicial si hay valor pero no hay resultados y no se ha buscado aún
    if (hasCelularValue && !hasResults && isFirstLoad && !hasSearched) {
        // Pequeño retraso para asegurarnos que la página esté completamente cargada
        setTimeout(() => {
            lastSubmittedValue = celularInput.value;
            searchForm.submit();
        }, 500);
        isFirstLoad = false;
    }
    
    // Evento para detectar cuando el usuario está escribiendo
    celularInput.addEventListener('keyup', function() {
        // Limpiar el timer existente
        clearTimeout(typingTimer);
        
        // Solo activar si hay 10+ dígitos Y es diferente al último valor enviado
        const currentValue = this.value;
        if (currentValue.length >= 10 && currentValue !== lastSubmittedValue) {
            // Esperar que el usuario termine de escribir
            typingTimer = setTimeout(function() {
                // Actualizar el último valor enviado para evitar envíos repetidos
                lastSubmittedValue = currentValue;
                
                // Mostrar indicador visual
                const loadingIndicator = document.createElement('div');
                loadingIndicator.id = 'loadingIndicator';
                loadingIndicator.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span> Buscando...';
                loadingIndicator.style.marginTop = '10px';
                
                // Verificar si ya existe un indicador y eliminarlo
                const existingIndicator = document.getElementById('loadingIndicator');
                if (existingIndicator) {
                    existingIndicator.remove();
                }
                
                // Agregar el indicador antes del botón de búsqueda
                const submitButton = searchForm.querySelector('button[type="submit"]');
                submitButton.parentNode.insertBefore(loadingIndicator, submitButton);
                
                // Enviar el formulario después de un breve retraso
                setTimeout(() => {
                    searchForm.submit();
                }, 300);
            }, doneTypingInterval);
        }
    });
    
    // Cancelar el timer si el usuario sigue escribiendo
    celularInput.addEventListener('keydown', function() {
        clearTimeout(typingTimer);
    });
    
    // Guardar el hecho de que se realizó una búsqueda (agregar esto al final del script)
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            hasSearched = true;
        });
    }
});
</script>
            
</body>

</html>