<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'RiffyBingo') }} - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Carga de assets (Tailwind CSS, JS, etc.) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-black text-white min-h-screen flex flex-col">
    <!-- Cabecera con fondo en #00bf63 -->
    <header class="bg-[#00bf63] w-full shadow-md">
        <div class="container mx-auto py-4 px-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <div class="logo-container mr-3" id="logo-container">
                        <img src="{{ asset('images/RiffyLogo.png') }}" alt="RIFFY Bingo" id="riffy-logo" style="height: 120px;">
                    </div>
                </div>
                
                <!-- Menú de navegación -->
                <nav class="hidden md:flex space-x-6">
                    <a href="{{ route('dashboard') }}" class="text-white font-medium hover:text-gray-200 transition-colors duration-200 border-b-2 border-white">Dashboard</a>
                    <a href="#" class="text-white font-medium hover:text-gray-200 transition-colors duration-200">Juegos</a>
                    <a href="#" class="text-white font-medium hover:text-gray-200 transition-colors duration-200">Premios</a>
                    
                    <!-- Menú de usuario -->
                    <div class="relative group">
                        <button class="text-white font-medium hover:text-gray-200 transition-colors duration-200 flex items-center">
                            <span>{{ Auth::user()->name }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-black border border-[#00bf63] rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                            <a href="#" class="block px-4 py-2 text-sm text-white hover:bg-[#00bf63] hover:text-white transition-colors duration-200">Perfil</a>
                            <a href="#" class="block px-4 py-2 text-sm text-white hover:bg-[#00bf63] hover:text-white transition-colors duration-200">Configuración</a>
                            <hr class="border-[#00bf63] my-1">
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-white hover:bg-[#00bf63] hover:text-white transition-colors duration-200">
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </nav>
                
                <!-- Menú móvil -->
                <button class="md:hidden text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Contenido principal -->
    <main class="flex-grow">
        <!-- Título de sección -->
        <div class="bg-gray-900 shadow-md">
            <div class="container mx-auto py-4 px-4">
                <h2 class="text-xl font-semibold text-white">
                    {{ __('Dashboard') }}
                </h2>
            </div>
        </div>
        
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-black border border-[#00bf63] overflow-hidden shadow-md rounded-lg">
                    <div class="p-6 text-white">
                        <div class="text-xl mb-4">¡Bienvenido a Riffy Bingo!</div>
                        <p>{{ __("Has iniciado sesión correctamente. Ahora puedes comenzar a explorar todas las características.") }}</p>
                        
                        <!-- Tarjetas de inicio rápido -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                            <div class="bg-gray-900 border border-[#00bf63] p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                                <h3 class="font-bold text-[#00bf63] mb-2">Jugar Ahora</h3>
                                <p class="text-gray-300 mb-4">Participa en partidas de bingo en tiempo real con otros jugadores.</p>
                                <a href="#" class="inline-block bg-[#00bf63] text-white py-2 px-4 rounded hover:bg-green-600 transition-colors duration-200">Jugar</a>
                            </div>
                            
                            <div class="bg-gray-900 border border-[#00bf63] p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                                <h3 class="font-bold text-[#00bf63] mb-2">Premios</h3>
                                <p class="text-gray-300 mb-4">Descubre los premios disponibles y cómo puedes ganarlos.</p>
                                <a href="#" class="inline-block bg-[#00bf63] text-white py-2 px-4 rounded hover:bg-green-600 transition-colors duration-200">Ver Premios</a>
                            </div>
                            
                            <div class="bg-gray-900 border border-[#00bf63] p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                                <h3 class="font-bold text-[#00bf63] mb-2">Invitar Amigos</h3>
                                <p class="text-gray-300 mb-4">Comparte Riffy Bingo con tus amigos y gana recompensas.</p>
                                <a href="#" class="inline-block bg-[#00bf63] text-white py-2 px-4 rounded hover:bg-green-600 transition-colors duration-200">Invitar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0 flex items-center">
                <img src="{{ asset('images/RiffyLogo.png') }}" alt="Logo" class="h-8 mr-2">
                    <span class="text-white">© {{ date('Y') }} Riffy Bingo. Todos los derechos reservados.</span>
                </div>
                
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-[#00bf63] transition-colors duration-200">Términos</a>
                    <a href="#" class="text-gray-400 hover:text-[#00bf63] transition-colors duration-200">Privacidad</a>
                    <a href="#" class="text-gray-400 hover:text-[#00bf63] transition-colors duration-200">Contacto</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>