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
            background-color: #0a0a0a;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        p {
            font-size: 16px;
            margin-bottom: 0.6rem;
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

        /* Step badge - orange pill */
        .paso-badge {
            display: inline-flex;
            align-items: center;
            background-color: #fa9044;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 15px;
            padding: 5px 14px;
            border-radius: 8px;
            margin-right: 10px;
            white-space: nowrap;
            border: 2px solid #e07d35;
        }

        .paso-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-left: 4px;
        }

        .paso-header-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #fff;
        }

        .paso-subtitle {
            font-size: 14px;
            color: #aaa;
            margin-top: 2px;
        }

        .btn-naranja {
            background-color: #fa9044;
            border-color: #fa9044;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 18px;
            color: #fff;
        }

        .btn-naranja:hover {
            background-color: #e07d35;
            border-color: #e07d35;
            color: #fff;
        }

        .text-verde {
            color: #00bf63;
        }

        .text-amarillo {
            color: #FFD700;
            font-size: 18px;
        }

        .text-naranja {
            color: #fa9044;
        }

        .bingo-container {
            background-color: transparent;
            border-radius: 0;
            padding: 0;
        }

        /* Step wrapper + sections */
        .step-wrapper {
            background-color: #1a1a1a;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #222;
        }

        .step-inner-box {
            background-color: #111;
            border-radius: 10px;
            padding: 14px;
        }

        .step-wrapper:last-child {
            margin-bottom: 0;
        }

        .form-control {
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            padding: 10px 14px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 2px rgba(0, 191, 99, 0.3);
            border-color: #00bf63;
        }

        /* Input groups with icons */
        .input-icon-group {
            position: relative;
        }

        .input-icon-group .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 18px;
            z-index: 2;
        }

        .input-icon-group .form-control {
            padding-left: 42px;
        }

        .form-label {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .nav-link-custom {
            font-size: 16px;
        }

        .btn {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
        }

        /* Quantity selector */
        .qty-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            border: 1px solid #333;
            border-radius: 6px;
            overflow: hidden;
        }

        .qty-selector .btn-qty {
            width: 42px;
            height: 42px;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: #2a2a2a;
            color: #aaa;
            transition: background-color 0.2s;
            cursor: pointer;
            flex-shrink: 0;
        }

        .qty-selector .btn-qty:hover {
            background-color: #3a3a3a;
            color: #fff;
        }

        .qty-selector .qty-input {
            width: 100%;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            border: none;
            border-left: 1px solid #333;
            border-right: 1px solid #333;
            background-color: #0d0d0d;
            color: #fff;
            height: 42px;
            border-radius: 0;
            margin: 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 0;
        }

        .total-label {
            color: #00bf63;
            font-weight: 700;
            font-size: 16px;
        }

        .total-value {
            font-weight: 700;
            font-size: 18px;
        }

        /* Payment section */
        .payment-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            margin-bottom: 14px;
        }

        .payment-total-row .total-left {
            color: #00bf63;
            font-weight: 700;
            font-size: 15px;
            font-style: italic;
        }

        .payment-total-row .total-right {
            font-weight: 700;
            font-size: 18px;
            color: #fff;
        }

        .payment-tabs {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        button.payment-tab {
            background: none !important;
            border: none !important;
            font-weight: 700;
            font-size: 15px;
            padding: 4px 8px;
            cursor: pointer;
            transition: opacity 0.2s;
            border-radius: 4px;
            outline: none;
            position: relative;
            z-index: 1;
        }

        button.payment-tab:hover {
            opacity: 0.8;
        }

        button.payment-tab.active {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        button.payment-tab.payment-tab-nequi,
        button.payment-tab.payment-tab-daviplata,
        button.payment-tab.payment-tab-transfiya { color: #00bf63 !important; }

        .payment-tab-separator {
            color: #555;
            font-size: 16px;
            user-select: none;
        }

        .payment-number-display {
            text-align: center;
            margin-bottom: 10px;
        }

        .payment-number-display .number {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }

        .payment-copy-center {
            text-align: center;
            margin-bottom: 14px;
        }

        .btn-copiar {
            background-color: transparent;
            border: 1px solid #555;
            color: #ccc;
            font-size: 13px;
            padding: 6px 20px;
            border-radius: 6px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-copiar:hover {
            border-color: #00bf63;
            color: #00bf63;
        }

        .payment-holders {
            text-align: center;
            font-size: 13px;
            color: #aaa;
            line-height: 1.6;
        }

        .payment-holders strong {
            color: #ccc;
        }

        /* Upload section */
        .border-naranja {
            border: 2px dashed #555 !important;
            transition: border-color 0.2s;
        }

        .border-naranja:hover {
            border-color: #00bf63 !important;
        }

        .etiqueta-archivo {
            cursor: pointer;
            color: #ffffff;
            font-size: 16px;
        }

        .btn-upload {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            padding: 12px 24px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
            cursor: pointer;
        }

        .btn-upload:hover {
            opacity: 0.9;
            color: #fff;
        }

        .btn-enviar {
            background-color: #00bf63;
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            padding: 14px;
            border-radius: 10px;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-enviar:hover {
            background-color: #00a854;
            color: #fff;
        }

        .btn-enviar:disabled, .btn-enviar.disabled {
            background-color: #1a4a2e;
            border: none;
            color: #666;
            cursor: not-allowed;
            opacity: 0.65;
        }

        /* Preview */
        #previewContainer {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .img-preview {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border: 2px solid #00bf63;
            border-radius: 8px;
            position: relative;
        }

        .delete-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(220, 38, 38, 0.9);
            border: none;
            color: #fff;
            font-size: 12px;
            width: 22px;
            height: 22px;
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-item {
            position: relative;
            width: 100%;
            height: 100px;
        }

        /* Bingo cerrado */
        .bingo-cerrado-container {
            background-color: #1a1a1a;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
            display: none;
        }

        .bingo-cerrado-titulo {
            color: #fa9044;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.2;
        }

        .bingo-cerrado-fecha {
            color: #ffffff;
            font-size: 18px;
            margin-bottom: 1rem;
        }

        .bingo-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* Video container */
        .video-vertical-container {
            width: 100%;
            max-width: 400px;
            height: auto;
            aspect-ratio: 9/16;
            margin: 10px auto 20px;
            position: relative;
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
        }

        /* Notifications */
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
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            font-weight: bold;
            animation: slideIn 0.4s ease-out;
        }

        .notification.success-notification {
            background-color: #EEFFEE !important;
            border-left: 4px solid #28a745 !important;
            color: #28a745 !important;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .notification-title {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .notification-message { margin: 0; }

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

        /* WhatsApp float */
        .whatsapp-float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 40px;
            right: 40px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50%;
            text-align: center;
            font-size: 36px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
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
        }

        /* Responsive */
        @media screen and (max-width: 767px) {
            .nav-link-custom { font-size: 13px; }
            .whatsapp-float {
                width: 56px; height: 56px;
                bottom: 24px; right: 24px;
                font-size: 30px;
            }
            .video-vertical-container {
                width: 100%;
                max-width: 467px;
                min-height: 600px;
            }
            .paso-header-text { font-size: 16px; }
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
            .logo-container { height: 50px; }
            .video-vertical-container {
                max-width: 458px;
                min-height: 650px;
            }
        }

        @media (min-width: 992px) {
            .bingo-container { max-width: 550px; }
            .bingo-cerrado-container { max-width: 550px; }
            .video-vertical-container {
                max-width: 568px;
                min-height: 700px;
            }
        }

        @media (max-width: 480px) {
            .video-vertical-container {
                width: 100%;
                max-width: 400px;
                min-height: 550px;
            }
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
            <a href="{{ env('APP_URL', 'https://bingoriffy.com') }}">
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
        <div class="bingo-container p-3 p-md-4" id="bingoFormContainer">
            <!-- FORMULARIO -->
            <form action="{{ route('bingo.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="bingo_id" id="bingo_id" value="{{ $bingo->id ?? '' }}">

                <!-- Paso 1 -->
                <div class="step-wrapper">
                    <div class="paso-header">
                        <span class="paso-badge">Paso 1</span>
                        <div>
                            <div class="paso-header-text">Escoge la cantidad de Cartones</div>
                            <div class="paso-subtitle">1 Carton trae 6 tablas</div>
                        </div>
                    </div>
                    <div class="step-inner-box">
                        <div class="qty-selector mb-2">
                            <button type="button" id="btnMinus" class="btn-qty">-</button>
                            <input
                                type="number"
                                id="inputCartones"
                                name="cartones"
                                class="qty-input"
                                value="1"
                                min="1"
                                readonly
                                max="10">
                            <button type="button" id="btnPlus" class="btn-qty">+</button>
                        </div>

                        <div class="total-row">
                            <span class="total-label">Total:</span>
                            <span class="total-value" id="totalPrice">${{ number_format((float)($bingo->precio ?? 6000), 0, '', '.') }} Pesos</span>
                        </div>
                    </div>
                    <div id="precioCarton" style="display:none;">${{ number_format((float)($bingo->precio ?? 6000), 2, '.', '.') }} Pesos</div>
                </div>

                <!-- Paso 2 -->
                <div class="step-wrapper">
                    <div class="paso-header">
                        <span class="paso-badge">Paso 2</span>
                        <div class="paso-header-text">Ingresa tus datos:</div>
                    </div>
                    <div class="mb-3">
                        <div class="input-icon-group">
                            <i class="fas fa-user input-icon"></i>
                            <input
                                type="text"
                                name="nombre"
                                class="form-control"
                                placeholder="Ingresa tu nombre completo">
                        </div>
                    </div>
                    <div class="mb-1">
                        <div class="input-icon-group">
                            <i class="fab fa-whatsapp input-icon" style="color: #25d366; font-size: 20px;"></i>
                            <input
                                type="tel"
                                name="celular"
                                class="form-control"
                                placeholder="Ingresa tu número de whatsapp"
                                pattern="[0-9]+"
                                inputmode="numeric">
                        </div>
                    </div>
                </div>

                <!-- Paso 3 -->
                <div class="step-wrapper">
                    <div class="paso-header">
                        <span class="paso-badge">Paso 3</span>
                        <div class="paso-header-text">Realiza el pago y toma una captura del comprobante:</div>
                    </div>
                    <div class="step-inner-box">
                        <!-- Total a pagar row -->
                        <div class="payment-total-row">
                            <span class="total-left">Total a pagar: <span id="totalPagarLeft">${{ number_format($bingo->precio ?? 6000, 0, ',', '.') }} Pesos</span></span>
                            <span class="total-right" id="totalPagar">${{ number_format($bingo->precio ?? 6000, 0, ',', '.') }} Pesos</span>
                        </div>

                        <!-- Payment method tabs -->
                        <div class="payment-tabs">
                            <button type="button" class="payment-tab payment-tab-nequi active" data-method="nequi" data-number="{{ $numeroNequi }}">Nequi</button>
                            <span class="payment-tab-separator">|</span>
                            <button type="button" class="payment-tab payment-tab-daviplata" data-method="daviplata" data-number="{{ $numeroDaviplata }}">Daviplata</button>
                            <span class="payment-tab-separator">|</span>
                            <button type="button" class="payment-tab payment-tab-transfiya" data-method="transfiya" data-number="{{ $numeroTransfiya }}">llave Bre-B</button>
                        </div>

                        <!-- Number display -->
                        <div class="payment-number-display">
                            <span class="number" id="paymentNumber">{{ $numeroNequi }}</span>
                        </div>

                        <!-- Copy button centered -->
                        <div class="payment-copy-center">
                            <button type="button" class="btn-copiar" id="btnCopiarPago" onclick="return copiarNumeroActivo(event)">
                                <i class="far fa-copy me-1"></i> Copiar
                            </button>
                        </div>

                        <!-- Account holder names -->
                        <div class="payment-holders">
                            Nequi: <strong>{{ $enlaces->nombre_nequi ?? 'Carmen causil' }}</strong><br>
                            Daviplata: <strong>{{ $enlaces->nombre_daviplata ?? 'Martin Rodriguez' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Paso 4 -->
                <div class="step-wrapper">
                    <div class="paso-header">
                        <span class="paso-badge">Paso 4</span>
                        <div>
                            <div class="paso-header-text">Confirma tu pago:</div>
                            <div class="paso-subtitle">Sube tu comprobante y luego presiona <strong style="color:#fff;">Enviar comprobante de pago</strong>.</div>
                        </div>
                    </div>
                    <div class="text-center mb-3">
                        <label class="btn-upload mb-0">
                            <i class="fas fa-camera"></i>
                            <span>Selecciona y sube tu comprobante</span>
                            <input
                                type="file"
                                name="comprobante"
                                id="comprobante"
                                style="display: none;"
                                accept="image/*"
                                multiple>
                        </label>
                    </div>

                    <div id="previewContainer"></div>

                    <button class="btn btn-enviar w-100 mt-2" type="submit">
                        Enviar comprobante de pago
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
document.getElementById('comprobante').addEventListener('change', function(e) {
    if (this.files.length > 1) {
        alert('Solo puedes subir una imagen como comprobante.');
        this.value = ''; // Limpia la selección
    }
});

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
    const totalPagarLeft = document.getElementById('totalPagarLeft');
    if (totalPagarLeft) totalPagarLeft.textContent = formatNumber(total);
}

// Function to validate all required fields
function validateFields() {
    // Get form elements
    const nombre = document.querySelector('input[name="nombre"]');
    const celular = document.querySelector('input[name="celular"]');
    const submitButton = document.querySelector('.btn-enviar');

    // Check if all required fields are filled
    const isNombreValid = nombre.value.trim() !== '';
    const isCelularValid = celular.value.trim() !== '' && /^[0-9]+$/.test(celular.value.trim());
    const isFileValid = selectedFiles.length > 0;

    // Enable button only if all validations pass
    if (isNombreValid && isCelularValid && isFileValid) {
        submitButton.disabled = false;
        submitButton.classList.remove('disabled');
    } else {
        submitButton.disabled = true;
        submitButton.classList.add('disabled');
    }
}

// Function to initialize button state
function initializeButtonState() {
    // Get the submit button
    const submitButton = document.querySelector('.btn-enviar');

    // Initially disable the button
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.classList.add('disabled');
    }

    // Add event listeners to all form fields
    const nombre = document.querySelector('input[name="nombre"]');
    const celular = document.querySelector('input[name="celular"]');

    if (nombre) {
        nombre.addEventListener('input', validateFields);
    }

    if (celular) {
        celular.addEventListener('input', validateFields);
    }
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
    const submitButton = document.querySelector('.btn-enviar');
    let originalText = '';
    if (submitButton) {
        originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        submitButton.classList.add('disabled');
    }

    // Get form elements
    const form = event.target;
    const nombre = form.querySelector('input[name="nombre"]');
    const celular = form.querySelector('input[name="celular"]');
    const comprobantes = form.querySelector('input[name="comprobante"]');

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
        const uploadLabel = document.querySelector('.btn-upload');
        if (uploadLabel) {
            highlightField(uploadLabel);
        }
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

// Updated preview function that also validates fields
function updatePreview() {
    previewContainer.innerHTML = '';

    // Si no hay archivos seleccionados, no hacer nada más
    if (selectedFiles.length === 0) {
        validateFields(); // Validate fields when no files are present
        return;
    }

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

            // Validate fields after each preview is loaded
            validateFields();
        };
        reader.readAsDataURL(file);
    });
}

// Variable para el número activo del pago
let activePaymentNumber = '{{ $numeroNequi }}';

function copiarNumeroActivo(event) {
    return copiarNumero(activePaymentNumber, event);
}

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
    notification.className = 'notification success-notification';
    notification.style.backgroundColor = '#EEFFEE';
    notification.style.borderLeftColor = '#28a745';
    notification.style.color = '#28a745';

    // Agregar botón de cierre
    const closeBtn = document.createElement('button');
    closeBtn.className = 'notification-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.style.color = '#28a745';
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

    // Auto-eliminar después de 3 segundos
    setTimeout(function() {
        if (container.contains(notification)) {
            container.removeChild(notification);
        }
    }, 3000);
}

// Initialize elements after DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize button validation state
    initializeButtonState();

    // Add styles for error states
    const styleElement = document.createElement('style');
    styleElement.textContent = `
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
    if (quantity < 10) {
        quantity++;
        inputCartones.value = quantity;
        updateTotal();
    }
});


    inputCartones.addEventListener('change', updateTotal);
    updateTotal();

    // File handling for multiple preview with deletion
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
            fileInput.addEventListener('change', function() {
                const uploadLabel = document.querySelector('.btn-upload');
                if (uploadLabel) {
                    uploadLabel.classList.remove('border-danger');
                }
            });
        }
    }

    // Agregar funcionalidad de un solo clic al botón de reserva
    const reservarButton = document.querySelector('.btn-enviar');
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
        });
    }

    // Payment tabs switching
    const paymentTabs = document.querySelectorAll('.payment-tab');
    const paymentNumberDisplay = document.getElementById('paymentNumber');
    paymentTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active from all tabs
            paymentTabs.forEach(t => t.classList.remove('active'));
            // Set this tab as active
            this.classList.add('active');
            // Update displayed number
            const number = this.getAttribute('data-number');
            paymentNumberDisplay.textContent = number;
            activePaymentNumber = number;
        });
    });

    // Perform initial validation
    validateFields();
});
</script>

</body>

</html>
