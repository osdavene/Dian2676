<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#070b14">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DIAN 2676') }} — @yield('title', 'Acceso')</title>

    <!-- Fuentes Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Estilos Locales Compilados y FontAwesome (Sin depender de CDN externa) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    <style>
        html { -webkit-tap-highlight-color: transparent; }
        .touch-action-manipulation { touch-action: manipulation; }
    </style>
</head>
<body class="min-h-full font-sans antialiased text-slate-100 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex flex-col justify-center py-6 px-4 sm:py-12 sm:px-6 lg:px-8 touch-action-manipulation">
    
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-5">
        <div class="inline-flex items-center justify-center p-3 bg-blue-600/20 border border-blue-500/30 rounded-2xl shadow-xl shadow-blue-500/10 mb-4 backdrop-blur-md">
            <i class="fa-solid fa-scale-balanced text-3xl text-blue-400"></i>
        </div>
        <h2 class="text-3xl font-black tracking-tight text-white uppercase">
            CONCURSO DIAN 2676
        </h2>
        <p class="mt-1 text-sm text-blue-200/80 font-medium">
            Simulador Oficial de Pruebas de Juicio Situacional (PJS)
        </p>
        <span class="inline-block mt-2 px-3 py-1 bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold rounded-full">
            CNSC &bull; Universidad del Área Andina
        </span>
    </div>

    <div class="mt-4 sm:mx-auto sm:w-full sm:max-w-md">
        @if(session('success'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-4 p-4 rounded-xl bg-blue-500/10 border border-blue-500/30 text-blue-300 text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-info text-blue-400"></i>
                <div>{{ session('info') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <div class="bg-slate-800/80 backdrop-blur-xl py-6 px-5 sm:py-8 sm:px-10 shadow-2xl border border-slate-700/60 rounded-2xl sm:rounded-3xl">
            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} Simulador DIAN 2676 &bull; Basado en acuerdos de convocatoria y pruebas reales
        </p>
    </div>

    {{-- Botón Flotante de Soporte WhatsApp (Número protegido en servidor) --}}
    <aside aria-label="Soporte WhatsApp" class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-50">
        <a href="{{ route('support.whatsapp') }}" target="_blank" rel="noopener noreferrer"
           title="¿Tienes preguntas? Escríbenos por WhatsApp"
           style="background-color: #25D366; color: #ffffff;"
           class="flex items-center gap-2.5 px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-full shadow-2xl shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-105 active:scale-95 transition-all text-white font-bold text-xs sm:text-sm group">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 fill-current flex-shrink-0" viewBox="0 0 24 24">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            <span class="hidden sm:inline-block font-extrabold tracking-wide">¿Dudas? Escríbenos</span>
        </a>
    </aside>
</body>
</html>
