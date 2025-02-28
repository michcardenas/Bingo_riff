@extends('layouts.guest')

@section('head')
<style>
    button:focus {
        outline: none !important;
        box-shadow: 0 0 0 2px rgba(0, 191, 99, 0.5) !important;
    }
</style>
@endsection

@section('content')
<div class="bg-black border border-white p-6 rounded-lg">
    <h2 class="text-2xl font-bold text-center mb-6">{{ __('Verificación de Correo') }}</h2>

    <div class="mb-4 text-sm text-white">
        {{ __('Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu dirección de correo electrónico haciendo clic en el enlace que acabamos de enviarte? Si no recibiste el correo, con gusto te enviaremos otro.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-[#00bf63]">
            {{ __('Se ha enviado un nuevo enlace de verificación a la dirección de correo electrónico que proporcionaste durante el registro.') }}
        </div>
    @endif

    <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
            @csrf

            <button type="submit" 
                    class="w-full sm:w-auto py-2 px-4 bg-[#00bf63] border-2 border-[#00bf63] rounded-md font-bold text-white hover:bg-transparent hover:text-[#00bf63] focus:outline-none focus:ring-2 focus:ring-[#00bf63] active:bg-green-700 active:border-green-700 transition-all duration-300 cursor-pointer transform hover:scale-[1.02]">
                {{ __('Reenviar Email de Verificación') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
            @csrf

            <button type="submit" 
                    class="w-full sm:w-auto py-2 px-4 bg-transparent border-2 border-white rounded-md font-bold text-white hover:bg-white hover:text-black focus:outline-none focus:ring-2 focus:ring-white active:bg-gray-300 active:border-gray-300 transition-all duration-300 cursor-pointer">
                {{ __('Cerrar Sesión') }}
            </button>
        </form>
    </div>
</div>

<style>
/* Estilos adicionales para garantizar que los efectos hover sean visibles */
button:hover {
    filter: brightness(1.2);
}

/* Eliminar el contorno azul predeterminado del navegador */
*:focus {
    outline: none !important;
    box-shadow: none !important;
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

/* Estilo específico para el botón de cerrar sesión */
form:last-child button:hover {
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}
</style>
@endsection