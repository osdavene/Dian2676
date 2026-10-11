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

    <div class="relative my-2">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-700/80"></div></div>
        <div class="relative flex justify-center text-xs uppercase"><span class="bg-slate-800/90 px-3 text-slate-400 font-semibold tracking-wider">O regístrate con</span></div>
    </div>

    <a href="{{ route('auth.google') }}"
       class="w-full flex items-center justify-center gap-3 py-2.5 px-4 rounded-xl border border-slate-700/80 bg-slate-900/90 hover:bg-slate-800 hover:border-slate-600 text-white text-sm font-semibold transition-all shadow-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        Registrarse con Google
    </a>

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
