@extends('layouts.app')

@section('title', 'Banco de Preguntas — DIAN 2676')

@section('content')
<div class="space-y-6">

    {{-- Banner de Encabezado --}}
    <div class="bg-gradient-to-r from-purple-950/60 via-blue-950/40 to-slate-900 border border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-xl">
        <div class="max-w-3xl space-y-2">
            <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-bold uppercase tracking-wider">
                Repositorio Oficial de Estudio
            </span>
            <h1 class="text-xl sm:text-3xl font-black text-white flex items-center gap-3">
                <i class="fa-solid fa-database text-purple-400"></i>
                Banco General de Preguntas y Casos
            </h1>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                Explora, busca y profundiza en el banco de reactivos clasificados por cargo, eje temático, dificultad y fundamento normativo del concurso de méritos DIAN 2676.
            </p>
        </div>
    </div>

    {{-- Barra de Filtros y Búsqueda --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl">
        <form method="GET" action="{{ route('banco.preguntas') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            
            {{-- Filtro de Cargo --}}
            <div>
                <label for="job_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Cargo
                </label>
                <select name="job_id" id="job_id" onchange="this.form.submit()"
                        class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="todos" {{ $jobId == 'todos' ? 'selected' : '' }}>
                        Todos los Cargos (Banco Global)
                    </option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" {{ $jobId == $j->id ? 'selected' : '' }}>
                            {{ $j->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtro de Eje Temático --}}
            <div>
                <label for="axis_id" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Eje Temático
                </label>
                <select name="axis_id" id="axis_id" onchange="this.form.submit()"
                        class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Todos los Ejes ({{ $axes->count() }})</option>
                    @foreach($axes as $ax)
                        <option value="{{ $ax->id }}" {{ $axisId == $ax->id ? 'selected' : '' }}>
                            {{ $ax->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filtro de Dificultad --}}
            <div>
                <label for="difficulty" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Dificultad
                </label>
                <select name="difficulty" id="difficulty" onchange="this.form.submit()"
                        class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Todas las dificultades</option>
                    <option value="Baja" {{ $difficulty === 'Baja' ? 'selected' : '' }}>Baja</option>
                    <option value="Media" {{ $difficulty === 'Media' ? 'selected' : '' }}>Media</option>
                    <option value="Alta" {{ $difficulty === 'Alta' ? 'selected' : '' }}>Alta</option>
                </select>
            </div>

            {{-- Búsqueda por Texto --}}
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    Palabra clave o ley
                </label>
                <div class="relative">
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Ej: Dian, factura, recurso..."
                           class="w-full bg-slate-950 border border-slate-700/80 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass text-slate-500 absolute left-3 top-2.5 text-xs"></i>
                </div>
            </div>

            {{-- Botones Filtrar y Limpiar --}}
            <div class="flex items-center gap-2">
                <button type="submit" 
                        class="flex-1 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/30 transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter"></i>
                    Filtrar
                </button>
                @if($search || $axisId || $difficulty)
                    <a href="{{ route('banco.preguntas', ['job_id' => $jobId]) }}" 
                       class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors" title="Limpiar filtros">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>

        </form>
    </div>

    {{-- Resumen de Resultados --}}
    <div class="flex items-center justify-between text-xs text-slate-400 px-1">
        <span>Mostrando <strong>{{ $questions->firstItem() ?? 0 }}</strong> - <strong>{{ $questions->lastItem() ?? 0 }}</strong> de <strong>{{ $questions->total() }}</strong> preguntas encontradas</span>
        <span>Página {{ $questions->currentPage() }} de {{ $questions->lastPage() }}</span>
    </div>

    {{-- Lista de Preguntas --}}
    <div class="space-y-5">
        @forelse($questions as $q)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 shadow-xl space-y-4" x-data="{ showAnswer: false }">
                
                {{-- Encabezado de Pregunta --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-blue-600/20 text-blue-300 border border-blue-500/30 text-xs font-black">
                            ID #{{ $q->id }}
                        </span>
                        @if($q->thematicAxis)
                            <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 border border-slate-700/60 text-xs font-semibold">
                                {{ $q->thematicAxis->name }}
                            </span>
                        @endif
                        <span class="px-2 py-0.5 rounded-md text-[10px] uppercase font-bold {{ $q->competency_type === 'FUNCIONAL' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-purple-500/20 text-purple-300' }}">
                            {{ $q->competency_type ?? 'FUNCIONAL' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-400">
                            Dificultad: <strong class="text-slate-300 capitalize">{{ $q->difficulty ?? 'Media' }}</strong>
                        </span>
                        <button type="button" @click="showAnswer = !showAnswer"
                                class="text-xs px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition-colors flex items-center gap-1.5">
                            <i :class="showAnswer ? 'fa-solid fa-eye-slash text-rose-400' : 'fa-solid fa-eye text-emerald-400'"></i>
                            <span x-text="showAnswer ? 'Ocultar Solución' : 'Ver Solución'"></span>
                        </button>
                    </div>
                </div>

                {{-- Caso Situacional --}}
                @if(!empty($q->case_text))
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-slate-300 text-xs sm:text-sm whitespace-pre-line leading-relaxed">
                        <div class="text-blue-400 font-bold text-xs uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-file-invoice"></i>
                            Situación de Análisis
                        </div>
                        {{ $q->case_text }}
                    </div>
                @endif

                {{-- Enunciado --}}
                <p class="text-sm font-semibold text-white leading-relaxed">
                    {{ $q->stem }}
                </p>

                {{-- Opciones --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    @php
                        $opts = $q->options_map;
                    @endphp
                    @foreach($opts as $let => $txt)
                        @if(!empty($txt))
                            <div class="p-3 rounded-xl border border-slate-800/80 bg-slate-950/40 flex items-start gap-2.5"
                                 :class="showAnswer && '{{ $let }}' === '{{ $q->correct_option }}' ? 'bg-emerald-950/40 border-emerald-500/60 text-emerald-100 font-semibold' : 'text-slate-300'">
                                <span class="w-5 h-5 rounded-md text-xs font-bold flex items-center justify-center shrink-0"
                                      :class="showAnswer && '{{ $let }}' === '{{ $q->correct_option }}' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400'">
                                    {{ $let }}
                                </span>
                                <span class="leading-relaxed">{{ $txt }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Solución y Justificación (Desplegable) --}}
                <div x-show="showAnswer" x-transition class="p-4 rounded-xl bg-blue-950/30 border border-blue-500/30 space-y-2 mt-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-emerald-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check"></i>
                            Respuesta Correcta: Opción {{ $q->correct_option }}
                        </span>
                        @if(!empty($q->legal_basis))
                            <span class="text-blue-300 font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-gavel"></i>
                                Base Legal: {{ $q->legal_basis }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-slate-200 leading-relaxed font-normal pt-1">
                        {{ $q->rationale ?? 'Sin sustentación registrada.' }}
                    </p>
                </div>

            </div>
        @empty
            <div class="py-12 text-center bg-slate-900 border border-slate-800 rounded-2xl">
                <i class="fa-solid fa-magnifying-glass text-4xl text-slate-600 mb-3 block"></i>
                <h3 class="text-base font-bold text-white">No se encontraron preguntas</h3>
                <p class="text-xs text-slate-400 mt-1">Prueba cambiando los criterios del filtro de búsqueda.</p>
            </div>
        @endforelse
    </div>

    {{-- Paginador --}}
    <div class="pt-4 flex justify-center">
        {{ $questions->links() }}
    </div>

</div>
@endsection
