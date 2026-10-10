@extends('layouts.guest')

@section('title', 'Registro de Aspirante')

@section('content')
<div class="space-y-6">
    <div class="border-b border-slate-700/60 pb-4">
        <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <i class="fa-solid fa-user-plus text-blue-400"></i>
            Registro de Aspirante
        </h3>
        <p class="text-xs text-slate-400 mt-1">
            Crea tu perfil y selecciona el empleo de la DIAN para el cual deseas entrenarte.
        </p>
    </div>

    <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Nombre Completo
            </label>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-user"></i>
                </div>
                <input id="name" name="name" type="text" required
                       value="{{ old('name') }}"
                       class="block w-full pl-10 pr-3 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                       placeholder="Ej. Carlos Alberto Gómez">
            </div>
            @error('name')
                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Correo Electrónico
            </label>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <input id="email" name="email" type="email" required
                       value="{{ old('email') }}"
                       class="block w-full pl-10 pr-3 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                       placeholder="tu.correo@ejemplo.com">
            </div>
            @error('email')
                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Selector de Cargo Meta --}}
        <div>
            <label for="job_profile_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Cargo de Postulación DIAN (Convocatoria 2676)
            </label>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
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
                <i class="fa-solid fa-lock mr-1"></i> Selecciona tu cargo de inscripción oficial. Una vez registrado, tu nivel quedará fijado y solo un Administrador podrá modificarlo.
            </p>
            @error('job_profile_id')
                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                    Contraseña
                </label>
                <div class="mt-1.5 relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input id="password" name="password" type="password" required
                           class="block w-full pl-10 pr-3 py-2 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="Mínimo 6 caracteres">
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                    Confirmar Contraseña
                </label>
                <div class="mt-1.5 relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock-check"></i>
                    </div>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="block w-full pl-10 pr-3 py-2 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="Repite la clave">
                </div>
            </div>
        </div>

        <button type="submit"
                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl shadow-lg shadow-blue-600/30 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all mt-2">
            <i class="fa-solid fa-check"></i>
            Crear Cuenta y Comenzar
        </button>
    </form>

    <div class="pt-4 border-t border-slate-700/60 text-center">
        <p class="text-xs text-slate-400">
            ¿Ya tienes una cuenta registrada?
            <a href="{{ route('login') }}" class="font-bold text-blue-400 hover:text-blue-300 transition-colors ml-1">
                Iniciar Sesión
            </a>
        </p>
    </div>
</div>
@endsection
