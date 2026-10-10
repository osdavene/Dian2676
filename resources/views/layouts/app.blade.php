<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#070b14">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DIAN 2676') }} — @yield('title', 'Simulador')</title>

    <!-- Fuentes Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Estilos Locales Compilados y FontAwesome (Sin depender de CDN externa) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    <style>
        /* Optimizaciones Mobile-First y Ergonomía Táctil */
        html { -webkit-tap-highlight-color: transparent; }
        .touch-action-manipulation { touch-action: manipulation; }
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-full font-sans antialiased text-slate-100 bg-[#070b14] flex flex-col touch-action-manipulation">

    {{-- Barra Superior de Navegación Responsive --}}
    <nav class="bg-slate-900/95 border-b border-slate-800/80 sticky top-0 z-50 backdrop-blur-md" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                {{-- Logo y Nombre --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-scale-balanced text-white text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-base font-black tracking-wider text-white">DIAN 2676</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-600/30 text-blue-300 border border-blue-500/30 uppercase">
                                    Simulador
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 block -mt-1 font-medium">Concurso de Méritos CNSC</span>
                        </div>
                    </a>
                </div>

                {{-- Navegación Central (Desktop) --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" 
                       class="px-3 py-2 rounded-xl text-xs font-semibold {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }} transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-house"></i>
                        Inicio
                    </a>

                    <a href="{{ route('practica.index') }}" 
                       class="px-3 py-2 rounded-xl text-xs font-semibold {{ request()->routeIs('practica.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }} transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-bullseye"></i>
                        Práctica por Eje
                    </a>

                    <a href="{{ route('repaso.fallos') }}" 
                       class="px-3 py-2 rounded-xl text-xs font-semibold {{ request()->routeIs('repaso.fallos') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }} transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left"></i>
                        Repaso de Fallos
                    </a>

                    <a href="{{ route('banco.preguntas') }}" 
                       class="px-3 py-2 rounded-xl text-xs font-semibold {{ request()->routeIs('banco.preguntas') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }} transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-database"></i>
                        Banco de Preguntas
                    </a>

                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" 
                           class="px-3 py-2 rounded-xl text-xs font-bold {{ request()->routeIs('admin.*') ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/30' : 'text-amber-300 hover:text-white hover:bg-amber-500/20' }} transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved"></i>
                            Control Central
                        </a>
                    @endif
                </div>

                {{-- Cargo Activo y Usuario (Desktop) --}}
                <div class="hidden md:flex items-center gap-3">
                    
                    {{-- Badge de Cargo Activo --}}
                    @php
                        $userJob = Auth::user()->jobProfile;
                        $allJobsList = \App\Models\JobProfile::where('is_active', true)->get();
                    @endphp
                    @if(Auth::user()->isAdmin())
                        <div class="relative">
                            <button type="button" onclick="document.getElementById('cargo-modal').classList.toggle('hidden')"
                                    class="flex items-center gap-2 px-3 py-1.5 bg-slate-800/90 hover:bg-slate-700/80 border border-slate-700/80 rounded-xl text-xs transition-all text-left"
                                    title="Modo Administrador: Alternar cargo de evaluación">
                                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                <div>
                                    <span class="text-[10px] text-amber-300/80 block leading-none font-medium">Cargo simulado:</span>
                                    <span class="font-bold text-white text-xs leading-tight">
                                        {{ $userJob->name ?? Auth::user()->target_cargo }}
                                    </span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-900/90 border border-slate-800 rounded-xl text-xs text-left cursor-default select-none shadow-sm"
                             title="Tu cargo de concurso ha sido fijado por la Administración">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            <div>
                                <span class="text-[10px] text-slate-400 block leading-none font-medium">Cargo Asignado:</span>
                                <span class="font-bold text-slate-200 text-xs leading-tight">
                                    {{ $userJob->name ?? Auth::user()->target_cargo }}
                                </span>
                            </div>
                            <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700/60 font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-lock text-[9px] text-slate-400"></i> Fijo
                            </span>
                        </div>
                    @endif

                    {{-- Menú de Usuario --}}
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-800">
                        <a href="{{ route('perfil') }}" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-800/70 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-blue-600/30 border border-blue-500/40 text-blue-300 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="text-xs font-semibold text-slate-200 max-w-[120px] truncate">
                                {{ Auth::user()->name }}
                            </span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Cerrar Sesión"
                                    class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors text-xs">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>

                </div>

                {{-- Controles Móviles (Avatar + Toggle Menú) --}}
                <div class="md:hidden flex items-center gap-2.5">
                    <a href="{{ route('perfil') }}" title="Mi Perfil"
                       class="w-8 h-8 rounded-lg bg-blue-600/30 border border-blue-500/40 text-blue-300 flex items-center justify-center font-bold text-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </a>
                    <button type="button" 
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2.5 rounded-xl text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 focus:outline-none transition-all"
                            aria-label="Abrir menú de navegación">
                        <i class="fa-solid text-base transition-transform" :class="mobileMenuOpen ? 'fa-xmark rotate-90 text-rose-400' : 'fa-bars'"></i>
                    </button>
                </div>

            </div>
        </div>

        {{-- Menú Desplegable Móvil con Animación y Alta Ergonomía Táctil --}}
        <div id="mobile-menu" 
             x-show="mobileMenuOpen" 
             x-cloak
             @click.outside="mobileMenuOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-slate-800 bg-slate-900/98 backdrop-blur-xl px-4 pt-3.5 pb-6 space-y-2 shadow-2xl">
            
            {{-- Ficha Móvil del Aspirante / Administrador --}}
            <div class="p-3.5 bg-slate-800/70 border border-slate-700/60 rounded-2xl mb-3 flex items-center justify-between shadow-inner">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div>
                        <span class="font-bold text-white text-xs block leading-tight">{{ Auth::user()->name }}</span>
                        <span class="text-[11px] text-blue-400 font-semibold block">{{ $userJob->name ?? Auth::user()->target_cargo }}</span>
                        <span class="text-[9px] text-slate-400 uppercase tracking-wider block font-medium">{{ Auth::user()->email }}</span>
                    </div>
                </div>
                @if(Auth::user()->isAdmin())
                    <button type="button" @click="mobileMenuOpen = false; document.getElementById('cargo-modal').classList.remove('hidden')"
                            class="text-xs px-2.5 py-1.5 bg-amber-500/20 text-amber-300 border border-amber-500/40 rounded-xl font-bold flex items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-arrows-rotate text-[10px]"></i> Cambiar
                    </button>
                @else
                    <span class="text-[10px] px-2.5 py-1 bg-slate-900 text-slate-400 rounded-lg border border-slate-700/70 flex items-center gap-1 font-bold">
                        <i class="fa-solid fa-lock text-[8px] text-slate-400"></i> Fijo
                    </span>
                @endif
            </div>

            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                    <i class="fa-solid fa-house text-sm w-5 text-center text-blue-400"></i>
                    <span>Inicio</span>
                </a>
                
                <a href="{{ route('practica.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold {{ request()->routeIs('practica.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                    <i class="fa-solid fa-bullseye text-sm w-5 text-center text-emerald-400"></i>
                    <span>Práctica por Eje</span>
                </a>
                
                <a href="{{ route('repaso.fallos') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold {{ request()->routeIs('repaso.fallos') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                    <i class="fa-solid fa-rotate-left text-sm w-5 text-center text-amber-400"></i>
                    <span>Repaso de Fallos</span>
                </a>
                
                <a href="{{ route('banco.preguntas') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold {{ request()->routeIs('banco.preguntas') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                    <i class="fa-solid fa-database text-sm w-5 text-center text-purple-400"></i>
                    <span>Banco de Preguntas</span>
                </a>

                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-black {{ request()->routeIs('admin.*') ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/30' : 'text-amber-300 hover:text-white hover:bg-amber-500/20' }} transition-colors">
                        <i class="fa-solid fa-shield-halved text-sm w-5 text-center text-amber-400"></i>
                        <span>Control Central (Admin)</span>
                    </a>
                @endif

                <a href="{{ route('perfil') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-bold {{ request()->routeIs('perfil') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }} transition-colors">
                    <i class="fa-solid fa-user-gear text-sm w-5 text-center text-slate-400"></i>
                    <span>Mi Perfil y Credenciales</span>
                </a>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="pt-3 border-t border-slate-800/80">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-3 text-xs font-bold text-rose-400 hover:bg-rose-500/10 rounded-xl transition-colors">
                    <i class="fa-solid fa-arrow-right-from-bracket text-sm w-5 text-center"></i>
                    <span>Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </nav>

    @if(Auth::user()->isAdmin())
    {{-- Modal Selector de Cargo (Exclusivo Administradores) --}}
    <div id="cargo-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-id-badge text-blue-400"></i>
                    Seleccionar Cargo de Entrenamiento
                </h3>
                <button type="button" onclick="document.getElementById('cargo-modal').classList.add('hidden')"
                        class="text-slate-400 hover:text-white p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <p class="text-xs text-slate-400 mt-3 mb-4">
                Elige el cargo para el cual deseas que el simulador y las prácticas se ajusten de manera inmediata:
            </p>
            <div class="space-y-2.5">
                @foreach($allJobsList as $j)
                    <form method="POST" action="{{ route('cambiar.cargo', $j->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left p-3.5 rounded-xl border transition-all flex items-center justify-between {{ ($userJob->id ?? 1) == $j->id ? 'bg-blue-600/20 border-blue-500/60 text-white' : 'bg-slate-800/40 border-slate-700/50 hover:bg-slate-800 text-slate-300' }}">
                            <div>
                                <div class="font-bold text-xs text-white">{{ $j->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $j->level }} (Grado {{ $j->grade }})</div>
                                <div class="text-[10px] text-blue-300/80">{{ $j->subprocess ?? $j->process }}</div>
                            </div>
                            @if(($userJob->id ?? 1) == $j->id)
                                <span class="px-2 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold">Activo</span>
                            @else
                                <span class="text-xs text-slate-500 hover:text-blue-400">Seleccionar</span>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Mensajes Flash --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-5 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
                <div class="font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-5 p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-300 text-xs sm:text-sm flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-circle-info text-blue-400 text-base"></i>
                <div class="font-medium">{{ session('info') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-triangle-exclamation text-rose-400 text-base"></i>
                <div class="font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-900 bg-slate-950/80 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4">
            <p>Simulador DIAN Convocatoria 2676 &bull; Concurso de Méritos CNSC</p>
            <p class="text-[11px] text-slate-600 mt-1">Estatuto Tributario Nacional &bull; Decreto 1165 de 2019 &bull; CPACA &bull; Código de Integridad DIAN</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
