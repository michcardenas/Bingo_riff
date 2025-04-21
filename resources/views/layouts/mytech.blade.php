<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyTech Solutions</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(25deg, #0d4399, #5f70ad, #94a0c0, #c5d4d3); }
    </style>
</head>
<body class="font-sans text-white">
    <main class="container mx-auto p-6 text-center flex flex-col items-center justify-center min-h-screen">
        <img src="{{ asset('images/logo-mytech.png') }}" alt="MyTech Solutions" class="w-80 drop-shadow-lg mb-6">
        @yield('content')
    </main>
    <footer class="text-center p-4 mt-6 bg-gray-800 opacity-80">
        &copy; {{ date('Y') }} MyTech Solutions - Bogotá, Colombia
    </footer>
</body>
</html>

