<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>RIFFY Bingo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/RiffyLogo.png') }}">
    <!-- Add this in the <head> section -->
<meta name="csrf-token" content="{{ csrf_token() }}">


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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />


    <script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '1291210271938936');
  fbq('track', 'PageView');
</script>

<noscript>
  <img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=1291210271938936&ev=PageView&noscript=1"/>
</noscript>
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

        .text-verde {
            color: #00bf63;
        }

        .text-amarillo {
            color: #FFD700;
            font-size: 20px;
        }
        
        .text-naranja {
            color: #fa9044;
        }

        .bingo-container {
            background-color: #121212;
            border-radius: 8px;
            padding: 0; /* Quitamos padding para maximizar espacio */
        }

        .form-control {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            padding: 10px;
        }

        .form-label {
            font-size: 18px;
        }

        .nav-link-custom {
            font-size: 16px;
        }

        .btn {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 18px;
        }

        .border-naranja {
            border: 1px dashed #ffffff !important;
        }

        .etiqueta-archivo {
            cursor: pointer;
            color: #ffffff;
            font-size: 18px;
        }

        /* Contenedor de vista previa con grid para agrupar en cuadraditos */
        #previewContainer {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        /* Imagenes de vista previa en cuadraditos */
        .img-preview {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border: 2px solid #fa9044;
            border-radius: 5px;
            position: relative;
        }
        
        /* Estilo para bingo cerrado */
        .bingo-cerrado-container {
            background-color: #121212;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            display: none;
        }

        .bingo-cerrado-titulo {
            color: #fa9044;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.2;
            text-align: center;
        }

        .bingo-cerrado-fecha {
            color: #ffffff;
            font-size: 20px;
            margin-bottom: 1rem;
        }

        .bingo-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
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

        /* Estilos para notificaciones */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 350px;
            z-index: 1050;
        }

        .notification {
            background-color: #FFEEEE;
            border-left: 4px solid #FF0000;
            color: #FF0000;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-weight: bold;
            animation: slideIn 0.5s ease-out;
        }

        /* Estilos más específicos para notificaciones de éxito */
        .notification.success-notification {
            background-color: #EEFFEE !important;
            border-left: 4px solid #28a745 !important;
            color: #28a745 !important;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification-title {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .notification-message {
            margin: 0;
        }

        .notification-close {
            float: right;
            background: none;
            border: none;
            color: #FF0000;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }

        .success-notification .notification-close {
            color: #28a745 !important;
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
            
            .video-vertical-container {
                width: 100%;
                max-width: 467px; /* Ligeramente más estrecho en móviles */
                min-height: 600px; /* Altura mínima para asegurar que se vea bien */
            }
        }

        @media (min-width: 768px) {
            

            .bingo-container {
                max-width: 500px;
                margin: 0 auto;
            }
            
            .bingo-cerrado-container {
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
            
            .bingo-cerrado-titulo {
                font-size: 32px;
            }

            .form-control,
            .form-label,
            .etiqueta-archivo {
                font-size: 20px;
            }

            .text-amarillo,
            .btn-naranja {
                font-size: 22px;
            }
            
            .bingo-cerrado-fecha {
                font-size: 22px;
            }

            .logo-container {
                height: 50px;
            }

            .video-vertical-container {
                max-width: 458px; /* Tamaño ideal para simular un teléfono */
                min-height: 650px; /* Altura mínima para tablets */
            }
        }

        @media (min-width: 992px) {
            .bingo-container {
                max-width: 600px;
            }
            
            .bingo-cerrado-container {
                max-width: 600px;
            }
            
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

        /* Botón de borrar sobre cada preview */
        .delete-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #fa9044;
            border: none;
            color: #fff;
            font-size: 14px;
            padding: 1px 6px;
            cursor: pointer;
            border-radius: 50%;
        }

        /* Contenedor de cada preview */
        .preview-item {
            position: relative;
            width: 100%;
            height: 100px;
        }
    </style>
</head>

<body>
    @php
        // Obtener los enlaces de la base de datos
        $enlaces = App\Models\Enlace::first() ?? new App\Models\Enlace();
        // Usar el nuevo campo telefono_atencion con respaldo al número de contacto antiguo
        $numeroContacto = $enlaces->numero_contacto ?? '3235903774'; // Número para pagos (original)
        $telefonoAtencion = $enlaces->telefono_atencion ?? $numeroContacto; // Teléfono de atención (nuevo)
        
        // Nuevos métodos de pago con respaldo al número de contacto
        $numeroNequi = $enlaces->numero_nequi ?? $numeroContacto;
        $numeroDaviplata = $enlaces->numero_daviplata ?? $numeroContacto;
        $numeroTransfiya = $enlaces->numero_transfiya ?? $numeroContacto;
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

    <!-- Contenedor principal -->
    <div class="container py-4">
        <!-- Contenedor para bingo cerrado -->
        <div class="bingo-cerrado-container" id="bingoCerradoContainer">
            <h1 class="bingo-cerrado-titulo">No hay más cartones disponibles para este Bingo</h1>
            
            <h3 class="bingo-cerrado-fecha" id="bingoCerradoFecha">
                <!-- La fecha se llenará dinámicamente -->
            </h3>
            
            <div class="bingo-actions">
                <a href="{{ route('cartones.index') }}" class="btn btn-naranja text-white">
                    Buscar mi cartón
                </a>
                @if($enlaces->grupo_whatsapp)
                    <a href="{{ $enlaces->grupo_whatsapp }}" target="_blank" class="btn btn-naranja text-white">
                        Grupo Whatsapp
                    </a>
                @endif
            </div>
        </div>
    
        <!-- Formulario de compra -->
        <div class="bingo-container p-4" id="bingoFormContainer">
            <!-- FORMULARIO -->
            <form action="{{ route('bingo.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="bingo_id" id="bingo_id" value="{{ $bingo->id ?? '' }}">
                <!-- Paso 1 -->
                <h4 class="paso-title text-center mb-2">Paso 1</h4>
                <p class="text-start mb-3 fw-bold">Escoge la cantidad de cartones:</p>
                <div class="bg-black p-3 rounded mb-4">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <button
                            type="button"
                            id="btnMinus"
                            class="btn btn-light fw-bold px-3 py-2">-</button>

                        <input
                            type="number"
                            id="inputCartones"
                            name="cartones"
                            class="form-control mx-2 text-center bg-white text-dark fw-bold"
                            style="max-width: 900px;"
                            value="1"
                            min="1">

                        <button
                            type="button"
                            id="btnPlus"
                            class="btn btn-light fw-bold px-3 py-2">+</button>
                    </div>
                    <!-- Sección de precios en HTML con formato correcto -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div><span class="text-verde fw-bold">Precio cartón:</span></div>
                        <div class="text-end fw-bold" id="precioCarton">${{ number_format((float)($bingo->precio ?? 6000), 2, '.', '.') }} Pesos</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div><span class="text-verde fw-bold">Total:</span></div>
                        <div class="text-end fw-bold" id="totalPrice">${{ number_format((float)($bingo->precio ?? 6000), 0, '', '.') }} Pesos</div>
                    </div>
                </div>

                <!-- Paso 2 -->
                <h4 class="paso-title text-center mb-2">Paso 2</h4>
                <p class="paso-title text-center mb-2">Ingresa tus datos:</p>
                <div class="bg-black p-3 rounded mb-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre y Apellidos</label>
                        <input
                            type="text"
                            name="nombre"
                            class="form-control bg-white text-dark"
                            placeholder="Ingresa tu nombre completo"
                            >
                    </div>
                    <div class="mb-2">
                    <label class="form-label fw-bold">Numero de Whatsapp</label>
                    <input
                        type="tel"
                        name="celular"
                        class="form-control bg-white text-dark"
                        placeholder="Ingresa tu número de whatsapp"
                        pattern="[0-9]+"
                        inputmode="numeric">
                </div>
                </div>

                <!-- Paso 3 -->
                <h4 class="paso-title text-center mb-2">Paso 3</h4>
<p class="text-start mb-3">Realiza el pago y toma una captura del comprobante</p>
<div class="bg-black p-3 rounded mb-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-verde fw-bold">
                    Nequi: <span class="text-white">{{ $numeroNequi }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-light" onclick="return copiarNumero('{{ $numeroNequi }}', event)">
                    Copiar
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-verde fw-bold">
                    Daviplata: <span class="text-white">{{ $numeroDaviplata }}</span>
                </div>
                <button class="btn btn-sm btn-outline-light" onclick="return copiarNumero('{{ $numeroDaviplata }}', event)">
                    Copiar
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-verde fw-bold">
                    Transfiya: <span class="text-white">{{ $numeroTransfiya }}</span>
                </div>
                <button class="btn btn-sm btn-outline-light" onclick="return copiarNumero('{{ $numeroTransfiya }}', event)">
                    Copiar
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div><span class="text-amarillo fw-bold">Total a pagar:</span></div>
                <div class="text-end fw-bold" id="totalPagar">${{ number_format($bingo->precio ?? 6000, 0, ',', '.') }} Pesos</div>
            </div>
        </div>
    </div>
</div>


                <!-- Paso 4 -->
                <h4 class="paso-title text-center mb-2">Paso 4</h4>
                <p class="text-start mb-3 fw-bold">Sube tu comprobante de pago</p>
                <div class="bg-black p-3 rounded mb-4">
                    <div class="border-naranja rounded p-3 text-center mb-3">
                        <!-- Botón para seleccionar archivos -->
                        <label class="etiqueta-archivo mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="currentColor" class="bi bi-camera me-2" viewBox="0 0 16 16">
                                <path d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828
                                         A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0
                                         12.828 5H14a1 1 0 0 1 1 1zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2
                                         2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828
                                         A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828
                                         A2 2 0 0 1 3.172 4z" />
                                <path d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0
                                         5m0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M3
                                         6.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0" />
                            </svg>
                            <span>Foto/Captura de pantalla</span>
                            <input
                                type="file"
                                name="comprobante[]"
                                id="comprobante"
                                style="display: none;"
                                accept="image/*"
                                multiple
                                >
                        </label>
                    </div>
                    <!-- Contenedor para la vista previa de las imágenes -->
                    <div id="previewContainer"></div>
                    <button class="btn btn-naranja text-white fw-bold w-100 py-2" style="margin-top: 10px;">
                        RESERVAR MIS CARTONES
                    </button>
                </div>
            </form>

            <!-- Ayuda -->
            <div class="text-center mt-4 fw-bold">
                <p class="mb-1">¿Cómo comprar?</p>
                @if($enlaces->video_1)
                    <div class="video-vertical-container">
                        <iframe 
                            src="{{ str_replace('watch?v=', 'embed/', $enlaces->video_1) }}" 
                            title="Video tutorial de compra" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                        </iframe>
                    </div>
                @else
                    <span class="text-decoration-underline text-warning">video 1 aquí</span>
                @endif
            </div>
        </div>
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

<!-- Script para manejar la lógica de + / -, cálculo de total, y vista previa con eliminación de imágenes -->
<!-- Script para manejar la lógica de + / -, cálculo de total, y vista previa con eliminación de imágenes -->
<script>
// Variables globales
let PRICE_PER_CARTON = parseFloat({{ $bingo->precio ?? 6000 }});
let inputCartones, btnMinus, btnPlus, totalPrice, totalPagar, precioCarton;
let selectedFiles = []; // Array para mantener los archivos seleccionados
let isSubmitting = false; // Flag para controlar múltiples envíos

// Function to format numbers with thousands separators (without decimals)
function formatNumber(number) {
    return `$${Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")} Pesos`;
}

// Make updateTotal a global function
function updateTotal() {
    let quantity = parseInt(inputCartones.value, 10);
    if (isNaN(quantity) || quantity < 1) {
        quantity = 1;
        inputCartones.value = 1;
    }
    const total = quantity * PRICE_PER_CARTON;

    // Update cart price and totals using the same format (without decimals)
    precioCarton.textContent = formatNumber(PRICE_PER_CARTON);
    totalPrice.textContent = formatNumber(total);
    totalPagar.textContent = formatNumber(total);
}

// Función para mostrar notificaciones de error
function showErrorNotification(title, message) {
    // Crear contenedor de notificaciones si no existe
    let container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
    
    // Crear la notificación
    const notification = document.createElement('div');
    notification.className = 'notification';
    
    // Agregar botón de cierre
    const closeBtn = document.createElement('button');
    closeBtn.className = 'notification-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', function() {
        container.removeChild(notification);
    });
    
    // Agregar título
    const titleElement = document.createElement('h5');
    titleElement.className = 'notification-title';
    titleElement.textContent = title;
    
    // Agregar mensaje
    const messageElement = document.createElement('p');
    messageElement.className = 'notification-message';
    messageElement.innerHTML = message;
    
    // Ensamblar la notificación
    notification.appendChild(closeBtn);
    notification.appendChild(titleElement);
    notification.appendChild(messageElement);
    
    // Agregar al contenedor
    container.appendChild(notification);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(function() {
        if (container.contains(notification)) {
            container.removeChild(notification);
        }
    }, 5000);
}

// Form validation function with notifications
function validarFormulario(event) {
    // Prevent form submission
    event.preventDefault();
    
    // Evitar múltiples envíos
    if (isSubmitting) {
        console.log('Envío en progreso, ignorando clics adicionales');
        return false;
    }
    
    // Marcar como en proceso de envío
    isSubmitting = true;
    
    // Deshabilitar el botón de reserva
    const submitButton = document.querySelector('.btn-naranja.text-white.fw-bold');
    if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        submitButton.classList.add('disabled');
    }
    
    // Get form elements
    const form = event.target;
    const nombre = form.querySelector('input[name="nombre"]');
    const celular = form.querySelector('input[name="celular"]');
    const comprobantes = form.querySelector('input[name="comprobante[]"]');
    
    // Reset previous error messages
    removeAllErrorHighlights();
    
    // Array to collect field errors
    let camposFaltantes = [];
    
    // Validate nombre
    if (!nombre.value.trim()) {
        highlightField(nombre);
        camposFaltantes.push('Nombre');
    }
    
    // Validate celular
    if (!celular.value.trim()) {
        highlightField(celular);
        camposFaltantes.push('Celular');
    } else if (!/^[0-9]+$/.test(celular.value.trim())) {
        highlightField(celular);
        camposFaltantes.push('Celular (formato inválido)');
    }
    
    // Validate file upload
    if (selectedFiles.length === 0) {
        highlightField(comprobantes.closest('.border-naranja'));
        camposFaltantes.push('Comprobante de pago');
    }
    
    // If fields are missing, show notification
    if (camposFaltantes.length > 0) {
        let message = '<ul style="margin-bottom: 0; padding-left: 20px;">';
        camposFaltantes.forEach(campo => {
            message += `<li>${campo}</li>`;
        });
        message += '</ul>';
        
        showErrorNotification('Por favor completa los siguientes campos:', message);
        
        // Scroll to the first error field
        const firstErrorField = document.querySelector('.border-danger');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Reactivar el botón de envío si hay errores de validación
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            submitButton.classList.remove('disabled');
            isSubmitting = false; // Permitir nuevo intento
        }
        
        return false;
    }
    
    // Preparar los datos para enviar al servidor
    const formData = new FormData(form);
    
    // Eliminar los archivos existentes en el FormData (para evitar duplicados)
    formData.delete('comprobante[]');
    
    // Agregar cada archivo seleccionado al FormData
    selectedFiles.forEach(file => {
        formData.append('comprobante[]', file);
    });
    
    // Ahora vamos a enviar el formulario usando Fetch API
    fetch(form.action, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
})
.then(response => {
    if (!response.ok) {
        return response.text().then(text => {
            console.error('Error del servidor:', response.status, response.statusText);
            console.error('Contenido de la respuesta:', text);
            throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
        });
    }
    return response.text();
})
.then(html => {
    // Redirigir a la página que nos devuelve el servidor
    document.open();
    document.write(html);
    document.close();
})
.catch(error => {
    console.error('Error completo:', error);
    showErrorNotification('Error al enviar formulario', 
        `Hubo un problema al enviar los datos. Error: ${error.message}. Revisa la consola para más detalles.`);
    
    // Reactivar el botón de envío en caso de error
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        submitButton.classList.remove('disabled');
        isSubmitting = false; // Permitir nuevo intento
    }
});
}

// Función simplificada para resaltar un campo con error
function highlightField(element) {
    // Add red border to indicate error
    element.classList.add('border-danger');
}

// Function to remove all error highlights
function removeAllErrorHighlights() {
    // Remove error borders
    document.querySelectorAll('.border-danger').forEach(el => {
        el.classList.remove('border-danger');
    });
}

// Initialize elements after DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add styles for notifications
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        /* Estilos para notificaciones de error */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 350px;
            z-index: 1050;
        }
        
        .notification {
            background-color: #FFEEEE;
            border-left: 4px solid #FF0000;
            color: #FF0000;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-weight: bold;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification-title {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 18px;
        }
        
        .notification-message {
            margin: 0;
        }
        
        .notification-close {
            float: right;
            background: none;
            border: none;
            color: #FF0000;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }
        
        /* Estilo para campos con error */
        .border-danger {
            border: 2px solid #FF0000 !important;
        }
    `;
    document.head.appendChild(styleElement);

    // Get elements for quantity and total
    inputCartones = document.getElementById('inputCartones');
    btnMinus = document.getElementById('btnMinus');
    btnPlus = document.getElementById('btnPlus');
    totalPrice = document.getElementById('totalPrice');
    totalPagar = document.getElementById('totalPagar');
    precioCarton = document.getElementById('precioCarton');

    // Manejamos la visualización basada en si el bingo está abierto o cerrado
    const esBingoCerrado = {{ $esBingoCerrado ? 'true' : 'false' }};
    const bingoFormContainer = document.getElementById('bingoFormContainer');
    const bingoCerradoContainer = document.getElementById('bingoCerradoContainer');
    
    if (esBingoCerrado) {
        // Si el bingo está cerrado, mostrar el mensaje correspondiente
        bingoFormContainer.style.display = 'none';
        bingoCerradoContainer.style.display = 'block';
        
        // Actualizar la fecha del bingo cerrado
        const bingoCerradoFecha = document.getElementById('bingoCerradoFecha');
        if (bingoCerradoFecha) {
            bingoCerradoFecha.textContent = "{{ $bingo->nombre ?? '' }} - {{ \Carbon\Carbon::parse($bingo->fecha ?? now())->format('d/m/Y') }}";
        }
    } else {
        // Si el bingo está abierto, mostrar el formulario
        bingoFormContainer.style.display = 'block';
        bingoCerradoContainer.style.display = 'none';
    }

    btnMinus.addEventListener('click', () => {
        let quantity = parseInt(inputCartones.value, 10);
        if (quantity > 1) {
            quantity--;
            inputCartones.value = quantity;
            updateTotal();
        }
    });

    btnPlus.addEventListener('click', () => {
        let quantity = parseInt(inputCartones.value, 10);
        quantity++;
        inputCartones.value = quantity;
        updateTotal();
    });

    inputCartones.addEventListener('change', updateTotal);
    updateTotal();

    // File handling for multiple preview with deletion - CORREGIDO
    const fileInput = document.getElementById('comprobante');
    const previewContainer = document.getElementById('previewContainer');

    fileInput.addEventListener('change', () => {
        // Add new files to global array
        const newFiles = Array.from(fileInput.files);
        newFiles.forEach(file => {
            selectedFiles.push(file);
        });
        updatePreview();
        
        // Reset el input para permitir seleccionar el mismo archivo múltiples veces
        fileInput.value = '';
    });

    function updatePreview() {
        previewContainer.innerHTML = '';
        
        // Si no hay archivos seleccionados, no hacer nada más
        if (selectedFiles.length === 0) return;
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Create container for image and delete button
                const previewItem = document.createElement('div');
                previewItem.classList.add('preview-item');

                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-preview');

                const deleteBtn = document.createElement('button');
                deleteBtn.textContent = 'X';
                deleteBtn.classList.add('delete-btn');
                deleteBtn.setAttribute('data-index', index); 
                deleteBtn.addEventListener('click', function() {
                    const idx = parseInt(this.getAttribute('data-index'));
                    // Eliminar el archivo del array
                    selectedFiles.splice(idx, 1);
                    // Actualizar la vista previa
                    updatePreview();
                });

                previewItem.appendChild(img);
                previewItem.appendChild(deleteBtn);
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });
    }
    
    // Add form validation on submit
    const bingoForm = document.querySelector('form');
    if (bingoForm) {
        bingoForm.addEventListener('submit', validarFormulario);
        
        // Add event listeners to remove validation errors when user interacts with field
        const formInputs = bingoForm.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                // Remove error styling from this input
                this.classList.remove('border-danger');
            });
        });
        
        // Special case for file upload
        if (fileInput) {
            const fileContainer = fileInput.closest('.border-naranja');
            fileInput.addEventListener('change', function() {
                if (fileContainer) {
                    fileContainer.classList.remove('border-danger');
                }
            });
        }
    }
    
    // Agregar funcionalidad de un solo clic al botón de reserva
    const reservarButton = document.querySelector('.btn-naranja.text-white.fw-bold.w-100');
    if (reservarButton) {
        reservarButton.addEventListener('click', function() {
            // Si ya está en proceso de envío, no hacer nada
            if (isSubmitting) {
                console.log('Ya hay un envío en proceso, ignorando clic adicional');
                return false;
            }
            
            // Si no está dentro de un formulario, manejar independientemente
            const form = this.closest('form');
            if (!form) {
                console.log('El botón no está dentro de un formulario');
                return;
            }
            
            // El botón está dentro de un formulario, el evento submit lo manejará
            // validarFormulario ya se encargará de deshabilitar el botón
            // No hacemos nada más ya que el evento submit del formulario se activará
        });
    }
});

function copiarNumero(numero, event) {
    // Prevenir cualquier comportamiento predeterminado
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Copiar el número al portapapeles
    navigator.clipboard.writeText(numero)
        .then(() => {
            // Mostrar notificación de éxito
            showSuccessNotification('Éxito', 'Número copiado');
        })
        .catch(err => {
            console.error('Error al copiar: ', err);
        });
        
    // Retornar false para evitar propagación adicional
    return false;
}

// Función para mostrar notificaciones de éxito (verde)
function showSuccessNotification(title, message) {
    // Crear contenedor de notificaciones si no existe
    let container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
    
    // Crear la notificación
    const notification = document.createElement('div');
    notification.className = 'notification success-notification'; // Aseguramos la clase para estilo verde
    notification.style.backgroundColor = '#EEFFEE'; // Forzar color de fondo
    notification.style.borderLeftColor = '#28a745'; // Forzar color de borde
    notification.style.color = '#28a745'; // Forzar color de texto
    
    // Agregar botón de cierre
    const closeBtn = document.createElement('button');
    closeBtn.className = 'notification-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.style.color = '#28a745'; // Forzar color del botón de cierre
    closeBtn.addEventListener('click', function() {
        container.removeChild(notification);
    });
    
    // Agregar título
    const titleElement = document.createElement('h5');
    titleElement.className = 'notification-title';
    titleElement.textContent = title;
    
    // Agregar mensaje
    const messageElement = document.createElement('p');
    messageElement.className = 'notification-message';
    messageElement.innerHTML = message;
    
    // Ensamblar la notificación
    notification.appendChild(closeBtn);
    notification.appendChild(titleElement);
    notification.appendChild(messageElement);
    
    // Agregar al contenedor
    container.appendChild(notification);
    
    // Auto-eliminar después de 3 segundos (más corto para notificaciones de éxito)
    setTimeout(function() {
        if (container.contains(notification)) {
            container.removeChild(notification);
        }
    }, 3000);
}
</script>

</body>

</html>