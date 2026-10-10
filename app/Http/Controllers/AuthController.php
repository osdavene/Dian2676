<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JobProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))
                ->with('success', '¡Bienvenido de nuevo, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $jobProfiles = JobProfile::where('is_active', true)->orderBy('id')->get();
        return view('auth.register', compact('jobProfiles'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(6)],
            'job_profile_id' => ['required', 'exists:job_profiles,id'],
        ], [
            'name.required' => 'El nombre completo es requerido.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya se encuentra registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'job_profile_id.required' => 'Debe seleccionar el cargo para el cual se está preparando.',
        ]);

        $jobProfile = JobProfile::findOrFail($request->job_profile_id);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'job_profile_id' => $jobProfile->id,
            'target_cargo' => $jobProfile->name,
            'role' => 'aspirante',
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', '¡Registro exitoso! Ya tienes acceso a tu perfil de ' . $jobProfile->name . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Has cerrado sesión correctamente.');
    }

    public function switchJob(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return back()->with('error', 'Acceso denegado: Tu cargo de concurso ha sido fijado institucionalmente. Solo un Administrador puede cambiar tu nivel.');
        }

        $job = JobProfile::findOrFail($id);

        $user->update([
            'job_profile_id' => $job->id,
            'target_cargo' => $job->name,
        ]);

        return back()->with('success', 'Has cambiado la vista de cargo a: ' . $job->name);
    }
}
