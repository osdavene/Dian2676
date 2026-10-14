<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\JobProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

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

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Demasiados intentos de acceso fallidos. Por seguridad, intente de nuevo en {$seconds} segundos.",
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', '¡Bienvenido de nuevo, '.Auth::user()->name.'!');
        }

        RateLimiter::hit($throttleKey, 60);

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

        // Enviar correo de verificación de cuenta
        $user->sendEmailVerificationNotification();

        // Enviar correo de bienvenida con detalles institucionales
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de bienvenida: '.$e->getMessage());
        }

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', '¡Registro exitoso! Por favor revisa tu correo electrónico para verificar tu cuenta y comenzar.');
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

        if (! $user->isAdmin()) {
            return back()->with('error', 'Acceso denegado: Tu cargo de concurso ha sido fijado institucionalmente. Solo un Administrador puede cambiar tu nivel.');
        }

        $job = JobProfile::findOrFail($id);

        $user->update([
            'job_profile_id' => $job->id,
            'target_cargo' => $job->name,
        ]);

        return back()->with('success', 'Has cambiado la vista de cargo a: '.$job->name);
    }

    // ─── Google OAuth 2.0 ─────────────────────────────────────────────

    public function redirectToGoogle()
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')
                ->with('error', 'El inicio de sesión con Google aún no está configurado (faltan GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en las variables de entorno).');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Error en autenticación con Google: '.$e->getMessage());

            return redirect()->route('login')
                ->with('error', 'No fue posible autenticar tu cuenta de Google. Inténtalo de nuevo o ingresa con tu correo y contraseña.');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Si ya existe pero no tenía vinculado el google_id o avatar, vincularlo
            $updates = [];
            if (empty($user->google_id)) {
                $updates['google_id'] = $googleUser->getId();
            }
            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $updates['avatar'] = $googleUser->getAvatar();
            }
            if (empty($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if (! empty($updates)) {
                $user->update($updates);
            }

            Auth::login($user, true);

            return redirect()->intended(route('dashboard'))
                ->with('success', '¡Bienvenido de nuevo, '.$user->name.'!');
        }

        // Es un usuario nuevo: guardar en sesión y pedir cargo meta DIAN
        session([
            'google_user' => [
                'id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ],
        ]);

        return redirect()->route('auth.google.complete');
    }

    public function showGoogleCompleteProfile()
    {
        if (! session()->has('google_user')) {
            return redirect()->route('login');
        }

        $googleUser = session('google_user');
        $jobProfiles = JobProfile::where('is_active', true)->orderBy('id')->get();

        return view('auth.google-complete', compact('googleUser', 'jobProfiles'));
    }

    public function storeGoogleCompleteProfile(Request $request)
    {
        if (! session()->has('google_user')) {
            return redirect()->route('login');
        }

        $googleUser = session('google_user');

        $request->validate([
            'job_profile_id' => ['required', 'exists:job_profiles,id'],
        ], [
            'job_profile_id.required' => 'Debe seleccionar el empleo al que aspira.',
        ]);

        $jobProfile = JobProfile::findOrFail($request->job_profile_id);

        $user = User::create([
            'name' => $googleUser['name'] ?? 'Aspirante DIAN',
            'email' => $googleUser['email'],
            'email_verified_at' => now(), // ¡Google ya verifica el correo!
            'google_id' => $googleUser['id'],
            'avatar' => $googleUser['avatar'],
            'password' => Hash::make(Str::random(32)),
            'job_profile_id' => $jobProfile->id,
            'target_cargo' => $jobProfile->name,
            'role' => 'aspirante',
        ]);

        session()->forget('google_user');

        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de bienvenida a usuario Google: '.$e->getMessage());
        }

        Auth::login($user, true);

        return redirect()->route('dashboard')
            ->with('success', '¡Registro exitoso! Ya estás listo para practicar para '.$jobProfile->name.'.');
    }
}
