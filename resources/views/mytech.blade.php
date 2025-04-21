@extends('layouts.mytech')

@section('content')
<div class="text-center">

  <h1 class="text-7xl font-extrabold mb-6 bg-clip-text text bg-gradient-to-r from-yellow-400 to-orange-500 animate-bounce 
drop-shadow-lg">
        Innovación en Tecnología
    </h1>
    <p class="text-lg text-gray-100 max-w-3xl mb-6 leading-relaxed">
        En <span class="text-orange-400 font-bold">MyTech Solutions</span>, ofrecemos soluciones tecnológicas de última generación.
        Desde desarrollo web hasta inteligencia artificial, trabajamos para potenciar tu negocio en la era digital.
    </p>
    <p class="text-xl font-semibold text-orange-300 mb-6">
        📧 Contacto: <a href="mailto:mytechsolutionsas@gmail.com" class="underline">mytechsolutionsas@gmail.com</a>
    </p>
    <a href="https://wa.me/+573024899201" target="_blank"
       class="bg-green-500 text-white px-10 py-5 rounded-full text-lg font-bold shadow-xl transition-all transform hover:scale-110 
hover:bg-green-600">
        💬 Contáctanos por WhatsApp
    </a>
    <div class="mt-12 text-gray-300 text-sm opacity-80">
        &copy; {{ date('Y') }} MyTech Solutions - Bogotá, Colombia
    </div>
</div>
@endsection
