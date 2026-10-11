@extends('layouts.app')

@section('title', 'Gestión y Corrección de Preguntas — Control Central')

@section('content')
<div class="space-y-6">

    {{-- Banner Superior --}}
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/60 to-slate-900 border border-indigo-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-pen-to-square text-amber-400"></i>
                    Super Administrador &bull; Banco de Preguntas
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                    Gestión, Auditoría y Corrección de Reactivos
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Edita enunciados, casos de estudio y corrige opciones marcadas erróneamente con recalificación automática de exámenes para los aspirantes.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                <a href="{{ route('admin.dashboard') }}" 
                   class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs uppercase tracking-wider border border-slate-700 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i>
                    Panel Central
                </a>

                @if($viewDuplicates)
                    <a href="{{ route('admin.questions.index') }}" 
                       class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-list-check"></i>
                        Ver Todas las Preguntas
                    </a>

                    <form method="POST" action="{{ route('admin.questions.deactivate-duplicates') }}" onsubmit="return confirm('¿Estás seguro de desactivar las copias redundantes? Se conservará solo 1 copia activa de cada pregunta.');">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-rose-600/30 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-trash-can"></i>
                            Desactivar Copias Duplicadas
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.questions.index', ['duplicates' => 1]) }}" 
                       class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-clone"></i>
                        Auditoría de Duplicadas ({{ $totalDuplicates }})
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Filtros y Buscador --}}
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 shadow-xl">
        <form method="GET" action="{{ route('admin.questions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            @if($viewDuplicates)
                <input type="hidden" name="duplicates" value="1">
            @endif

            {{-- Buscador Texto / ID --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Buscar por ID, texto o fundamentación:</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Ej: ID (149) o 'protección vida'..."
                           class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-blue-500 transition-colors">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-500 text-xs"></i>
                </div>
            </div>

            {{-- Filtro por Cargo --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Cargo:</label>
                <select name="job_id" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                    <option value="">Todos los Cargos</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" {{ $jobId == $j->id ? 'selected' : '' }}>{{ $j->name }}</option>
                    @endforeach
                    <option value="globales" {{ $jobId === 'globales' ? 'selected' : '' }}>Transversales / Sin Cargo</option>
                </select>
            </div>

            {{-- Filtro por Eje Temático --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Eje Temático:</label>
                <select name="axis_id" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                    <option value="">Todos los Ejes</option>
                    @foreach($axes as $ax)
                        <option value="{{ $ax->id }}" {{ $axisId == $ax->id ? 'selected' : '' }}>{{ Str::limit($ax->name, 28) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Botones de Filtrar --}}
            <div class="flex items-center gap-2">
                <button type="submit" 
                        class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5 shadow-md">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <a href="{{ route('admin.questions.index') }}" 
                   class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors" title="Restablecer filtros">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>

            {{-- Casilla: Solo con Casos Situacionales --}}
            <div class="lg:col-span-5 flex items-center gap-4 pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300 select-none">
                    <input type="checkbox" name="only_cases" value="1" {{ $onlyCases ? 'checked' : '' }} onchange="this.form.submit()"
                           class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-blue-600 focus:ring-0 focus:ring-offset-0">
                    <span>Ver solo preguntas asociadas a casos de estudio situacionales</span>
                </label>
            </div>
        </form>
    </div>

    {{-- Banner de Modo Duplicadas si está activo --}}
    @if($viewDuplicates)
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-xl"></i>
                <div>
                    <h4 class="text-sm font-bold text-amber-300">Modo Auditoría de Preguntas Duplicadas Activo</h4>
                    <p class="text-xs text-slate-300">
                        Se están listando las preguntas que comparten el mismo enunciado. Puedes desactivar las copias redundantes con 1 clic para que no se repitan en los simulacros.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabla / Listado de Preguntas --}}
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl space-y-4">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div>
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-database text-blue-400"></i>
                    Banco de Preguntas
                </h3>
                <p class="text-xs text-slate-400">
                    Total encontradas: <strong class="text-white">{{ $questions->total() }}</strong> reactivos
                </p>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($questions as $q)
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-950/60 border border-slate-800 hover:border-slate-700 transition-all space-y-3.5">
                    
                    {{-- Encabezado de la Fila --}}
                    <div class="flex flex-wrap items-center justify-between gap-2.5 pb-2.5 border-b border-slate-800/80">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-lg bg-blue-600/20 text-blue-300 border border-blue-500/30 font-black text-xs font-mono">
                                #{{ $q->id }}
                            </span>

                            @if($q->is_active)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    Activa
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                    Inactiva (Desactivada)
                                </span>
                            @endif

                            <span class="px-2 py-0.5 rounded-md text-[10px] text-slate-300 bg-slate-800 border border-slate-700/60 font-medium">
                                {{ $q->jobProfile->name ?? 'Transversal (Todos)' }}
                            </span>

                            @if($q->thematicAxis)
                                <span class="px-2 py-0.5 rounded-md text-[10px] text-purple-300 bg-purple-500/10 border border-purple-500/20 font-medium">
                                    {{ Str::limit($q->thematicAxis->name, 35) }}
                                </span>
                            @endif
                        </div>

                        {{-- Botón Editar --}}
                        <button type="button" onclick="openEditQuestionModal({{ $q->id }})"
                                class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-md shadow-amber-500/20 transition-all active:scale-95">
                            <i class="fa-solid fa-pen-to-square"></i> Corregir / Editar
                        </button>
                    </div>

                    {{-- Caso Situacional (si aplica) --}}
                    @if(!empty($q->case_text))
                        <div class="p-3 rounded-xl bg-slate-900 border border-blue-500/20 text-slate-300 text-xs">
                            <span class="text-[10px] uppercase font-bold text-blue-400 block mb-0.5 flex items-center gap-1">
                                <i class="fa-solid fa-file-lines"></i> Caso Situacional Asociado:
                            </span>
                            <div class="line-clamp-2 text-justify">
                                {{ $q->case_text }}
                            </div>
                        </div>
                    @endif

                    {{-- Enunciado --}}
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Enunciado:</span>
                        <p class="text-sm font-semibold text-white leading-relaxed text-justify">
                            {{ $q->stem }}
                        </p>
                    </div>

                    {{-- Opciones A, B, C, D --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs pt-1">
                        @php
                            $optionsMap = $q->options_map;
                            $correctLetter = strtoupper(trim($q->correct_option));
                        @endphp
                        @foreach(['A', 'B', 'C', 'D'] as $let)
                            @if(isset($optionsMap[$let]) && !empty($optionsMap[$let]))
                                <div class="p-2.5 rounded-xl border flex items-start gap-2 {{ $let === $correctLetter ? 'bg-emerald-950/40 border-emerald-500/50 text-emerald-200 ring-1 ring-emerald-500/40' : 'bg-slate-900/60 border-slate-800 text-slate-300' }}">
                                    <span class="w-5 h-5 rounded-md font-bold text-xs flex items-center justify-center shrink-0 {{ $let === $correctLetter ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $let }}
                                    </span>
                                    <span class="flex-1 leading-snug">{{ $optionsMap[$let] }}</span>
                                    @if($let === $correctLetter)
                                        <span class="text-[9px] font-black uppercase text-emerald-400 shrink-0 px-1 py-0.5 rounded bg-emerald-500/20 border border-emerald-500/30">
                                            ✓ Correcta
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Sustentación y Base Legal --}}
                    @if(!empty($q->explanation_summary) || !empty($q->legal_basis))
                        <div class="text-[11px] text-slate-400 pt-2 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <strong class="text-slate-300">Justificación:</strong>
                                <span>{{ Str::limit($q->explanation_summary ?? 'No registrada', 140) }}</span>
                            </div>
                            @if(!empty($q->legal_basis))
                                <span class="text-blue-400 font-medium">
                                    <i class="fa-solid fa-gavel text-[10px]"></i> Base Legal: {{ $q->legal_basis }}
                                </span>
                            @endif
                        </div>
                    @endif

                </div>
            @empty
                <div class="py-12 text-center text-slate-400">
                    <i class="fa-solid fa-database text-3xl mb-2 text-slate-600 block"></i>
                    No se encontraron preguntas con los filtros seleccionados.
                </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        <div class="pt-4 border-t border-slate-800">
            {{ $questions->links() }}
        </div>

    </div>

</div>

{{-- Modal Profesional de Edición de Pregunta (AJAX) --}}
<div id="modal-edit-question" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-3xl w-full p-5 sm:p-7 shadow-2xl relative space-y-4 max-h-[90vh] flex flex-col">
        
        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black text-white">
                        Editar y Corregir Pregunta #<span id="modal-q-id-title">--</span>
                    </h3>
                    <p class="text-xs text-slate-400">
                        <span id="modal-q-job">--</span> &bull; <span id="modal-q-axis">--</span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeEditQuestionModal()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Formulario con Scroll Interno --}}
        <form id="edit-question-form" method="POST" action="" class="space-y-4 overflow-y-auto pr-1 flex-1">
            @csrf
            @method('PUT')

            {{-- Selector Prominente de Opción Correcta --}}
            <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-500/40 space-y-2">
                <label class="block text-xs font-black uppercase tracking-wider text-emerald-300">
                    <i class="fa-solid fa-check-double mr-1"></i> Opción Correcta Oficial del Reactivo:
                </label>
                <div class="grid grid-cols-4 gap-2 sm:gap-3">
                    @foreach(['A', 'B', 'C', 'D'] as $let)
                        <label class="cursor-pointer">
                            <input type="radio" name="correct_option" value="{{ $let }}" id="radio-opt-{{ $let }}" class="sr-only peer">
                            <div class="py-2.5 px-3 rounded-xl border border-slate-700 text-center font-bold text-sm text-slate-300 peer-checked:bg-emerald-600 peer-checked:border-emerald-500 peer-checked:text-white peer-checked:shadow-lg peer-checked:shadow-emerald-600/30 transition-all hover:bg-slate-800">
                                Opción {{ $let }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Enunciado --}}
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Enunciado de la Pregunta:</label>
                <textarea name="stem" id="input-stem" rows="3" required
                          class="w-full p-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs leading-relaxed focus:outline-none focus:border-blue-500"></textarea>
            </div>

            {{-- Caso Situacional --}}
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Caso Situacional de Análisis (Opcional):</label>
                <textarea name="case_text" id="input-case-text" rows="3" placeholder="Si la pregunta se basa en un caso, ingrésalo aquí..."
                          class="w-full p-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs leading-relaxed focus:outline-none focus:border-blue-500"></textarea>
            </div>

            {{-- Textos de las Opciones --}}
            <div class="space-y-2.5 pt-2 border-t border-slate-800">
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Textos de las Opciones:</span>

                <div>
                    <label class="block text-[11px] font-bold text-blue-300 mb-0.5">Opción A:</label>
                    <input type="text" name="option_a" id="input-opt-a" required
                           class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-blue-300 mb-0.5">Opción B:</label>
                    <input type="text" name="option_b" id="input-opt-b" required
                           class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-blue-300 mb-0.5">Opción C:</label>
                    <input type="text" name="option_c" id="input-opt-c" required
                           class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-blue-300 mb-0.5">Opción D (Opcional):</label>
                    <input type="text" name="option_d" id="input-opt-d"
                           class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                </div>
            </div>

            {{-- Justificación Jurídica y Base Legal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Base Legal:</label>
                    <input type="text" name="legal_basis" id="input-legal-basis" placeholder="Ej: Constitución Política de Colombia Art. 2"
                           class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Dificultad:</label>
                    <select name="difficulty" id="input-difficulty" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500">
                        <option value="bajo">Bajo</option>
                        <option value="medio">Medio</option>
                        <option value="alto">Alto</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-300 mb-1">Sustentación / Explicación:</label>
                    <textarea name="explanation_summary" id="input-explanation" rows="2"
                              class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-blue-500"></textarea>
                </div>
            </div>

            {{-- Opciones de Recalificación y Estado --}}
            <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-amber-300 font-bold">
                    <input type="checkbox" name="recalculate_scores" value="1" checked
                           class="w-4 h-4 rounded border-amber-500 bg-slate-900 text-amber-500 focus:ring-0">
                    <span>Recalcular automáticamente los exámenes históricos de aspirantes que contestaron esta pregunta</span>
                </label>
                <p class="text-[11px] text-slate-400 pl-6">
                    Si cambiaste la opción correcta, el sistema buscará qué usuarios contestaron este reactivo, corregirá su puntaje y actualizará su estado de aprobación en tiempo real.
                </p>

                <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-300 font-semibold pt-1">
                    <input type="checkbox" name="is_active" value="1" id="input-is-active" checked
                           class="w-4 h-4 rounded border-slate-700 bg-slate-900 text-blue-600 focus:ring-0">
                    <span>Pregunta Activa (disponible en simulacros y prácticas)</span>
                </label>
            </div>

            {{-- Botones de Acción --}}
            <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-3 shrink-0">
                <button type="button" onclick="closeEditQuestionModal()"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Corrección
                </button>
            </div>
        </form>

    </div>
</div>

@endsection

@push('scripts')
<script>
    function openEditQuestionModal(qId) {
        const modal = document.getElementById('modal-edit-question');
        const form = document.getElementById('edit-question-form');

        form.action = `/admin/preguntas/${qId}`;
        document.getElementById('modal-q-id-title').textContent = qId;

        // Cargar datos vía AJAX
        fetch(`/admin/preguntas/${qId}/datos`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('modal-q-job').textContent = data.job_name || 'Transversal';
                document.getElementById('modal-q-axis').textContent = data.axis_name || 'General';
                document.getElementById('input-stem').value = data.stem || '';
                document.getElementById('input-case-text').value = data.case_text || '';
                document.getElementById('input-opt-a').value = data.option_a || '';
                document.getElementById('input-opt-b').value = data.option_b || '';
                document.getElementById('input-opt-c').value = data.option_c || '';
                document.getElementById('input-opt-d').value = data.option_d || '';
                document.getElementById('input-legal-basis').value = data.legal_basis || '';
                document.getElementById('input-explanation').value = data.explanation_summary || '';
                document.getElementById('input-difficulty').value = data.difficulty || 'medio';
                document.getElementById('input-is-active').checked = !!data.is_active;

                // Marcar radio de la opción correcta
                const correct = (data.correct_option || 'A').toUpperCase();
                const radio = document.getElementById(`radio-opt-${correct}`);
                if (radio) radio.checked = true;

                modal.classList.remove('hidden');
            })
            .catch(err => {
                alert('Error al cargar la información de la pregunta.');
                console.error(err);
            });
    }

    function closeEditQuestionModal() {
        document.getElementById('modal-edit-question').classList.add('hidden');
    }
</script>
@endpush
