@extends('layouts.app')

@section('title', 'Simulacro Oficial DIAN 2676 — Prueba de Méritos')

@section('content')
<div class="space-y-6 pb-24 sm:pb-8">

    {{-- Barra Superior Fija Institucional de Control del Examen (Responsive Mobile & Desktop) --}}
    <div class="bg-slate-900/95 border border-slate-800/90 rounded-2xl p-3 sm:p-5 shadow-2xl backdrop-blur-md sticky top-16 z-30">
        
        {{-- Versión Móvil Compacta (visible solo en celulares) --}}
        <div class="sm:hidden flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-black text-white font-mono">
                    P <span id="mobile-current-q" class="text-blue-400">1</span>/{{ $questions->count() }}
                </span>
            </div>

            {{-- Reloj en Móvil --}}
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-200">
                <i class="fa-regular fa-clock text-amber-400 text-xs animate-pulse"></i>
                <span id="mobile-timer-display" class="font-mono text-xs font-black text-amber-300">
                    05:00:00
                </span>
            </div>

            <div class="flex items-center gap-1.5">
                <button type="button" onclick="openOpticalSheetModal()" 
                        class="px-2.5 py-1 rounded-lg bg-blue-600/30 text-blue-300 border border-blue-500/40 text-[11px] font-bold flex items-center gap-1">
                    <i class="fa-solid fa-table-cells text-[10px]"></i>
                    <span id="mobile-header-answered">{{ count($savedAnswers) }}</span>/{{ $questions->count() }}
                </button>
                <button type="button" onclick="confirmSubmitModal()"
                        class="px-2.5 py-1 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg text-[11px] font-bold shadow-md shadow-emerald-600/20">
                    Enviar
                </button>
            </div>
        </div>

        {{-- Versión Desktop y Tablet (pantallas medianas y grandes) --}}
        <div class="hidden sm:flex flex-wrap items-center justify-between gap-4">
            
            {{-- Identificación Concurso DIAN / CNSC --}}
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-blue-600/30">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-500/30">
                            CNSC &bull; DIAN 2676
                        </span>
                        <span class="text-xs font-bold text-slate-300">
                            {{ $session->jobProfile->name ?? 'Analista III' }}
                        </span>
                    </div>
                    <h1 class="text-sm sm:text-base font-extrabold text-white mt-0.5">
                        Simulacro Oficial Concurso DIAN 2676
                    </h1>
                    <p class="text-[11px] text-slate-400">
                        Prueba Escrita de Competencias Básicas, Funcionales y Conductuales
                    </p>
                </div>
            </div>

            {{-- Reloj Temporizador, Guardado y Acción Finalizar --}}
            <div class="flex items-center gap-3 sm:gap-5">
                
                {{-- Estado de Autoguardado en Tiempo Real --}}
                <div id="save-status-indicator" class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-emerald-400 font-semibold shadow-inner">
                    <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                    <span id="save-status-text">Guardado en nube</span>
                </div>

                {{-- Progreso Numérico --}}
                <div class="hidden lg:block text-right">
                    <span class="text-[10px] uppercase tracking-wider text-slate-400 block font-semibold">Progreso</span>
                    <span class="text-xs font-bold text-white font-mono">
                        <span id="answered-count-text" class="text-blue-400 font-black">{{ count($savedAnswers) }}</span> / {{ $questions->count() }}
                    </span>
                </div>

                {{-- Cronómetro Oficial Continuo (5 Horas) --}}
                <div class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 shadow-inner">
                    <i class="fa-regular fa-clock text-amber-400 text-base animate-pulse"></i>
                    <div>
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 block -mb-1 font-semibold">Tiempo Restante</span>
                        <span id="timer-display" class="font-mono text-sm sm:text-base font-black text-amber-300">
                            05:00:00
                        </span>
                    </div>
                </div>

                {{-- Botón Finalizar Examen --}}
                <button type="button" onclick="confirmSubmitModal()"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-lg shadow-emerald-600/25 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-flag-checkered"></i>
                    <span>Finalizar Examen</span>
                </button>
            </div>

        </div>

        {{-- Barra de Progreso Visual --}}
        @php
            $initPercent = $questions->count() > 0 ? (count($savedAnswers) / $questions->count()) * 100 : 0;
        @endphp
        <div class="w-full bg-slate-800/80 h-1.5 rounded-full mt-2.5 sm:mt-3 overflow-hidden">
            <div id="progress-bar-fill" class="bg-gradient-to-r from-blue-500 via-indigo-500 to-emerald-400 h-full transition-all duration-300" style="width: {{ $initPercent }}%"></div>
        </div>
    </div>

    {{-- Formulario Principal del Examen --}}
    <form id="exam-form" method="POST" action="{{ route('simulacro.finalizar', $session->id) }}">
        @csrf
        <input type="hidden" name="time_spent_seconds" id="time_spent_seconds" value="{{ $elapsedSeconds ?? 0 }}">

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            
            {{-- Columna Izquierda: Tarjetas de Preguntas (3 cols) --}}
            <div class="lg:col-span-3 space-y-6">
                
                @foreach($questions as $index => $q)
                    @php
                        $savedOpt = $savedAnswers[$q->id] ?? null;
                    @endphp
                    <div id="question-card-{{ $index + 1 }}" 
                         class="question-card {{ $index === 0 ? '' : 'hidden' }} bg-slate-900 border border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-2xl transition-all">
                        
                        {{-- Encabezado de la Pregunta --}}
                        <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3.5 mb-4 sm:mb-6 border-b border-slate-800">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 sm:px-3 py-1 rounded-xl bg-blue-600 text-white text-xs font-black shadow-md shadow-blue-600/30">
                                    Pregunta {{ $index + 1 }} de {{ $questions->count() }}
                                </span>
                                @if($q->thematicAxis)
                                    <span class="px-2.5 py-1 rounded-xl bg-slate-800 text-blue-300 border border-slate-700/60 text-[10px] sm:text-[11px] font-semibold truncate max-w-[200px] sm:max-w-none">
                                        {{ $q->thematicAxis->name }}
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 rounded-lg text-[10px] uppercase font-bold {{ $q->competency_type === 'FUNCIONAL' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : 'bg-purple-500/20 text-purple-300 border border-purple-500/30' }}">
                                    {{ $q->competency_type ?? 'FUNCIONAL' }}
                                </span>
                            </div>

                            {{-- Botón Marcar para Revisar --}}
                            <button type="button" onclick="toggleReviewMark({{ $index + 1 }})" 
                                    id="btn-mark-{{ $index + 1 }}"
                                    class="text-xs px-2.5 sm:px-3 py-1.5 rounded-xl bg-slate-800/80 hover:bg-amber-500/20 text-slate-400 hover:text-amber-300 border border-slate-700/60 transition-colors flex items-center gap-1.5">
                                <i class="fa-regular fa-bookmark"></i>
                                <span class="hidden sm:inline">Marcar para revisión</span>
                                <span class="sm:hidden">Marcar</span>
                            </button>
                        </div>

                        {{-- Caso / Situación de Análisis (Diseño Formal) --}}
                        @if(!empty($q->case_text))
                            <div class="mb-4 sm:mb-6 p-3.5 sm:p-5 rounded-xl sm:rounded-2xl bg-gradient-to-r from-slate-950 to-indigo-950/20 border-l-4 border-blue-500 border-y border-r border-slate-800 text-slate-200 shadow-inner">
                                <div class="flex items-center gap-2 text-xs font-extrabold text-blue-400 uppercase tracking-wider mb-2">
                                    <i class="fa-solid fa-file-lines text-sm"></i>
                                    Caso de Estudio Situacional &bull; Concurso DIAN
                                </div>
                                <div class="text-xs sm:text-sm leading-relaxed text-slate-300 whitespace-pre-line font-normal">
                                    {{ $q->case_text }}
                                </div>
                            </div>
                        @endif

                        {{-- Enunciado de la Pregunta --}}
                        <div class="mb-4 sm:mb-6">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                                Enunciado
                            </span>
                            <h3 class="text-xs sm:text-base font-bold text-white leading-relaxed">
                                {{ $q->stem }}
                            </h3>
                        </div>

                        {{-- Opciones de Respuesta --}}
                        <div class="space-y-2.5 sm:space-y-3" id="options-group-{{ $index + 1 }}">
                            @php
                                $options = $q->options_map;
                            @endphp

                            @foreach($options as $letter => $text)
                                @if(!empty($text))
                                    @php
                                        $isSelected = ($savedOpt === $letter);
                                    @endphp
                                    <label for="q{{ $q->id }}_{{ $letter }}" 
                                           data-letter="{{ $letter }}"
                                           class="option-label flex items-start gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl border cursor-pointer transition-all active:scale-[0.99] {{ $isSelected ? 'border-blue-500 bg-blue-950/40 ring-1 ring-blue-500/50 shadow-md shadow-blue-500/10' : 'border-slate-800 bg-slate-950/60 hover:bg-slate-800/60 hover:border-blue-500/50' }}">
                                        
                                        <div class="flex items-center h-5 mt-0.5">
                                            <input type="radio" 
                                                   id="q{{ $q->id }}_{{ $letter }}" 
                                                   name="answers[{{ $q->id }}]" 
                                                   value="{{ $letter }}" 
                                                   {{ $isSelected ? 'checked' : '' }}
                                                   onchange="onSelectAnswer({{ $index + 1 }}, {{ $q->id }}, '{{ $letter }}')"
                                                   class="w-4 h-4 text-blue-600 bg-slate-900 border-slate-700 focus:ring-blue-500 focus:ring-2">
                                        </div>

                                        <div class="flex-1 flex items-start gap-2.5 sm:gap-3">
                                            <span class="option-badge w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-xs font-black flex items-center justify-center shrink-0 transition-colors {{ $isSelected ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/40' : 'bg-slate-800 text-slate-300' }}">
                                                {{ $letter }}
                                            </span>
                                            <span class="text-xs sm:text-sm text-slate-200 font-medium leading-relaxed">
                                                {{ $text }}
                                            </span>
                                        </div>
                                    </label>
                                @endif
                            @endforeach
                        </div>

                        {{-- Barra de Navegación Inferior (Desktop & Tablet) --}}
                        <div class="flex items-center justify-between pt-5 sm:pt-6 mt-5 sm:mt-6 border-t border-slate-800">
                            <button type="button" 
                                    onclick="goToQuestion({{ $index }})" 
                                    class="{{ $index === 0 ? 'invisible' : '' }} px-3.5 sm:px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs sm:text-sm font-semibold transition-colors flex items-center gap-1.5 sm:gap-2">
                                <i class="fa-solid fa-arrow-left"></i> Anterior
                            </button>

                            <div class="text-xs text-slate-400 font-medium hidden sm:block">
                                Usa las flechas <kbd class="px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] font-mono text-slate-300">&larr;</kbd> <kbd class="px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] font-mono text-slate-300">&rarr;</kbd> del teclado
                            </div>

                            @if($index + 1 < $questions->count())
                                <button type="button" 
                                        onclick="goToQuestion({{ $index + 2 }})" 
                                        class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-semibold shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2">
                                    Siguiente <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            @else
                                <button type="button" 
                                        onclick="confirmSubmitModal()" 
                                        class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2">
                                    Finalizar y Calificar <i class="fa-solid fa-check-double"></i>
                                </button>
                            @endif
                        </div>

                    </div>
                @endforeach

            </div>

            {{-- Columna Derecha: Hoja Óptica / Grilla de Navegación (Solo Desktop/Tablet grande) --}}
            <div class="hidden lg:block lg:col-span-1 space-y-4 lg:sticky lg:top-48">
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-2xl">
                    
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-table-cells text-blue-400"></i>
                            Hoja Óptica CNSC
                        </h4>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $questions->count() }} reactivos</span>
                    </div>

                    {{-- Filtros Rápidos de la Hoja Óptica --}}
                    <div class="flex items-center gap-1 mb-3 text-[10px] font-bold">
                        <button type="button" onclick="filterBubbles('all')" id="filter-btn-all" class="px-2 py-1 rounded-lg bg-blue-600 text-white transition-colors">
                            Todas
                        </button>
                        <button type="button" onclick="filterBubbles('pending')" id="filter-btn-pending" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                            Pendientes
                        </button>
                        <button type="button" onclick="filterBubbles('answered')" id="filter-btn-answered" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                            Respondidas
                        </button>
                        <button type="button" onclick="filterBubbles('review')" id="filter-btn-review" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                            Revisión
                        </button>
                    </div>

                    {{-- Grilla de Bubbles --}}
                    <div class="grid grid-cols-5 gap-2 max-h-[380px] overflow-y-auto pr-1" id="bubbles-container">
                        @foreach($questions as $index => $q)
                            @php
                                $isAnswered = isset($savedAnswers[$q->id]) && !empty($savedAnswers[$q->id]);
                            @endphp
                            <button type="button" 
                                    id="bubble-{{ $index + 1 }}" 
                                    data-q="{{ $index + 1 }}"
                                    onclick="goToQuestion({{ $index + 1 }})" 
                                    class="bubble-btn h-8 rounded-xl font-bold text-xs flex items-center justify-center transition-all {{ $isAnswered ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 text-slate-300' }} {{ $index === 0 ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900 scale-105' : '' }}">
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Leyenda de Estados --}}
                    <div class="grid grid-cols-3 gap-1 text-[9px] text-slate-400 pt-3 mt-3 border-t border-slate-800 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-slate-800 border border-slate-600"></span>
                            <span>Pendiente</span>
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <span>Respondida</span>
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Revisión</span>
                        </div>
                    </div>

                    {{-- Botón Enviar --}}
                    <div class="mt-4 pt-4 border-t border-slate-800">
                        <button type="button" onclick="confirmSubmitModal()"
                                class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i>
                            Calificar y Concluir
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </form>

    {{-- Barra Flotante Inferior Ergonómica para Celulares --}}
    <div class="fixed bottom-0 inset-x-0 bg-slate-900/95 backdrop-blur-xl border-t border-slate-800/90 px-3 py-2.5 z-40 sm:hidden shadow-2xl flex items-center justify-between gap-2 pb-safe">
        <button type="button" id="mobile-nav-prev" onclick="goToQuestion(currentQuestion - 1)" 
                class="flex-1 py-2.5 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs flex items-center justify-center gap-1 active:scale-95 transition-all">
            <i class="fa-solid fa-chevron-left text-[10px]"></i> Anterior
        </button>

        <button type="button" onclick="openOpticalSheetModal()" 
                class="py-2.5 px-3 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 border border-blue-500/30 font-bold text-xs flex items-center justify-center gap-1 active:scale-95 transition-all">
            <i class="fa-solid fa-table-cells text-xs"></i> <span id="mobile-bar-answered-count">{{ count($savedAnswers) }}/{{ $questions->count() }}</span>
        </button>

        <button type="button" id="mobile-nav-mark" onclick="toggleReviewMark(currentQuestion)" 
                class="py-2.5 px-3 rounded-xl bg-slate-800 text-slate-400 hover:text-amber-300 border border-slate-700/60 font-bold text-xs flex items-center justify-center active:scale-95 transition-all" title="Marcar para revisión">
            <i class="fa-regular fa-bookmark text-xs"></i>
        </button>

        <button type="button" id="mobile-nav-next" onclick="goToNextOrSubmit()" 
                class="flex-1 py-2.5 px-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-blue-600/30 active:scale-95 transition-all">
            <span id="mobile-next-text">Siguiente</span> <i id="mobile-next-icon" class="fa-solid fa-chevron-right text-[10px]"></i>
        </button>
    </div>

    {{-- Modal / Drawer Móvil de Hoja Óptica (Bottom Sheet) --}}
    <div id="optical-sheet-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/85 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl relative space-y-4 max-h-[85vh] flex flex-col">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-table-cells text-blue-400"></i>
                        Hoja de Respuestas
                    </h3>
                    <p class="text-[11px] text-slate-400">
                        <span id="mobile-modal-answered-count">{{ count($savedAnswers) }}</span> de {{ $questions->count() }} respondidas
                    </p>
                </div>
                <button type="button" onclick="closeOpticalSheetModal()"
                        class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Filtros Rápidos en Móvil --}}
            <div class="flex items-center gap-1.5 shrink-0 text-[10px] font-bold">
                <button type="button" onclick="filterBubbles('all')" id="m-filter-btn-all" class="px-2.5 py-1.5 rounded-lg bg-blue-600 text-white transition-colors">
                    Todas
                </button>
                <button type="button" onclick="filterBubbles('pending')" id="m-filter-btn-pending" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                    Pendientes
                </button>
                <button type="button" onclick="filterBubbles('answered')" id="m-filter-btn-answered" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                    Respondidas
                </button>
                <button type="button" onclick="filterBubbles('review')" id="m-filter-btn-review" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                    Marcadas
                </button>
            </div>

            {{-- Grilla de Bubbles en Móvil --}}
            <div class="grid grid-cols-5 gap-2 overflow-y-auto pr-1 flex-1 py-1" id="mobile-bubbles-container">
                @foreach($questions as $index => $q)
                    @php
                        $isAnswered = isset($savedAnswers[$q->id]) && !empty($savedAnswers[$q->id]);
                    @endphp
                    <button type="button" 
                            id="m-bubble-{{ $index + 1 }}" 
                            data-q="{{ $index + 1 }}"
                            onclick="goToQuestionAndClose({{ $index + 1 }})" 
                            class="m-bubble-btn h-10 rounded-xl font-bold text-xs flex items-center justify-center transition-all active:scale-95 {{ $isAnswered ? 'bg-blue-600 text-white font-black shadow-sm' : 'bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 text-slate-300' }} {{ $index === 0 ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900 scale-105' : '' }}">
                        {{ $index + 1 }}
                    </button>
                @endforeach
            </div>

            {{-- Leyenda y Botón Continuar --}}
            <div class="pt-3 border-t border-slate-800 shrink-0 space-y-3">
                <div class="grid grid-cols-3 gap-1 text-[10px] text-slate-400 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-800 border border-slate-600"></span>
                        <span>Pendiente</span>
                    </div>
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <span>Respondida</span>
                    </div>
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span>Revisión</span>
                    </div>
                </div>

                <button type="button" onclick="closeOpticalSheetModal()"
                        class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition-colors">
                    Continuar respondiendo
                </button>
            </div>

        </div>
    </div>

    {{-- Modal Profesional de Confirmación de Finalización --}}
    <div id="modal-submit-confirm" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl relative space-y-4">
            
            <div class="text-center">
                <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 mx-auto flex items-center justify-center text-2xl mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="text-lg font-extrabold text-white">¿Deseas finalizar y calificar el simulacro?</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Verifica el resumen de tu hoja de respuestas antes de enviar:
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 grid grid-cols-2 gap-3 text-center">
                <div>
                    <span class="text-[10px] text-slate-400 uppercase font-semibold block">Respondidas</span>
                    <strong id="modal-answered-count" class="text-emerald-400 text-lg font-black font-mono">0</strong>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 uppercase font-semibold block">Sin Responder</span>
                    <strong id="modal-unanswered-count" class="text-amber-400 text-lg font-black font-mono">0</strong>
                </div>
            </div>

            <div id="modal-warning-omitted" class="hidden p-3 rounded-xl bg-amber-950/30 border border-amber-500/30 text-amber-300 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-amber-400"></i>
                <span>Tienes preguntas sin responder. Aunque no descuentan puntos, no sumarán a tu calificación eliminatoria.</span>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeSubmitModal()"
                        class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-colors">
                    Continuar Examen
                </button>
                <button type="button" onclick="doSubmitForm()"
                        class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Sí, Calificar
                </button>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const totalQuestions = {{ $questions->count() }};
    let currentQuestion = 1;
    let answeredSet = new Set([
        @foreach($questions as $idx => $q)
            @if(isset($savedAnswers[$q->id]) && !empty($savedAnswers[$q->id]))
                {{ $idx + 1 }},
            @endif
        @endforeach
    ]);
    let reviewSet = new Set();
    let currentBubbleFilter = 'all';

    // Temporizador continuo y persistente
    let remainingSeconds = {{ $remainingSeconds }};
    let secondsSpent = {{ $elapsedSeconds }};

    const timerDisplay = document.getElementById('timer-display');
    const mobileTimerDisplay = document.getElementById('mobile-timer-display');
    const timeSpentInput = document.getElementById('time_spent_seconds');
    const progressBar = document.getElementById('progress-bar-fill');
    const answeredCountText = document.getElementById('answered-count-text');
    const saveIndicator = document.getElementById('save-status-indicator');

    function updateTimerUI() {
        const hrs = String(Math.floor(remainingSeconds / 3600)).padStart(2, '0');
        const mins = String(Math.floor((remainingSeconds % 3600) / 60)).padStart(2, '0');
        const secs = String(remainingSeconds % 60).padStart(2, '0');
        const timeStr = `${hrs}:${mins}:${secs}`;
        
        if (timerDisplay) timerDisplay.textContent = timeStr;
        if (mobileTimerDisplay) mobileTimerDisplay.textContent = timeStr;

        // Alerta visual de últimos 30 min
        if (remainingSeconds <= 1800) {
            if (timerDisplay) timerDisplay.className = "font-mono text-sm sm:text-base font-black text-rose-400 animate-pulse";
            if (mobileTimerDisplay) mobileTimerDisplay.className = "font-mono text-xs font-black text-rose-400 animate-pulse";
        }
    }

    updateTimerUI();

    const timerInterval = setInterval(() => {
        secondsSpent++;
        remainingSeconds--;
        timeSpentInput.value = secondsSpent;

        if (remainingSeconds <= 0) {
            clearInterval(timerInterval);
            alert('¡El tiempo oficial de 5 horas ha concluido! Tu examen se enviará y calificará automáticamente.');
            document.getElementById('exam-form').submit();
            return;
        }

        updateTimerUI();
    }, 1000);

    function updateMobileUI() {
        const mobileCurrentQ = document.getElementById('mobile-current-q');
        if (mobileCurrentQ) mobileCurrentQ.textContent = currentQuestion;

        const mobilePrevBtn = document.getElementById('mobile-nav-prev');
        if (mobilePrevBtn) {
            mobilePrevBtn.style.visibility = (currentQuestion === 1) ? 'hidden' : 'visible';
        }

        const nextBtn = document.getElementById('mobile-nav-next');
        const nextText = document.getElementById('mobile-next-text');
        const nextIcon = document.getElementById('mobile-next-icon');

        if (currentQuestion >= totalQuestions) {
            if (nextText) nextText.textContent = 'Calificar';
            if (nextIcon) nextIcon.className = 'fa-solid fa-check text-[10px]';
            if (nextBtn) {
                nextBtn.className = 'flex-1 py-2.5 px-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-emerald-600/30 active:scale-95 transition-all';
            }
        } else {
            if (nextText) nextText.textContent = 'Siguiente';
            if (nextIcon) nextIcon.className = 'fa-solid fa-chevron-right text-[10px]';
            if (nextBtn) {
                nextBtn.className = 'flex-1 py-2.5 px-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-blue-600/30 active:scale-95 transition-all';
            }
        }

        const mobileMarkBtn = document.getElementById('mobile-nav-mark');
        if (mobileMarkBtn) {
            if (reviewSet.has(currentQuestion)) {
                mobileMarkBtn.className = 'py-2.5 px-3 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold text-xs flex items-center justify-center active:scale-95 transition-all';
            } else {
                mobileMarkBtn.className = 'py-2.5 px-3 rounded-xl bg-slate-800 text-slate-400 hover:text-amber-300 border border-slate-700/60 font-bold text-xs flex items-center justify-center active:scale-95 transition-all';
            }
        }
    }

    function goToQuestion(qNum) {
        if (qNum < 1 || qNum > totalQuestions) return;

        // Quitar anillo activo anterior (desktop y móvil)
        const prevBubble = document.getElementById(`bubble-${currentQuestion}`);
        if (prevBubble) {
            prevBubble.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-2', 'ring-offset-slate-900', 'scale-105');
        }
        const prevMBubble = document.getElementById(`m-bubble-${currentQuestion}`);
        if (prevMBubble) {
            prevMBubble.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-2', 'ring-offset-slate-900', 'scale-105');
        }

        const currentCard = document.getElementById(`question-card-${currentQuestion}`);
        if (currentCard) currentCard.classList.add('hidden');

        currentQuestion = qNum;
        const newCard = document.getElementById(`question-card-${currentQuestion}`);
        if (newCard) newCard.classList.remove('hidden');

        // Poner anillo activo al nuevo bubble (desktop y móvil)
        const activeBubble = document.getElementById(`bubble-${currentQuestion}`);
        if (activeBubble) {
            activeBubble.classList.add('ring-2', 'ring-amber-400', 'ring-offset-2', 'ring-offset-slate-900', 'scale-105');
            activeBubble.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
        const activeMBubble = document.getElementById(`m-bubble-${currentQuestion}`);
        if (activeMBubble) {
            activeMBubble.classList.add('ring-2', 'ring-amber-400', 'ring-offset-2', 'ring-offset-slate-900', 'scale-105');
            activeMBubble.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        updateMobileUI();
        window.scrollTo({ top: 90, behavior: 'smooth' });
    }

    function goToNextOrSubmit() {
        if (currentQuestion < totalQuestions) {
            goToQuestion(currentQuestion + 1);
        } else {
            confirmSubmitModal();
        }
    }

    function openOpticalSheetModal() {
        document.getElementById('optical-sheet-modal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeOpticalSheetModal() {
        document.getElementById('optical-sheet-modal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function goToQuestionAndClose(qNum) {
        goToQuestion(qNum);
        closeOpticalSheetModal();
    }

    function onSelectAnswer(qNum, qId, optionLetter) {
        answeredSet.add(qNum);
        updateProgress();

        // 1. Actualizar visualmente la tarjeta de la opción elegida y desmarcar las otras
        const card = document.getElementById(`question-card-${qNum}`);
        if (card) {
            const labels = card.querySelectorAll('.option-label');
            labels.forEach(lbl => {
                const badge = lbl.querySelector('.option-badge');
                if (lbl.getAttribute('data-letter') === optionLetter) {
                    lbl.className = 'option-label flex items-start gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl border cursor-pointer transition-all active:scale-[0.99] border-blue-500 bg-blue-950/40 ring-1 ring-blue-500/50 shadow-md shadow-blue-500/10';
                    if (badge) {
                        badge.className = 'option-badge w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-xs font-black flex items-center justify-center shrink-0 transition-colors bg-blue-600 text-white shadow-sm shadow-blue-500/40';
                    }
                } else {
                    lbl.className = 'option-label flex items-start gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl sm:rounded-2xl border cursor-pointer transition-all active:scale-[0.99] border-slate-800 bg-slate-950/60 hover:bg-slate-800/60 hover:border-blue-500/50';
                    if (badge) {
                        badge.className = 'option-badge w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-xs font-black flex items-center justify-center shrink-0 transition-colors bg-slate-800 text-slate-300';
                    }
                }
            });
        }

        // 2. Actualizar burbujas ópticas (Desktop y Móvil)
        const bubble = document.getElementById(`bubble-${qNum}`);
        const mBubble = document.getElementById(`m-bubble-${qNum}`);
        if (!reviewSet.has(qNum)) {
            if (bubble) {
                bubble.classList.remove('bg-slate-800/80');
                bubble.classList.add('bg-blue-600', 'text-white', 'font-black');
            }
            if (mBubble) {
                mBubble.classList.remove('bg-slate-800/80');
                mBubble.classList.add('bg-blue-600', 'text-white', 'font-black');
            }
        }

        // 3. Indicador de Guardando... en la barra superior
        if (saveIndicator) {
            saveIndicator.className = "hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-amber-400 font-semibold shadow-inner";
            saveIndicator.innerHTML = '<i class="fa-solid fa-arrows-rotate animate-spin"></i><span>Guardando...</span>';
        }

        // 4. Autoguardado asíncrono en PostgreSQL
        fetch("{{ route('simulacro.guardar-respuesta', $session->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                question_id: qId,
                selected_option: optionLetter
            })
        })
        .then(() => {
            if (saveIndicator) {
                saveIndicator.className = "hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-emerald-400 font-semibold shadow-inner";
                saveIndicator.innerHTML = '<i class="fa-solid fa-cloud-arrow-up text-sm"></i><span>Guardado en nube</span>';
            }
        })
        .catch(err => {
            console.warn('Error en autoguardado:', err);
            if (saveIndicator) {
                saveIndicator.className = "hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-rose-400 font-semibold shadow-inner";
                saveIndicator.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i><span>Error conexión</span>';
            }
        });
    }

    function toggleReviewMark(qNum) {
        const bubble = document.getElementById(`bubble-${qNum}`);
        const mBubble = document.getElementById(`m-bubble-${qNum}`);
        const btnMark = document.getElementById(`btn-mark-${qNum}`);

        if (reviewSet.has(qNum)) {
            reviewSet.delete(qNum);
            if (btnMark) btnMark.classList.remove('bg-amber-500/20', 'text-amber-300', 'border-amber-500/40');
            
            [bubble, mBubble].forEach(b => {
                if (b) {
                    b.classList.remove('bg-amber-500', 'text-slate-950');
                    if (answeredSet.has(qNum)) {
                        b.classList.add('bg-blue-600', 'text-white', 'font-black');
                    } else {
                        b.classList.add('bg-slate-800/80', 'text-slate-300');
                    }
                }
            });
        } else {
            reviewSet.add(qNum);
            if (btnMark) btnMark.classList.add('bg-amber-500/20', 'text-amber-300', 'border-amber-500/40');
            
            [bubble, mBubble].forEach(b => {
                if (b) {
                    b.classList.remove('bg-slate-800/80', 'bg-blue-600', 'text-white');
                    b.classList.add('bg-amber-500', 'text-slate-950', 'font-black');
                }
            });
        }
        updateMobileUI();
    }

    function filterBubbles(filterType) {
        currentBubbleFilter = filterType;

        const filterBtns = ['all', 'pending', 'answered', 'review'];
        filterBtns.forEach(btnKey => {
            const el = document.getElementById(`filter-btn-${btnKey}`);
            const mEl = document.getElementById(`m-filter-btn-${btnKey}`);
            [el, mEl].forEach(btn => {
                if (btn) {
                    if (btnKey === filterType) {
                        btn.className = 'px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg bg-blue-600 text-white transition-colors';
                    } else {
                        btn.className = 'px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors';
                    }
                }
            });
        });

        for (let i = 1; i <= totalQuestions; i++) {
            const bubble = document.getElementById(`bubble-${i}`);
            const mBubble = document.getElementById(`m-bubble-${i}`);
            const isAns = answeredSet.has(i);
            const isRev = reviewSet.has(i);

            let shouldShow = true;
            if (filterType === 'pending') shouldShow = !isAns;
            else if (filterType === 'answered') shouldShow = isAns;
            else if (filterType === 'review') shouldShow = isRev;

            if (bubble) bubble.style.display = shouldShow ? 'flex' : 'none';
            if (mBubble) mBubble.style.display = shouldShow ? 'flex' : 'none';
        }
    }

    function updateProgress() {
        const count = answeredSet.size;
        if (answeredCountText) answeredCountText.textContent = count;
        
        const mHeaderAns = document.getElementById('mobile-header-answered');
        if (mHeaderAns) mHeaderAns.textContent = count;

        const mBarAns = document.getElementById('mobile-bar-answered-count');
        if (mBarAns) mBarAns.textContent = `${count}/${totalQuestions}`;

        const mModalAns = document.getElementById('mobile-modal-answered-count');
        if (mModalAns) mModalAns.textContent = count;

        const percent = (count / totalQuestions) * 100;
        if (progressBar) progressBar.style.width = `${percent}%`;
    }

    function confirmSubmitModal() {
        const answered = answeredSet.size;
        const unanswered = totalQuestions - answered;

        document.getElementById('modal-answered-count').textContent = answered;
        document.getElementById('modal-unanswered-count').textContent = unanswered;

        const warningEl = document.getElementById('modal-warning-omitted');
        if (unanswered > 0) {
            warningEl.classList.remove('hidden');
        } else {
            warningEl.classList.add('hidden');
        }

        document.getElementById('modal-submit-confirm').classList.remove('hidden');
    }

    function closeSubmitModal() {
        document.getElementById('modal-submit-confirm').classList.add('hidden');
    }

    function doSubmitForm() {
        clearInterval(timerInterval);
        document.getElementById('exam-form').submit();
    }

    // Inicializar UI Móvil al cargar
    updateMobileUI();

    // Atajos de teclado para navegación rápida
    document.addEventListener('keydown', function(e) {
        if (!document.getElementById('modal-submit-confirm').classList.contains('hidden')) return;

        if (e.key === 'ArrowRight' || e.key === 'PageDown') {
            if (currentQuestion < totalQuestions) {
                goToQuestion(currentQuestion + 1);
            }
        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
            if (currentQuestion > 1) {
                goToQuestion(currentQuestion - 1);
            }
        }
    });
</script>
@endpush
