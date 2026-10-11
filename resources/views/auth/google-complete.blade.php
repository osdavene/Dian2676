@extends('layouts.guest')

@section('title', 'Completar Registro con Google')

@section('content')
<div class="space-y-6">
    <div class="border-b border-slate-700/60 pb-4 text-center">
        @if (!empty($googleUser['avatar']))
            <img src="{{ $googleUser['avatar'] }}" alt="{{ $googleUser['name'] }}"
                 class="w-16 h-16 rounded-full mx-auto mb-3 border-2 border-blue-500 shadow-md">
        @else
            <div class="w-16 h-16 rounded-full mx-auto mb-3 bg-blue-600/30 border-2 border-blue-500 flex items-center justify-center text-blue-400 text-2xl font-bold">
                {{ substr($googleUser['name'] ?? 'U', 0, 1) }}
            </div>
        @endif
        <h3 class="text-xl font-bold text-white">
            ¡Casi listo, {{ $googleUser['name'] }}!
        </h3>
        <p class="text-xs text-slate-400 mt-1">
            Cuenta vinculada: <span class="text-blue-400 font-semibold">{{ $googleUser['email'] }}</span>
        </p>
    </div>

    <div class="p-3.5 rounded-xl bg-blue-950/40 border border-blue-500/30 text-xs text-blue-200 flex items-start gap-3">
        <i class="fa-solid fa-circle-info text-blue-400 text-sm mt-0.5"></i>
        <div>
            <p class="font-bold text-blue-300">Paso final de inscripción</p>
            <p class="mt-0.5 text-slate-300">Selecciona el empleo de la DIAN (Convocatoria 2676) al que te estás postulando para personalizar tus preguntas y simulacros.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('auth.google.complete.post') }}" class="space-y-5">
        @csrf

        {{-- Selector de Cargo Meta --}}
        <div>
            <label for="job_profile_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Cargo de Postulación DIAN (Convocatoria 2676)
            </label>
            <div class="mt-2 relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-id-badge"></i>
                </div>
                <select id="job_profile_id" name="job_profile_id" required
                        class="block w-full pl-10 pr-8 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none cursor-pointer">
                    @foreach($jobProfiles as $job)
                        <option value="{{ $job->id }}" {{ old('job_profile_id') == $job->id || $job->code == 'AT-FL-2011' ? 'selected' : '' }}>
                            {{ $job->name }} — {{ $job->level }} (Grado {{ $job->grade }}) [{{ $job->subprocess ?? $job->process }}]
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </div>
            </div>
            <p class="text-[11px] text-amber-300/80 mt-1">
                <i class="fa-solid fa-lock mr-1"></i> Una vez registrado, tu cargo quedará fijado para los simulacros y solo un Administrador podrá modificarlo.
            </p>
            @error('job_profile_id')
                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl shadow-lg shadow-blue-600/30 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            <i class="fa-solid fa-check"></i>
            Finalizar Registro y Entrar
        </button>
    </form>
</div>
@endsection
