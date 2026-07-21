<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>RIFFY Bingo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="{{ asset('images/RiffyLogo.png') }}">

    <!-- Carga de assets (Tailwind CSS, JS, etc.) mediante Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Reset global para quitar el outline azul en todos los navegadores */
        *:focus { outline: none !important; }

        /* Eliminar el highlight de tap en dispositivos móviles */
        * { -webkit-tap-highlight-color: transparent; }

        /* Logo dentro de la tarjeta */
        #riffy-logo {
            height: 64px;
            filter: drop-shadow(0 4px 14px rgba(0, 191, 99, 0.35));
        }
        @media (min-width: 768px) {
            #riffy-logo { height: 84px; }
        }

        /* Fondo con brillo verde sutil */
        .auth-bg {
            background-color: #050505;
            background-image:
                radial-gradient(circle at 50% 0%, rgba(0, 191, 99, 0.18), transparent 55%),
                radial-gradient(circle at 85% 90%, rgba(0, 191, 99, 0.10), transparent 45%);
        }

        /* Animación de entrada de la tarjeta */
        @keyframes authCardIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .auth-card-in { animation: authCardIn 0.5s ease-out both; }
    </style>

    @yield('head')
</head>
<body class="auth-bg text-white min-h-screen flex flex-col antialiased">

    <!-- Contenedor principal centrado -->
    <main class="flex-grow flex items-center justify-center p-4">
        <div class="w-full sm:max-w-md auth-card-in">

            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <img src="{{ asset('images/RiffyLogo.png') }}" alt="RIFFY Bingo" id="riffy-logo">
            </div>

            @yield('content')
        </div>
    </main>

    <!-- Footer simple -->
    <footer class="py-4 text-center text-gray-600 text-xs">
        <p>&copy; {{ date('Y') }} Riffy Bingo. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
