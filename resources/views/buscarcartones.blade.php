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
    width: 80px;
    height: 80px;
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

/* Efecto de pulso para llamar más la atención */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
    }
    70% {
        box-shadow: 0 0 0 15px rgba(37, 211, 102, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
    }
}

.whatsapp-float {
    animation: pulse 2s infinite;
}

/* Estilos para el contenedor de video vertical - FORMATO TELÉFONO */
.video-vertical-container {
            width: 100%; /* Ancho del 95% del contenedor para dejar un pequeño margen */
            max-width: 400px; /* Ancho máximo para simular un teléfono */
            height: auto; /* Auto para mantener proporción */
            aspect-ratio: 9/16; /* Proporción de aspecto de teléfono vertical (16:9 invertido) */
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
                max-width: 467px; /* Ligeramente más estrecho en móviles */
                min-height: 600px; /* Altura mínima para asegurar que se vea bien */
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
                max-width: 467px; /* Ligeramente más estrecho en móviles */
                min-height: 600px; /* Altura mínima para asegurar que se vea bien */
            }
        }

        @media (min-width: 768px) {
            .video-vertical-container {
                max-width: 458px; /* Tamaño ideal para simular un teléfono */
                min-height: 650px; /* Altura mínima para tablets */
            }
        }

        @media (min-width: 992px) {            
            .video-vertical-container {
                max-width: 568px; /* Ligeramente más ancho en desktop */
                min-height: 700px; /* Altura mínima para desktop */
            }
        }

        @media (max-width: 480px) {
            

            .video-vertical-container {
                width: 103%; /* Casi todo el ancho en móviles pequeños */
                max-width: 400px; /* Limitado para mantener proporción */
                min-height: 550px; /* Altura mínima para móviles pequeños */
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
                    <input type="text" class="form-control" id="celular" name="celular" placeholder="Ejemplo: 3234095109" required>
                    <div class="form-text">Ingresa el mismo número de celular con el que reservaste tus cartones.</div>
                </div>
                <button type="submit" class="btn btn-orange">BUSCAR MIS CARTONES</button>
            </form>
            <!-- Mensaje de notificación de WhatsApp -->
            <div class="alert alert-info mt-3 mb-3 text-center">
                <i class="fab fa-whatsapp me-2"></i> Informaremos por el grupo de Whatsapp cuando los cartones esten aprobados para su descarga.
            </div>

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
                Su {{ $cartonesRechazados == 1 ? 'cartón ha' : 'cartones han' }} sido {{ $cartonesRechazados == 1 ? 'rechazado' : 'rechazados' }}, contacta al administrador por medio del botón de Whatsapp.
            </div>
            @endif

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
                        <td data-bingo-id="{{ $carton['bingo_id'] ?? '' }}">{{ $carton['bingo_nombre'] ?? 'Sin asignar' }}</td>
                        <td>{{ $carton['numero'] }}</td>
                        <td class="carton-estado" data-estado="{{ $carton['estado'] }}">
                            @if($carton['estado'] == 'aprobado')
                            <span class="estado-aprobado">Aprobado</span>
                            <a href="{{ route('cartones.descargar', $carton['numero']) }}" class="ms-2 download-link">
                                <i class="fas fa-download download-icon"></i>
                            </a>
                            @elseif($carton['estado'] == 'revision')
                            <span class="estado-revision">Revisión</span>
                            @elseif($carton['estado'] == 'rechazado')
                            <span class="estado-rechazado">Rechazado</span>
                            <a href="#" class="ms-2 contactar-admin" data-carton="{{ $carton['numero'] }}" data-whatsapp="{{ $telefonoAtencion }}">
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
    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $telefonoAtencion) }}" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

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
                    const mensaje = `Hola, necesito ayuda con mi cartón rechazado #${cartonNumero}.`;
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

        /**
         * Procesa la información del bingo y actualiza la visualización del cartón
         * Prioriza el estado "archivado" sobre cualquier otro estado
         */
        function procesarEstadoBingo(infoBingo, celdaEstado, estadoOriginal) {
            if (!infoBingo) return;
            
            // Verificar si el bingo está archivado (esta condición tiene prioridad)
            if (infoBingo.estado && infoBingo.estado.toLowerCase() === 'archivado') {
                // Obtener elementos necesarios
                const estadoSpan = celdaEstado.querySelector('span[class^="estado-"]');
                
                // 1. Cambiar el texto y la clase del estado
                if (estadoSpan) {
                    estadoSpan.textContent = 'Archivado';
                    estadoSpan.className = '';
                    estadoSpan.classList.add('estado-archivado');
                }
                
                // 2. Si hay un enlace de WhatsApp para cartones rechazados, ocultarlo
                const enlaceWhatsapp = celdaEstado.querySelector('.contactar-admin');
                if (enlaceWhatsapp) {
                    enlaceWhatsapp.style.display = 'none';
                }
                
                // 3. Para cartones aprobados, cambiar el icono de descarga por un candado
                const iconoDescarga = celdaEstado.querySelector('.fa-download');
                if (iconoDescarga) {
                    iconoDescarga.classList.remove('fa-download');
                    iconoDescarga.classList.add('fa-lock');
                    
                    // Deshabilitar el enlace de descarga
                    const enlaceDescarga = iconoDescarga.closest('a');
                    if (enlaceDescarga) {
                        enlaceDescarga.setAttribute('data-original-href', enlaceDescarga.href);
                        enlaceDescarga.href = 'javascript:void(0)';
                        enlaceDescarga.title = 'Este cartón pertenece a un bingo archivado y no puede ser descargado';
                        enlaceDescarga.onclick = function(e) {
                            e.preventDefault();
                            alert('Este cartón pertenece a un bingo archivado y no puede ser descargado.');
                        };
                    }
                }
                
                // 4. Agregar indicador visual de archivado
                agregarIndicadorVisual(celdaEstado, 'archivado');
                
                // Terminar aquí - el estado archivado tiene prioridad sobre cualquier otro
                return;
            }
            
            // Si no está archivado, verificar tiempos para cartones aprobados
            if (estadoOriginal === 'aprobado' && infoBingo.estado && infoBingo.estado.toLowerCase() !== 'abierto') {
                const iconoDescarga = celdaEstado.querySelector('.fa-download');
                const enlaceDescarga = iconoDescarga ? iconoDescarga.closest('a') : null;
                
                if (enlaceDescarga) {
                    const fechaCierre = new Date(infoBingo.fecha_cierre || infoBingo.updated_at);
                    const ahora = new Date();
                    const diferenciaMs = ahora - fechaCierre;
                    const diferenciaHoras = diferenciaMs / (1000 * 60 * 60);

                    if (diferenciaHoras > 24) {
                        // Han pasado más de 24 horas, deshabilitar descarga
                        enlaceDescarga.setAttribute('data-original-href', enlaceDescarga.href);
                        enlaceDescarga.href = 'javascript:void(0)';
                        enlaceDescarga.title = 'Este cartón ya no está disponible para descarga';
                        enlaceDescarga.onclick = function(e) {
                            e.preventDefault();
                            alert('El período de descarga ha expirado. Los cartones solo están disponibles por 24 horas después de que el bingo cierra.');
                        };
                        
                        iconoDescarga.classList.remove('fa-download');
                        iconoDescarga.classList.add('fa-lock');
                        
                        agregarIndicadorVisual(celdaEstado, 'expirado');
                    } else {
                        // Mostrar tiempo restante
                        const horasRestantes = Math.round(24 - diferenciaHoras);
                        enlaceDescarga.title = `Disponible por ${horasRestantes} horas más`;
                        
                        agregarIndicadorVisual(celdaEstado, 'tiempo-restante', horasRestantes);
                    }
                }
            }
        }

        /**
         * Agrega un indicador visual del estado de disponibilidad del cartón
         */
        function agregarIndicadorVisual(celdaEstado, tipo, horasRestantes = null) {
            // Eliminar indicador existente si hay uno
            const indicadorExistente = celdaEstado.querySelector('.tiempo-descarga');
            if (indicadorExistente) {
                indicadorExistente.remove();
            }

            // Crear el nuevo indicador
            const indicador = document.createElement('span');
            indicador.className = 'tiempo-descarga';

            if (tipo === 'expirado') {
                indicador.textContent = 'Descarga expirada';
                indicador.classList.add('tiempo-rojo');
            } else if (tipo === 'archivado') {
                indicador.textContent = 'No disponible';
                indicador.classList.add('tiempo-amarillo');
            } else if (tipo === 'tiempo-restante') {
                if (horasRestantes < 6) {
                    indicador.classList.add('tiempo-rojo');
                    indicador.textContent = `Expira en ${horasRestantes}h`;
                } else if (horasRestantes < 12) {
                    indicador.classList.add('tiempo-naranja');
                    indicador.textContent = `Expira en ${horasRestantes}h`;
                } else {
                    indicador.classList.add('tiempo-verde');
                    indicador.textContent = `Disponible ${horasRestantes}h más`;
                }
            }

            // Agregar el indicador a la celda
            celdaEstado.appendChild(indicador);
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
</body>

</html>