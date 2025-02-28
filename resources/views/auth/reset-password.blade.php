@extends('layouts.guest')

@section('head')
<style>
    input:focus {
        outline: none !important;
        box-shadow: 0 0 0 2px rgba(0, 191, 99, 0.5) !important;
        border-color: #00bf63 !important;
    }
</style>
@endsection

@section('content')
<div class="bg-black border border-white p-6 rounded-lg">
    <h2 class="text-2xl font-bold text-center mb-6">{{ __('Restablecer Contraseña') }}</h2>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-white">{{ __('Correo Electrónico') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                   class="mt-1 block w-full rounded-md bg-black border border-[#00bf63] text-white focus:ring-0 focus:ring-offset-0 focus:border-[#00bf63] hover:border-green-400 active:border-white active:bg-gray-900 focus:bg-gray-900 transition-all duration-300">
            @error('email')
                <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-white">{{ __('Nueva Contraseña') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-md bg-black border border-[#00bf63] text-white focus:ring-0 focus:ring-offset-0 focus:border-[#00bf63] hover:border-green-400 active:border-white active:bg-gray-900 focus:bg-gray-900 transition-all duration-300">
            @error('password')
                <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-white">{{ __('Confirmar Contraseña') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-md bg-black border border-[#00bf63] text-white focus:ring-0 focus:ring-offset-0 focus:border-[#00bf63] hover:border-green-400 active:border-white active:bg-gray-900 focus:bg-gray-900 transition-all duration-300">
            @error('password_confirmation')
                <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end mt-6">
            <button type="submit" 
                    class="py-2 px-4 bg-[#00bf63] border-2 border-[#00bf63] rounded-md font-bold text-white hover:bg-transparent hover:text-[#00bf63] focus:outline-none focus:ring-2 focus:ring-[#00bf63] active:bg-green-700 active:border-green-700 transition-all duration-300 cursor-pointer transform hover:scale-[1.02]">
                {{ __('Restablecer Contraseña') }}
            </button>
        </div>
    </form>
</div>

<style>
/* Estilos adicionales para garantizar que los efectos hover sean visibles */
input:hover, button:hover, a:hover, label:hover {
    filter: brightness(1.2);
}

/* Eliminar el contorno azul predeterminado del navegador - solución más agresiva */
*:focus {
    outline: none !important;
    box-shadow: none !important;
}

input:focus {
    outline: none !important;
    box-shadow: 0 0 0 1px #00bf63 !important;
    border-color: #00bf63 !important;
    -webkit-box-shadow: 0 0 0 1px #00bf63 !important;
    -moz-box-shadow: 0 0 0 1px #00bf63 !important;
}

/* Eliminar específicamente el borde azul en WebKit/Safari */
input, textarea, button, select, a {
    -webkit-tap-highlight-color: rgba(0,0,0,0);
}

/* Estilos para los campos de texto cuando están activos/en foco */
input:focus, input:active {
    box-shadow: 0 0 0 2px rgba(0, 191, 99, 0.5) !important;
    background-color: rgba(0, 191, 99, 0.05) !important;
    border-color: #00bf63 !important;
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

/* Parche específico para Firefox */
@-moz-document url-prefix() {
    input:focus {
        outline: none !important;
        box-shadow: 0 0 0 1px #00bf63 !important;
        border-color: #00bf63 !important;
    }
}
</style>
@endsection