@extends('layouts.guest')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="space-y-6">
    <div class="border-b border-slate-700/60 pb-4">
        <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <i class="fa-solid fa-right-to-bracket text-blue-400"></i>
            Ingreso de Aspirante
        </h3>
        <p class="text-xs text-slate-400 mt-1">
            Inicia sesión para acceder a tus simulacros, historial y estadísticas.
        </p>
    </div>

    <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Correo Electrónico
            </label>
            <div class="mt-2 relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <input id="email" name="email" type="email" autocomplete="email" required
                       value="{{ old('email') }}"
                       class="block w-full pl-10 pr-3 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                       placeholder="tu.correo@ejemplo.com">
            </div>
            @error('email')
                <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Contraseña
            </label>
            <div class="mt-2 relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="block w-full pl-10 pr-3 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                       placeholder="••••••••">
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                </p>
            @enderror
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-blue-500">
                <span class="text-xs text-slate-300">Recordar sesión</span>
            </label>
            <span class="text-xs text-blue-400 font-medium">Acceso seguro</span>
        </div>

        <button type="submit"
                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl shadow-lg shadow-blue-600/30 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-blue-500 transition-all">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            Ingresar al Simulador
        </button>
    </form>

    <div class="pt-4 border-t border-slate-700/60 text-center">
        <p class="text-xs text-slate-400">
            ¿Aún no tienes cuenta?
            <a href="{{ route('register') }}" class="font-bold text-blue-400 hover:text-blue-300 transition-colors ml-1">
                Regístrate aquí y elige tu cargo
            </a>
        </p>
    </div>
</div>
@endsection
