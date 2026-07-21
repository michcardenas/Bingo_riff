@extends('layouts.guest')

@section('content')
<div class="bg-black border border-white p-6 rounded-lg">
    <h2 class="text-2xl font-bold text-center mb-6">Iniciar Sesión</h2>

    <!-- Mostrar mensaje de sesión (si existe) -->
    @if (session('status'))
        <div class="mb-4 text-green-500">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Campo Email con datalist para sugerencias -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-white">Correo Electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus list="email-suggestions"
                   class="mt-1 block w-full rounded-md bg-black border border-[#00bf63] text-white focus:ring-[#00bf63] focus:border-[#00bf63] focus:outline-none hover:border-green-400 active:border-white active:bg-gray-900 focus:bg-gray-900 transition-all duration-300">          
            @error('email')
                <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Campo Password -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-white">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-md bg-black border border-[#00bf63] text-white focus:ring-[#00bf63] focus:border-[#00bf63] focus:outline-none hover:border-green-400 active:border-white active:bg-gray-900 focus:bg-gray-900 transition-all duration-300">
            @error('password')
                <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Checkbox "Recuérdame" y enlace de recuperación -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center group">
                <input id="remember_me" type="checkbox" name="remember" 
                       class="rounded bg-black border border-[#00bf63] text-[#00bf63] focus:ring-[#00bf63] cursor-pointer group-hover:border-green-400 transition-all duration-300">
                <label for="remember_me" class="ml-2 text-sm text-white cursor-pointer group-hover:text-[#00bf63] transition-all duration-300">Recuérdame</label>
            </div>

            @if (Route::has('password.request'))
                <a class="underline text-sm text-green-500 hover:text-[#00bf63] active:text-white focus:text-[#00bf63] transition-all duration-300 relative after:content-[''] after:absolute after:w-0 after:h-0.5 after:bg-[#00bf63] after:left-0 after:bottom-0 hover:after:w-full after:transition-all after:duration-300 group" 
                   href="{{ route('password.request') }}">
                    <span class="group-hover:bg-[#00bf63]/10 group-active:bg-[#00bf63]/30 px-2 py-1 rounded transition-all duration-300">¿Olvidaste tu contraseña?</span>
                </a>
            @endif
        </div>

        <!-- Botón de envío -->
        <div>
            <button type="submit" 
                    class="w-full py-3 px-4 bg-[#00bf63] border-2 border-[#00bf63] rounded-md font-bold text-white hover:bg-transparent hover:text-[#00bf63] focus:outline-none focus:ring-2 focus:ring-[#00bf63] active:bg-green-700 active:border-green-700 transition-all duration-300 cursor-pointer transform hover:scale-[1.02]">
                Ingresar
            </button>
        </div>
    </form>
</div>

<style>
/* Estilos adicionales para garantizar que los efectos hover sean visibles */
input:hover, button:hover, a:hover, label:hover {
    filter: brightness(1.2);
}

/* Eliminar el contorno azul predeterminado del navegador */
input:focus, button:focus, a:focus, *:focus {
    outline: none !important;
}

/* Estilos para los campos de texto cuando están activos/en foco */
input:focus, input:active {
    box-shadow: 0 0 0 2px rgba(0, 191, 99, 0.5);
    background-color: rgba(0, 191, 99, 0.05) !important;
    border-color: #00bf63 !important;
}

/* Efecto especial para enlaces al hacer hover */
a:hover {
    text-shadow: 0 0 2px rgba(0, 191, 99, 0.5);
}

/* Efecto especial para enlaces al hacer clic */
a:active, a:focus {
    color: white !important;
    background-color: rgba(0, 191, 99, 0.2);
    border-radius: 4px;
}

/* Efecto para el botón al hacer hover */
button:hover {
    box-shadow: 0 0 10px rgba(0, 191, 99, 0.5);
}

/* Efecto para el botón al hacer clic */
button:active {
    transform: scale(0.98);
    box-shadow: 0 0 5px rgba(0, 191, 99, 0.8);
}
</style>
@endsection