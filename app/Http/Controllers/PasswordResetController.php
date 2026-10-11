<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    /**
     * Muestra la vista para solicitar el enlace de restablecimiento.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Envía el correo con el enlace de restablecimiento.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $plainToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($plainToken),
                    'created_at' => Carbon::now(),
                ]
            );

            $resetUrl = route('password.reset', [
                'token' => $plainToken,
                'email' => $user->email,
            ]);

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
            } catch (\Throwable $e) {
                Log::error('Error enviando correo de recuperación con Brevo: '.$e->getMessage(), [
                    'email' => $user->email,
                    'exception' => $e,
                ]);
            }
        }

        return back()->with('status', 'Si tu correo se encuentra registrado en nuestra plataforma, hemos enviado un enlace para restablecer tu contraseña. Por favor revisa tu bandeja principal y la carpeta de spam.');
    }

    /**
     * Muestra el formulario para escribir la nueva contraseña.
     */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email', '')),
        ]);
    }

    /**
     * Valida el token y actualiza la contraseña del usuario.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'token.required' => 'El token de seguridad es requerido.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return back()->withErrors([
                'email' => 'No encontramos una solicitud de restablecimiento activa para este correo.',
            ])->onlyInput('email');
        }

        // Token expira en 60 minutos
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return back()->withErrors([
                'email' => 'El enlace de recuperación ha expirado. Por favor solicita uno nuevo.',
            ])->onlyInput('email');
        }

        // Validar token (compatible con Hash::make o token plano)
        $isValidToken = Hash::check($request->token, $record->token) || $request->token === $record->token;

        if (! $isValidToken) {
            return back()->withErrors([
                'email' => 'El token de recuperación no es válido o ha sido modificado.',
            ])->onlyInput('email');
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'No existe ningún usuario registrado con este correo electrónico.',
            ])->onlyInput('email');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')
            ->with('success', '¡Tu contraseña ha sido restablecida con éxito! Ya puedes ingresar con tu nueva clave.');
    }
}
