@extends('layouts.app')

@section('title', 'Ficha de Aspirante — Control Central')

@section('content')
<div class="space-y-6">

    {{-- Encabezado con Enlace de Retorno --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users') }}" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5 transition-colors">
            <i class="fa-solid fa-arrow-left"></i> Volver a la lista de aspirantes
        </a>
        <span class="text-xs text-slate-500 font-mono">ID Aspirante: #{{ $aspirante->id }}</span>
    </div>

    {{-- Tarjeta Principal del Aspirante y Formulario de Actualización --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        {{-- Columna 1: Resumen y Estadísticas Personales --}}
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-600/20 border border-blue-500/40 text-blue-400 flex items-center justify-center font-bold text-xl">
                    {{ strtoupper(substr($aspirante->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-white">{{ $aspirante->name }}</h2>
                    <p class="text-xs text-slate-400 font-mono">{{ $aspirante->email }}</p>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $aspirante->role === 'superadmin' ? 'bg-amber-500/20 text-amber-300' : ($aspirante->role === 'admin' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-slate-800 text-slate-300') }}">
                        {{ $aspirante->role }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-800 text-xs">
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] text-slate-400 block">Exámenes Rendidos</span>
                    <strong class="text-white text-base font-bold">{{ $total }}</strong>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] text-slate-400 block">Aprobados (&ge; 70 pts)</span>
                    <strong class="text-emerald-400 text-base font-bold">{{ $passed }}</strong>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] text-slate-400 block">Promedio General</span>
                    <strong class="text-white text-base font-mono">{{ number_format($avgScore, 2) }}</strong>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[10px] text-slate-400 block">Mejor Puntaje</span>
                    <strong class="text-amber-400 text-base font-mono">{{ number_format($bestScore, 2) }}</strong>
                </div>
            </div>

            {{-- Formulario para Modificar Datos, Credenciales, Rol y Cargo --}}
            <form method="POST" action="{{ route('admin.users.update', $aspirante->id) }}" class="pt-4 border-t border-slate-800 space-y-3.5">
                @csrf
                @method('PUT')
                <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-user-gear text-blue-400"></i> Editar Datos y Credenciales
                </h4>

                <div>
                    <label for="name" class="block text-[11px] font-semibold text-slate-300 mb-1">
                        Nombre Completo
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $aspirante->name) }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="email" class="block text-[11px] font-semibold text-slate-300 mb-1">
                        Correo Electrónico (Login)
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email', $aspirante->email) }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="job_profile_id" class="block text-[11px] font-semibold text-slate-300 mb-1">
                        Cargo Objetivo
                    </label>
                    <select name="job_profile_id" id="job_profile_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($jobs as $j)
                            <option value="{{ $j->id }}" {{ ($aspirante->job_profile_id ?? 1) == $j->id ? 'selected' : '' }}>
                                {{ $j->name }} ({{ $j->level }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="role" class="block text-[11px] font-semibold text-slate-300 mb-1">
                        Rol en la Plataforma
                    </label>
                    <select name="role" id="role" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="aspirante" {{ $aspirante->role === 'aspirante' ? 'selected' : '' }}>Aspirante</option>
                        <option value="admin" {{ $aspirante->role === 'admin' ? 'selected' : '' }}>Administrador</option>
                        <option value="superadmin" {{ $aspirante->role === 'superadmin' ? 'selected' : '' }}>Super Administrador</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-[11px] font-semibold text-slate-300 mb-1">
                        Nueva Contraseña (Opcional)
                    </label>
                    <input type="text" name="password" id="password" placeholder="Dejar en blanco para mantener la actual"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/30 transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                </button>
            </form>

            {{-- Zona de Mantenimiento --}}
            <div class="pt-4 border-t border-slate-800 space-y-2.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Acciones de Mantenimiento</span>
                
                <form method="POST" action="{{ route('admin.users.reset_attempts', $aspirante->id) }}" onsubmit="return confirm('¿Reiniciar todo el historial de exámenes de {{ $aspirante->name }}? Esta acción borrará sus simulacros y le permitirá empezar de cero.')">
                    @csrf
                    <button type="submit" 
                            class="w-full py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i> Reiniciar Intentos de Simulacro
                    </button>
                </form>

                @if(auth()->id() !== $aspirante->id)
                    <form method="POST" action="{{ route('admin.users.delete', $aspirante->id) }}" onsubmit="return confirm('¿Eliminar permanentemente a {{ $aspirante->name }} de la plataforma?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-semibold text-xs transition-colors flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-trash-can text-[11px]"></i> Eliminar Usuario
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Columna 2 & 3: Historial Completo de Exámenes del Aspirante --}}
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-list-check text-emerald-400"></i>
                Historial de Intentos y Simulacros
            </h3>

            {{-- Vista de Tarjetas para Celulares (< sm) --}}
            <div class="grid grid-cols-1 gap-3 sm:hidden">
                @forelse($sessions as $s)
                    <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-blue-400 text-xs">
                                Sesión #{{ $s->id }}
                            </span>
                            @if($s->completed_at)
                                @if($s->is_passed)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold">
                                        APROBADO
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 text-[10px] font-bold">
                                        NO APROBADO
                                    </span>
                                @endif
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold animate-pulse">
                                    EN CURSO
                                </span>
                            @endif
                        </div>

                        <div class="text-xs">
                            <span class="font-semibold text-white">{{ $s->jobProfile->name ?? 'N/A' }}</span>
                            <span class="block text-[11px] text-slate-400 font-mono mt-0.5">{{ $s->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs py-2 border-y border-slate-800/60 text-slate-300">
                            <div>
                                <span class="text-[10px] text-slate-400 block">Puntaje</span>
                                <span class="font-mono font-black text-white text-sm">{{ number_format($s->score, 2) }}</span>
                                <span class="text-[10px] text-slate-400">({{ $s->correct_answers }}/{{ $s->total_questions }})</span>
                            </div>
                            <div>
                                @if($s->completed_at)
                                    <a href="{{ route('simulacro.resultado', $s->id) }}" 
                                       class="px-3 py-1.5 rounded-lg bg-blue-600/20 hover:bg-blue-600/40 text-blue-300 font-semibold transition-colors text-xs flex items-center gap-1">
                                        <i class="fa-solid fa-file-lines text-[10px]"></i> Ver Examen
                                    </a>
                                @else
                                    <span class="text-slate-500 text-xs font-mono">En curso</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-500 text-xs">
                        Este aspirante no ha presentado ningún simulacro aún.
                    </div>
                @endforelse
            </div>

            {{-- Vista de Tabla para Escritorio (>= sm) --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-3">Sesión</th>
                            <th class="py-3 px-3">Fecha</th>
                            <th class="py-3 px-3">Cargo</th>
                            <th class="py-3 px-3 text-center">Aciertos</th>
                            <th class="py-3 px-3 text-center">Puntaje</th>
                            <th class="py-3 px-3 text-center">Estado</th>
                            <th class="py-3 px-3 text-right">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($sessions as $s)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-blue-400">
                                    #{{ $s->id }}
                                </td>
                                <td class="py-3 px-3 text-slate-400 whitespace-nowrap">
                                    {{ $s->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-white">
                                    {{ $s->jobProfile->name ?? 'N/A' }}
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-emerald-400">
                                    {{ $s->correct_answers }} / {{ $s->total_questions }}
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-black text-white text-sm">
                                    {{ number_format($s->score, 2) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @if($s->completed_at)
                                        @if($s->is_passed)
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold">
                                                APROBADO
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 text-[10px] font-bold">
                                                NO APROBADO
                                            </span>
                                        @endif
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold animate-pulse">
                                            EN CURSO
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($s->completed_at)
                                        <a href="{{ route('simulacro.resultado', $s->id) }}" 
                                           class="px-2.5 py-1 rounded-lg bg-blue-600/20 hover:bg-blue-600/40 text-blue-300 font-semibold transition-colors text-[11px]">
                                            Ver Examen
                                        </a>
                                    @else
                                        <span class="text-slate-500 text-[11px]">En progreso</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">
                                    Este aspirante no ha presentado ningún simulacro aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
