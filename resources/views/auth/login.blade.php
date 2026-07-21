@extends('layouts.guest')

@section('content')
<div class="bg-[#0d0d0d]/90 backdrop-blur border border-[#00bf63]/30 rounded-2xl shadow-2xl shadow-[#00bf63]/10 p-8">

    <div class="text-center mb-7">
        <h2 class="text-2xl font-extrabold tracking-tight">Iniciar Sesión</h2>
        <p class="text-sm text-gray-400 mt-1">Ingresa a tu panel de administración</p>
    </div>

    <!-- Mensaje de sesión (si existe) -->
    @if (session('status'))
        <div class="mb-5 rounded-lg bg-[#00bf63]/10 border border-[#00bf63]/40 px-4 py-2.5 text-sm text-[#00bf63]">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Campo Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-200 mb-1.5">Correo Electrónico</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" list="email-suggestions" placeholder="tucorreo@ejemplo.com"
                       class="block w-full rounded-lg bg-black/60 border border-[#00bf63]/40 text-white placeholder-gray-600 pl-11 pr-4 py-2.5 focus:border-[#00bf63] focus:ring-2 focus:ring-[#00bf63]/40 hover:border-[#00bf63]/70 transition-all duration-200">
            </div>
            @error('email')
                <p class="mt-1.5 text-red-400 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Campo Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-200 mb-1.5">Contraseña</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       placeholder="••••••••"
                       class="block w-full rounded-lg bg-black/60 border border-[#00bf63]/40 text-white placeholder-gray-600 pl-11 pr-11 py-2.5 focus:border-[#00bf63] focus:ring-2 focus:ring-[#00bf63]/40 hover:border-[#00bf63]/70 transition-all duration-200">
                <button type="button" id="togglePassword" aria-label="Mostrar u ocultar contraseña"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-500 hover:text-[#00bf63] transition-colors duration-200">
                    <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-red-400 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Recuérdame + recuperación -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center cursor-pointer group">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded bg-black border border-[#00bf63]/50 text-[#00bf63] focus:ring-[#00bf63] focus:ring-offset-0 cursor-pointer">
                <span class="ml-2 text-sm text-gray-300 group-hover:text-[#00bf63] transition-colors duration-200">Recuérdame</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-[#00bf63] hover:text-green-400 hover:underline transition-colors duration-200"
                   href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <!-- Botón de envío -->
        <button type="submit"
                class="w-full py-3 px-4 rounded-lg font-bold text-white bg-gradient-to-r from-[#00bf63] to-[#00a656] shadow-lg shadow-[#00bf63]/30 hover:shadow-[#00bf63]/50 hover:brightness-110 focus:ring-2 focus:ring-[#00bf63]/60 active:scale-[0.98] transition-all duration-200">
            Ingresar
        </button>
    </form>
</div>

<script>
    // Mostrar/ocultar contraseña
    document.getElementById('togglePassword')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        // Tachar el ícono cuando la contraseña es visible
        document.getElementById('eyeIcon').style.opacity = isPassword ? '0.5' : '1';
    });
</script>
@endsection
