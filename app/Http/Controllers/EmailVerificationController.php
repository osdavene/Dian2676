<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Muestra la pantalla de aviso de verificación de correo.
     */
    public function showNotice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard'))
            : view('auth.verify-email');
    }

    /**
     * Marca el correo del usuario como verificado mediante el enlace firmado.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = $request->user();

        if ((string) $id !== (string) $user->getKey()) {
            abort(403, 'Identificador de usuario inválido.');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Enlace de verificación inválido o alterado.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'))
                ->with('info', 'Tu cuenta de correo ya había sido verificada con anterioridad.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard'))
            ->with('success', '¡Excelente! Tu correo electrónico ha sido verificado con éxito. Ya tienes acceso completo a tus simulacros y estadísticas.');
    }

    /**
     * Reenvía el correo de verificación.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Hemos enviado un nuevo enlace de confirmación a tu correo electrónico. Recuerda revisar tu bandeja de entrada y la carpeta de correo no deseado (spam).');
    }
}
