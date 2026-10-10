@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<div class="space-y-6">

    {{-- Banner de Bienvenida y Cargo Meta --}}
    <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-r from-blue-900/90 via-indigo-900/80 to-slate-900 border border-blue-500/30 p-5 sm:p-8 shadow-2xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-5 sm:gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[11px] sm:text-xs font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping"></span>
                    Convocatoria DIAN 2676 &bull; CNSC
                </div>
                <h1 class="text-xl sm:text-3xl font-black text-white tracking-tight">
                    ¡Bienvenido, {{ $user->name }}!
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Preparación especializada para el empleo <strong class="text-white">{{ $activeJob->name }}</strong> ({{ $activeJob->level }}, Grado {{ $activeJob->grade }}). 
                    Tienes a tu disposición <strong class="text-blue-300">{{ $totalJobQuestions }} preguntas situacionales oficiales</strong> con fundamentación jurídica.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
                @if($activeSession)
                    <a href="{{ route('simulacro.view', $activeSession->id) }}"
                       class="w-full sm:w-auto px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/30 flex items-center justify-center gap-2 transition-all transform active:scale-95 animate-pulse text-center">
                        <i class="fa-solid fa-forward-step"></i>
                        Continuar Simulacro en Curso
                    </a>
                @else
                    <form method="POST" action="{{ route('simulacro.iniciar') }}">
                        @csrf
                        <button type="submit"
                                class="w-full sm:w-auto px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2 transition-all transform active:scale-95">
                            <i class="fa-solid fa-play"></i>
                            Iniciar Simulacro Oficial
                        </button>
                    </form>
                @endif

                <a href="{{ route('practica.index') }}"
                   class="w-full sm:w-auto px-4 sm:px-5 py-3 rounded-2xl bg-slate-800/80 hover:bg-slate-700/80 text-white font-bold text-xs uppercase tracking-wider border border-slate-700 flex items-center justify-center gap-2 transition-all active:scale-95">
                    <i class="fa-solid fa-bullseye text-blue-400"></i>
                    Modo Práctica
                </a>
            </div>
        </div>
        
        {{-- Patrón sutil decorativo en fondo --}}
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- Cuadrícula de Métricas Personales (2 cols en móvil, 4 en desktop) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <div class="bg-slate-900/70 border border-slate-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 flex items-center justify-between">
            <div>
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block leading-tight">Simulacros</span>
                <span class="text-xl sm:text-3xl font-black text-white mt-1 block">{{ $totalExams }}</span>
                <span class="text-[10px] sm:text-[11px] text-slate-500 font-medium">Historial</span>
            </div>
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-sm sm:text-xl shrink-0">
                <i class="fa-solid fa-file-signature"></i>
            </div>
        </div>

        <div class="bg-slate-900/70 border border-slate-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 flex items-center justify-between">
            <div>
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block leading-tight">Aprobación</span>
                <span class="text-xl sm:text-3xl font-black {{ $passRate >= 70 ? 'text-emerald-400' : 'text-amber-400' }} mt-1 block">
                    {{ $passRate }}%
                </span>
                <span class="text-[10px] sm:text-[11px] text-slate-500 font-medium">&ge; 70.00 pts</span>
            </div>
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm sm:text-xl shrink-0">
                <i class="fa-solid fa-trophy"></i>
            </div>
        </div>

        <div class="bg-slate-900/70 border border-slate-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 flex items-center justify-between">
            <div>
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block leading-tight">Promedio</span>
                <span class="text-xl sm:text-3xl font-black text-white mt-1 block">
                    {{ number_format($avgScore, 2) }}
                </span>
                <span class="text-[10px] sm:text-[11px] text-slate-500 font-medium">Sobre 100</span>
            </div>
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-sm sm:text-xl shrink-0">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>

        <div class="bg-slate-900/70 border border-slate-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 flex items-center justify-between">
            <div>
                <span class="text-[11px] sm:text-xs font-semibold text-slate-400 block leading-tight">Fallos</span>
                <span class="text-xl sm:text-3xl font-black {{ $failedQuestionsCount > 0 ? 'text-rose-400' : 'text-emerald-400' }} mt-1 block">
                    {{ $failedQuestionsCount }}
                </span>
                <a href="{{ route('repaso.fallos') }}" class="text-[10px] sm:text-[11px] text-rose-300 hover:underline font-medium">
                    {{ $failedQuestionsCount > 0 ? 'Repasar &rarr;' : 'Sin fallos' }}
                </a>
            </div>
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center text-sm sm:text-xl shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

    </div>

    {{-- 4 Modos Principales de Estudio --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Tarjeta 1: Simulacro Oficial --}}
        <div class="bg-slate-900/80 border border-slate-800 hover:border-amber-500/50 rounded-2xl p-5 transition-all flex flex-col justify-between group">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
                <h3 class="text-base font-bold text-white group-hover:text-amber-400 transition-colors">
                    Simulacro Oficial
                </h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Examen cronometrado con temporizador de 5 horas, hoja de respuestas óptica y regla eliminatoria oficial (&ge; 70.00 pts).
                </p>
            </div>
            <form method="POST" action="{{ route('simulacro.iniciar') }}" class="mt-4">
                @csrf
                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 transition-colors">
                    Iniciar Examen
                </button>
            </form>
        </div>

        {{-- Tarjeta 2: Práctica Focalizada --}}
        <div class="bg-slate-900/80 border border-slate-800 hover:border-blue-500/50 rounded-2xl p-5 transition-all flex flex-col justify-between group">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/30 text-blue-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <h3 class="text-base font-bold text-white group-hover:text-blue-400 transition-colors">
                    Práctica por Eje
                </h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Entrena eje por eje con <strong>retroalimentación inmediata</strong>: conoce la clave correcta y el fundamento legal al instante.
                </p>
            </div>
            <a href="{{ route('practica.index') }}" class="mt-4 w-full py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 transition-colors text-center">
                Elegir Eje Temático
            </a>
        </div>

        {{-- Tarjeta 3: Repaso de Fallos --}}
        <div class="bg-slate-900/80 border border-slate-800 hover:border-rose-500/50 rounded-2xl p-5 transition-all flex flex-col justify-between group">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <h3 class="text-base font-bold text-white group-hover:text-rose-400 transition-colors">
                    Repaso de Fallos
                </h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Reintenta exclusivamente las preguntas que tuviste erradas en simulacros anteriores para fijar el aprendizaje.
                </p>
            </div>
            <a href="{{ route('repaso.fallos') }}" class="mt-4 w-full py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-rose-300 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 transition-colors text-center border border-slate-700">
                Repasar ({{ $failedQuestionsCount }})
            </a>
        </div>

        {{-- Tarjeta 4: Banco de Preguntas --}}
        <div class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/50 rounded-2xl p-5 transition-all flex flex-col justify-between group">
            <div class="space-y-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <h3 class="text-base font-bold text-white group-hover:text-purple-400 transition-colors">
                    Banco de Preguntas
                </h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Explora y filtra las {{ $totalJobQuestions }} preguntas del empleo con sus casos, opciones y citas normativas completas.
                </p>
            </div>
            <a href="{{ route('banco.preguntas') }}" class="mt-4 w-full py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-purple-300 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 transition-colors text-center border border-slate-700">
                Explorar Banco
            </a>
        </div>

    </div>

    {{-- Sección de Ejes Temáticos del Cargo --}}
    <div class="bg-slate-900/70 border border-slate-800/80 rounded-3xl p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-blue-400"></i>
                    Ejes Temáticos del Empleo: {{ $activeJob->name }}
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Componente Funcional Específico, Básico, Comportamental y de Integridad
                </p>
            </div>
            <span class="text-xs font-bold px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-300 rounded-full">
                {{ $thematicAxes->count() }} Ejes Activos
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pt-2">
            @foreach($thematicAxes as $axis)
                <a href="{{ route('practica.eje', $axis->id) }}"
                   class="p-4 rounded-2xl bg-slate-800/40 border border-slate-700/50 hover:bg-slate-800/80 hover:border-blue-500/50 transition-all flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $axis->category === 'integridad' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($axis->category === 'comportamental' ? 'bg-sky-500/10 text-sky-400 border border-sky-500/20' : 'bg-slate-700/60 text-slate-300') }}">
                                {{ str_replace('_', ' ', $axis->category) }}
                            </span>
                            <span class="text-xs font-bold text-blue-400 group-hover:scale-105 transition-transform">
                                {{ $axis->questions_count }} preguntas
                            </span>
                        </div>
                        <h4 class="text-xs font-bold text-white group-hover:text-blue-300 transition-colors line-clamp-2">
                            {{ $axis->name }}
                        </h4>
                        <p class="text-[11px] text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                            {{ $axis->description }}
                        </p>
                    </div>
                    <div class="mt-3 pt-2 border-t border-slate-700/40 flex items-center justify-between text-[11px] text-blue-400 font-semibold">
                        <span>Practicar ahora</span>
                        <i class="fa-solid fa-arrow-right text-[10px] transform group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Historial Reciente de Simulacros --}}
    @if($recentSessions->isNotEmpty())
        <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl sm:rounded-3xl p-4 sm:p-8 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-400"></i>
                    Tus Simulacros Recientes
                </h2>
                <span class="text-xs text-slate-400 font-medium">
                    {{ $recentSessions->count() }} evaluacion(es)
                </span>
            </div>

            {{-- Versión Móvil: Tarjetas Táctiles Ergonomicas (Solo celulares) --}}
            <div class="space-y-3 sm:hidden">
                @foreach($recentSessions as $s)
                    <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3 shadow-inner">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-bold text-xs text-white block">
                                    @if($s->mode === 'simulacro_oficial')
                                        <span class="text-amber-400"><i class="fa-solid fa-stopwatch mr-1"></i> Simulacro Oficial</span>
                                    @elseif($s->mode === 'practica_eje')
                                        <span class="text-blue-400"><i class="fa-solid fa-bullseye mr-1"></i> {{ $s->thematicAxis->name ?? 'Práctica' }}</span>
                                    @else
                                        <span class="text-rose-400"><i class="fa-solid fa-rotate-left mr-1"></i> Repaso de Fallos</span>
                                    @endif
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono">
                                    {{ $s->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            @if($s->is_passed)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 font-black text-[9px] uppercase">
                                    Aprobado
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-300 font-black text-[9px] uppercase">
                                    No Aprobado
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center p-2 rounded-lg bg-slate-900 border border-slate-800/80">
                            <div>
                                <span class="text-[9px] text-slate-500 block">Puntaje</span>
                                <strong class="text-xs font-black text-white font-mono">{{ number_format($s->score, 2) }}</strong>
                            </div>
                            <div>
                                <span class="text-[9px] text-emerald-500 block">Aciertos</span>
                                <strong class="text-xs font-black text-emerald-400">{{ $s->correct_answers }}</strong>
                            </div>
                            <div>
                                <span class="text-[9px] text-rose-500 block">Fallos</span>
                                <strong class="text-xs font-black text-rose-400">{{ $s->incorrect_answers }}</strong>
                            </div>
                        </div>

                        <a href="{{ route('simulacro.resultado', $s->id) }}"
                           class="w-full py-2 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors border border-blue-500/30 active:scale-95">
                            <span>Ver Auditoría Completa</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Versión Tablet & Desktop: Tabla Completa --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-3">Fecha</th>
                            <th class="py-3 px-3">Modo</th>
                            <th class="py-3 px-3 text-center">Correctas</th>
                            <th class="py-3 px-3 text-center">Incorrectas</th>
                            <th class="py-3 px-3 text-center">Puntaje</th>
                            <th class="py-3 px-3 text-center">Estado</th>
                            <th class="py-3 px-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @foreach($recentSessions as $s)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="py-3 px-3 whitespace-nowrap text-slate-400 font-mono">
                                    {{ $s->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-white">
                                    @if($s->mode === 'simulacro_oficial')
                                        <span class="text-amber-400">Simulacro Oficial</span>
                                    @elseif($s->mode === 'practica_eje')
                                        <span class="text-blue-400">Práctica: {{ $s->thematicAxis->name ?? 'Eje' }}</span>
                                    @else
                                        <span class="text-rose-400">Repaso de Fallos</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-emerald-400">
                                    {{ $s->correct_answers }}
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-rose-400">
                                    {{ $s->incorrect_answers }}
                                </td>
                                <td class="py-3 px-3 text-center font-black text-sm text-white font-mono">
                                    {{ number_format($s->score, 2) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @if($s->is_passed)
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 font-bold text-[10px]">
                                            APROBADO
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-300 font-bold text-[10px]">
                                            NO APROBADO
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="{{ route('simulacro.resultado', $s->id) }}"
                                       class="px-2.5 py-1 rounded-lg bg-blue-600/20 hover:bg-blue-600/40 text-blue-300 font-semibold transition-colors text-[11px]">
                                        Ver Detalle
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
