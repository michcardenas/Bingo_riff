<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>RIFFY Bingo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="{{ asset('images/paraelico.png') }}">

    <!-- Carga de assets (Tailwind CSS, JS, etc.) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Estilos para eliminar el borde azul de los inputs -->
    <style>
        /* Reset global para quitar el outline azul en todos los navegadores */
        *:focus {
            outline: none !important;
            box-shadow: none !important;
        }
        
        /* Estilo específico para inputs en Chrome/Safari */
        input:focus, textarea:focus, select:focus, button:focus {
            outline: none !important;
            -webkit-box-shadow: none !important;
            box-shadow: none !important;
            border-color: #00bf63 !important;
        }
        
        /* Eliminar el highlight de tap en dispositivos móviles */
        * {
            -webkit-tap-highlight-color: transparent;
        }

        #riffy-logo {
        height: 32px; /* Tamaño en móviles */
    }
    
    /* Ajuste para tamaños de pantalla más grandes */
    @media (min-width: 768px) {
        #riffy-logo {
            height: 120px;
        }
    }
    
    /* Si necesitas ajustar más el logo */
    #logo-container {
        display: flex;
        align-items: center;
    }
    </style>
    
    @yield('head')
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Cabecera con fondo en #00bf63 -->
    <header class="bg-[#00bf63] w-full shadow-md">
    <div class="container mx-auto py-4 px-4">
        <div class="flex justify-center items-center">
            <div class="logo-container mr-3" id="logo-container">
            <img src="{{ asset('images/RiffyLogo.png') }}" alt="Logo" id="riffy-logo">
            </div>
        </div>
    </div>
</header>
    <!-- Contenedor principal centrado vertical y horizontalmente -->
    <div class="flex-grow flex items-center justify-center p-4">
        <!-- Tarjeta de autenticación con fondo oscuro, bordes redondeados y sombra -->
        <div class="w-full sm:max-w-md p-6 shadow-lg rounded-lg border border-white">
            @yield('content')
        </div>
    </div>

    <!-- Footer simple -->
    <footer class="mt-auto py-3 text-center text-gray-500 text-sm">
        <p>&copy; {{ date('Y') }} Riffy Bingo. Todos los derechos reservados.</p>
    </footer>
</body>
</html>