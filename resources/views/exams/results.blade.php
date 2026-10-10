@extends('layouts.app')

@section('title', 'Resultados del Examen — DIAN 2676')

@section('content')
<div class="space-y-8">

    {{-- Encabezado y Tarjeta Principal de Puntaje --}}
    <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 border border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-2xl relative overflow-hidden">
        
        {{-- Efecto de Luz de Fondo según Aprobado o Reprobado --}}
        @if($session->is_passed)
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        @else
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>
        @endif

        <div class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            
            {{-- Columna 1 & 2: Veredicto y Detalles --}}
            <div class="md:col-span-2 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $session->is_passed ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-400 border border-rose-500/40' }}">
                        {{ $session->is_passed ? 'Aspirante Aprobado' : 'Aspirante No Aprobado' }}
                    </span>
                    <span class="text-xs text-slate-400">
                        Sesión #{{ $session->id }} &bull; {{ $session->completed_at ? $session->completed_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}
                    </span>
                    @if($session->user)
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-500/30 text-blue-300 font-semibold">
                            <i class="fa-solid fa-user text-[10px]"></i> {{ $session->user->name }} ({{ $session->user->email }})
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
                    @if(Auth::user()->isAdmin() && Auth::id() !== $session->user_id)
                        Auditoría de Examen: {{ $session->user->name ?? 'Aspirante #' . $session->user_id }}
                    @else
                        {{ $session->is_passed ? '¡Felicitaciones! Has superado el umbral eliminatorio' : 'No alcanzaste el puntaje mínimo de 70.00 pts' }}
                    @endif
                </h1>

                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl">
                    De acuerdo con los acuerdos de convocatoria de la CNSC y DIAN, el componente eliminatorio de competencias funcionales exige un puntaje mínimo aprobatorio de <strong>70.00 puntos</strong> sobre 100.
                </p>

                <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 sm:gap-3 pt-2">
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.sessions') }}" 
                           class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-arrow-left"></i>
                            Volver al Monitoreo General
                        </a>
                        @if($session->user_id)
                            <a href="{{ route('admin.users.detail', $session->user_id) }}" 
                               class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-colors flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-user-gear"></i>
                                Ficha del Aspirante
                            </a>
                        @endif
                    @else
                        <a href="{{ route('repaso.fallos') }}" 
                           class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 font-bold text-xs transition-colors flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-rotate-left"></i>
                            Repasar Fallos de esta Sesión
                        </a>

                        <form method="POST" action="{{ route('simulacro.iniciar') }}" class="w-full sm:w-auto inline-block">
                            @csrf
                            <button type="submit" 
                                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-play"></i>
                                Iniciar Nuevo Simulacro
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('dashboard') }}" 
                       class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-gauge-high"></i>
                        Ir al Dashboard
                    </a>
                </div>
            </div>

            {{-- Columna 3: Tarjeta de Puntaje Oficial --}}
            <div class="text-center p-5 sm:p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 shadow-inner">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                    Calificación Oficial CNSC
                </span>
                <div class="text-4xl sm:text-5xl font-black {{ $session->is_passed ? 'text-emerald-400' : 'text-rose-400' }} tracking-tight">
                    {{ number_format($session->score, 2) }}
                </div>
                <div class="text-xs text-slate-400 mt-1 font-semibold">
                    sobre 100.00 puntos
                </div>
                <div class="mt-3 pt-3 border-t border-slate-800 text-[11px] text-slate-400">
                    Cargo: <strong class="text-slate-200">{{ $session->jobProfile->name ?? 'Analista III' }}</strong>
                </div>
            </div>

        </div>

        {{-- 4 Indicadores Estadísticos --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 mt-6 sm:mt-8 pt-5 sm:pt-6 border-t border-slate-800/80">
            
            <div class="p-3.5 rounded-xl bg-emerald-950/20 border border-emerald-500/20 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-medium block">Aciertos</span>
                    <span class="text-base font-extrabold text-white">
                        {{ $session->correct_answers }} / {{ $session->total_questions }}
                    </span>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-rose-950/20 border border-rose-500/20 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-medium block">Errores</span>
                    <span class="text-base font-extrabold text-rose-300">
                        {{ $session->incorrect_answers }}
                    </span>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-800/40 border border-slate-700/40 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-slate-800 text-slate-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-circle-minus"></i>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-medium block">Omitidas</span>
                    <span class="text-base font-extrabold text-slate-300">
                        {{ $session->omitted_answers }}
                    </span>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-blue-950/20 border border-blue-500/20 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-medium block">Tiempo Empleado</span>
                    <span class="text-base font-extrabold text-blue-300">
                        @php
                            $s = $session->time_spent_seconds ?? 0;
                            $hrs = floor($s / 3600);
                            $mins = floor(($s % 3600) / 60);
                            $secs = $s % 60;
                        @endphp
                        {{ sprintf('%02dh %02dm %02ds', $hrs, $mins, $secs) }}
                    </span>
                </div>
            </div>

        </div>

    </div>

    {{-- Desglose por Componente de Competencias CNSC --}}
    @if(isset($competencyStats) && count($competencyStats) > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($competencyStats as $k => $c)
                @if($c['total'] > 0)
                    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-3 shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-blue-500/20 text-blue-300">
                                {{ $k === 'BASICA' ? 'BÁSICAS' : ($k === 'CONDUCTUAL' ? 'CONDUCTUALES' : 'FUNCIONALES') }}
                            </span>
                            <span class="font-mono font-black text-sm {{ $c['percent'] >= 70 ? 'text-emerald-400' : 'text-amber-400' }}">
                                {{ $c['percent'] }}%
                            </span>
                        </div>
                        <h4 class="text-xs font-bold text-white">{{ $c['name'] }}</h4>
                        <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $c['percent'] >= 70 ? 'bg-emerald-500' : ($c['percent'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $c['percent'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-slate-400 font-medium pt-1">
                            <span>Aciertos: <strong class="text-white">{{ $c['correct'] }}</strong> / {{ $c['total'] }}</span>
                            <span>{{ $c['percent'] >= 70 ? 'Supera umbral' : 'A reforzar' }}</span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Desglose de Desempeño por Eje Temático --}}
    @if(count($axisStats) > 0)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-blue-400"></i>
                Desempeño por Eje Temático Evaluado
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($axisStats as $axisName => $stats)
                    @php
                        $axisPercent = $stats['total'] > 0 ? round(($stats['correct'] / $stats['total']) * 100, 1) : 0;
                    @endphp
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-200 truncate max-w-[280px]">{{ $axisName }}</span>
                            <span class="font-mono font-bold {{ $axisPercent >= 70 ? 'text-emerald-400' : 'text-amber-400' }}">
                                {{ $stats['correct'] }}/{{ $stats['total'] }} ({{ $axisPercent }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $axisPercent >= 70 ? 'bg-emerald-500' : ($axisPercent >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                 style="width: {{ $axisPercent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Plan de Mejora Pedagógico y Diagnóstico Personalizado --}}
    <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/30 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-5 shadow-2xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-bold uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-compass"></i>
                    Diagnóstico Pedagógico y Plan de Mejora
                </div>
                <h3 class="text-lg font-black text-white">
                    ¿En qué debes trabajar más para asegurar tu nombramiento en la DIAN?
                </h3>
                <p class="text-xs text-slate-300 max-w-3xl leading-relaxed">
                    Identificamos los ejes temáticos donde tu puntaje estuvo por debajo del 70.00% eliminatorio. Reforzar estas áreas de forma focalizada garantizará el máximo rendimiento en la prueba real.
                </p>
            </div>
            @if(isset($improvementPlan) && count($improvementPlan) > 0)
                <span class="px-3 py-1.5 rounded-xl bg-rose-500/20 border border-rose-500/30 text-rose-300 text-xs font-extrabold flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{ count($improvementPlan) }} temas a priorizar
                </span>
            @else
                <span class="px-3 py-1.5 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-xs font-extrabold flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-check"></i>
                    ¡Dominio en todos los ejes!
                </span>
            @endif
        </div>

        @if(isset($improvementPlan) && count($improvementPlan) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                @foreach($improvementPlan as $plan)
                    <div class="p-5 rounded-2xl bg-slate-950/80 border {{ $plan['priority'] === 'CRÍTICA' ? 'border-rose-500/40' : 'border-amber-500/30' }} space-y-3 flex flex-col justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider {{ $plan['priority'] === 'CRÍTICA' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                    Prioridad {{ $plan['priority'] }} &bull; {{ $plan['percent'] }}%
                                </span>
                                <span class="text-xs font-mono text-slate-400">
                                    {{ $plan['incorrect'] }} fallos de {{ $plan['total'] }} reactivos
                                </span>
                            </div>

                            <h4 class="text-sm font-extrabold text-white">{{ $plan['axis_name'] }}</h4>

                            @if(!empty($plan['legal_references']))
                                <div class="text-[11px] text-slate-400 space-y-1 pt-1">
                                    <strong class="text-slate-300 block text-[10px] uppercase font-bold">Normas y Fundamentos Clave a Estudiar:</strong>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($plan['legal_references'] as $ref)
                                            <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-blue-300 text-[10px] font-mono">
                                                {{ \Illuminate\Support\Str::limit($ref, 45) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-900 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400 font-medium">Entrenamiento específico</span>
                            @if($plan['axis_id'])
                                <a href="{{ route('practica.eje', $plan['axis_id']) }}" 
                                   class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-colors flex items-center gap-1.5 shadow-md shadow-blue-600/30">
                                    <i class="fa-solid fa-play text-[10px]"></i>
                                    Entrenar este Eje
                                </a>
                            @else
                                <a href="{{ route('practica.index') }}" 
                                   class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-colors">
                                    Ver Prácticas
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 rounded-2xl bg-emerald-950/20 border border-emerald-500/30 text-emerald-200 text-xs flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-2xl text-emerald-400"></i>
                <div>
                    <h4 class="font-bold text-sm text-white">Rendimiento Integral Sólido</h4>
                    <p class="text-slate-300 mt-0.5">Has obtenido una efectividad superior al 70.00% en todas las temáticas. Para mantener tu ventaja competitiva, continúa realizando simulacros completos para perfeccionar tu velocidad y gestión del tiempo.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Revisión Detallada Pregunta por Pregunta --}}
    <div class="space-y-4" x-data="{ filter: 'all' }">
        
        <div class="flex flex-wrap items-center justify-between gap-4 pb-2 border-b border-slate-800">
            <div>
                <h2 class="text-base font-extrabold text-white flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-400"></i>
                    Retroalimentación y Justificaciones Jurídicas
                </h2>
                <p class="text-xs text-slate-400">
                    Revisa la sustentación con fundamento normativo y doctrina de cada pregunta evaluada
                </p>
            </div>

            {{-- Filtros de visualización --}}
            <div class="grid grid-cols-3 sm:flex items-center gap-1.5 p-1 bg-slate-900 border border-slate-800 rounded-xl text-xs w-full sm:w-auto">
                <button type="button" @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg transition-colors text-center">
                    Todas ({{ $answers->count() }})
                </button>
                <button type="button" @click="filter = 'incorrect'"
                        :class="filter === 'incorrect' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg transition-colors text-center">
                    Solo Fallos ({{ $session->incorrect_answers }})
                </button>
                <button type="button" @click="filter = 'correct'"
                        :class="filter === 'correct' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 sm:px-3 py-1.5 rounded-lg transition-colors text-center">
                    Aciertos ({{ $session->correct_answers }})
                </button>
            </div>
        </div>

        {{-- Lista de Preguntas con Rationale Oficial --}}
        <div class="space-y-4">
            @foreach($answers as $idx => $ans)
                @php
                    $q = $ans->question;
                    $statusType = $ans->is_correct ? 'correct' : ($ans->is_omitted ? 'omitted' : 'incorrect');
                @endphp
                <div x-show="filter === 'all' || (filter === 'incorrect' && '{{ $statusType }}' === 'incorrect') || (filter === 'correct' && '{{ $statusType }}' === 'correct')"
                     class="bg-slate-900 border {{ $ans->is_correct ? 'border-emerald-500/30' : ($ans->is_omitted ? 'border-slate-800' : 'border-rose-500/30') }} rounded-2xl p-4 sm:p-6 shadow-xl space-y-4 transition-all">
                    
                    {{-- Header de la Pregunta --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-800/80">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-black {{ $ans->is_correct ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($ans->is_omitted ? 'bg-slate-800 text-slate-400' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30') }}">
                                #{{ $idx + 1 }} &bull; {{ $ans->is_correct ? 'ACIERTO' : ($ans->is_omitted ? 'OMITIDA' : 'FALLO') }}
                            </span>
                            @if($q->thematicAxis)
                                <span class="px-2 py-0.5 rounded-md bg-slate-800 text-blue-300 text-[11px] font-medium border border-slate-700/60">
                                    {{ $q->thematicAxis->name }}
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-slate-400">
                            Tu respuesta: <strong class="{{ $ans->is_correct ? 'text-emerald-400' : 'text-rose-400' }}">{{ $ans->selected_option ?? 'Ninguna' }}</strong>
                            &bull; Correcta: <strong class="text-emerald-400">{{ $q->correct_option }}</strong>
                        </div>
                    </div>

                    {{-- Caso / Situación (si existe) --}}
                    @if(!empty($q->case_text))
                        <div class="p-4 rounded-xl bg-slate-950/50 border border-slate-800 text-slate-300 text-xs sm:text-sm whitespace-pre-line">
                            <strong class="text-blue-400 block mb-1 font-semibold uppercase tracking-wider text-[11px]">
                                <i class="fa-solid fa-book-open mr-1"></i> Contexto / Caso
                            </strong>
                            {{ $q->case_text }}
                        </div>
                    @endif

                    {{-- Enunciado --}}
                    <p class="text-sm font-semibold text-white leading-relaxed">
                        {{ $q->stem }}
                    </p>

                    {{-- Opciones detalladas --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        @php
                            $letters = $q->options_map;
                        @endphp
                        @foreach($letters as $let => $txt)
                            @if(!empty($txt))
                                <div class="p-3 rounded-xl border flex items-start gap-2.5 {{ $let === $q->correct_option ? 'bg-emerald-950/30 border-emerald-500/50 text-emerald-200' : ($let === $ans->selected_option ? 'bg-rose-950/30 border-rose-500/50 text-rose-200' : 'bg-slate-950/40 border-slate-800/80 text-slate-400') }}">
                                    <span class="w-5 h-5 rounded-md font-bold text-xs flex items-center justify-center shrink-0 {{ $let === $q->correct_option ? 'bg-emerald-600 text-white' : ($let === $ans->selected_option ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300') }}">
                                        {{ $let }}
                                    </span>
                                    <span class="leading-relaxed">{{ $txt }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Retroalimentación / Justificación Jurídica y Fundamento Legal --}}
                    <div class="p-4 rounded-xl bg-blue-950/30 border border-blue-500/30 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-blue-300 uppercase tracking-wider">
                            <i class="fa-solid fa-scale-balanced"></i>
                            Sustentación Oficial y Fundamento Jurídico
                        </div>
                        <p class="text-xs sm:text-sm text-slate-200 leading-relaxed font-normal">
                            {{ $q->rationale ?? 'No se registró explicación adicional.' }}
                        </p>
                        @if(!empty($q->legal_basis))
                            <div class="pt-2 border-t border-blue-500/20 text-xs text-blue-300 flex items-center gap-1.5 font-semibold">
                                <i class="fa-solid fa-gavel"></i>
                                Base Legal: <span>{{ $q->legal_basis }}</span>
                            </div>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

    </div>

</div>
@endsection
