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

</body>
</html>
