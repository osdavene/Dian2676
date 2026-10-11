<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionBankController;
use Illuminate\Support\Facades\Route;

// ─── Rutas Públicas (Huéspedes) ──────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    // Recuperación de Contraseña
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

    // Autenticación con Google OAuth 2.0
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/auth/google/completar-perfil', [AuthController::class, 'showGoogleCompleteProfile'])->name('auth.google.complete');
    Route::post('/auth/google/completar-perfil', [AuthController::class, 'storeGoogleCompleteProfile'])->name('auth.google.complete.post');
});

// ─── Rutas Autenticadas Básicas (No requieren verificación) ──────
Route::middleware('auth')->group(function () {
    // Cierre de Sesión
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout']);

    // Verificación de Correo Electrónico
    Route::get('/email/verify', [EmailVerificationController::class, 'showNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
});

// ─── Rutas Protegidas (Usuarios con Correo Verificado) ───────────
Route::middleware(['auth', 'verified'])->group(function () {
    // Inicio / Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Selector rápido de cargo activo
    Route::post('/cambiar-cargo/{id}', [AuthController::class, 'switchJob'])->name('cambiar.cargo');

    // Perfil de Usuario
    Route::get('/perfil', [ProfileController::class, 'show'])->name('perfil');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');

    // Simulador Oficial y Express
    Route::post('/simulacro/iniciar', [ExamController::class, 'startSimulacro'])->name('simulacro.iniciar');
    Route::get('/simulacros/historial', [ExamController::class, 'history'])->name('simulacros.historial');
    Route::get('/simulacro/{session}', [ExamController::class, 'showSimulacro'])->name('simulacro.view');
    Route::post('/simulacro/{session}/guardar-respuesta', [ExamController::class, 'saveSingleAnswer'])->name('simulacro.guardar-respuesta');
    Route::post('/simulacro/{session}/heartbeat', [ExamController::class, 'syncHeartbeat'])->name('simulacro.heartbeat');
    Route::post('/simulacro/{session}/pausar', [ExamController::class, 'pauseSimulacro'])->name('simulacro.pausar');
    Route::post('/simulacro/{session}/finalizar', [ExamController::class, 'submitSimulacro'])->name('simulacro.finalizar');
    Route::get('/simulacro/{session}/resultado', [ExamController::class, 'showResult'])->name('simulacro.resultado');

    // Modo Práctica Focalizada
    Route::get('/practica', [ExamController::class, 'practiceSelection'])->name('practica.index');
    Route::get('/practica/eje/{axis}', [ExamController::class, 'practiceAxis'])->name('practica.eje');

    // Repaso de Fallos Personal
    Route::get('/repaso-fallos', [ExamController::class, 'reviewMistakes'])->name('repaso.fallos');

    // Banco de Preguntas
    Route::get('/banco-preguntas', [QuestionBankController::class, 'index'])->name('banco.preguntas');

    // ─── Control Central (Super Usuario / Administrador) ─────────────
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/usuarios', [AdminController::class, 'users'])->name('users');
        Route::post('/usuarios', [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/usuarios/{id}', [AdminController::class, 'userDetail'])->name('users.detail');
        Route::put('/usuarios/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/usuarios/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::post('/usuarios/{id}/reiniciar', [AdminController::class, 'resetAttempts'])->name('users.reset_attempts');
        Route::get('/simulacros', [AdminController::class, 'examSessions'])->name('sessions');
        Route::get('/preguntas', [AdminController::class, 'questionsIndex'])->name('questions.index');
        Route::get('/preguntas/{id}/datos', [AdminController::class, 'getQuestionData'])->name('questions.data');
        Route::put('/preguntas/{id}', [AdminController::class, 'updateQuestion'])->name('questions.update');
        Route::post('/preguntas/desactivar-duplicados', [AdminController::class, 'deactivateDuplicates'])->name('questions.deactivate-duplicates');
        Route::get('/analitica-preguntas', [AdminController::class, 'questionAnalytics'])->name('questions.analytics');
        Route::get('/backup', [AdminController::class, 'backupCenter'])->name('backup');
        Route::post('/backup/generar', [AdminController::class, 'generateBackupFile'])->name('backup.generate');
        Route::get('/backup/descargar-directo', [AdminController::class, 'downloadDirectBackup'])->name('backup.download.direct');
        Route::get('/backup/descargar/{filename}', [AdminController::class, 'downloadBackupFile'])->name('backup.download');
        Route::post('/backup/importar', [AdminController::class, 'importBackupUpload'])->name('backup.import');
        Route::post('/backup/restaurar/{filename}', [AdminController::class, 'restoreServerBackup'])->name('backup.restore');
    });
});
