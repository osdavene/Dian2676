<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\ProfileController;

// ─── Rutas Públicas (Huéspedes) ──────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// ─── Rutas Protegidas (Usuarios Autenticados) ─────────────────────
Route::middleware('auth')->group(function () {
    // Inicio / Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cierre de Sesión
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout']); // Soporte para enlaces directos

    // Selector rápido de cargo activo
    Route::post('/cambiar-cargo/{id}', [AuthController::class, 'switchJob'])->name('cambiar.cargo');

    // Perfil de Usuario
    Route::get('/perfil', [ProfileController::class, 'show'])->name('perfil');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');

    // Simulador Oficial
    Route::post('/simulacro/iniciar', [ExamController::class, 'startSimulacro'])->name('simulacro.iniciar');
    Route::get('/simulacro/{session}', [ExamController::class, 'showSimulacro'])->name('simulacro.view');
    Route::post('/simulacro/{session}/guardar-respuesta', [ExamController::class, 'saveSingleAnswer'])->name('simulacro.guardar-respuesta');
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
        Route::get('/', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/usuarios', [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
        Route::post('/usuarios', [\App\Http\Controllers\AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/usuarios/{id}', [\App\Http\Controllers\AdminController::class, 'userDetail'])->name('users.detail');
        Route::put('/usuarios/{id}', [\App\Http\Controllers\AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/usuarios/{id}', [\App\Http\Controllers\AdminController::class, 'deleteUser'])->name('users.delete');
        Route::get('/simulacros', [\App\Http\Controllers\AdminController::class, 'examSessions'])->name('sessions');
        Route::get('/analitica-preguntas', [\App\Http\Controllers\AdminController::class, 'questionAnalytics'])->name('questions.analytics');
        Route::get('/backup', [\App\Http\Controllers\AdminController::class, 'backupCenter'])->name('backup');
        Route::post('/backup/generar', [\App\Http\Controllers\AdminController::class, 'generateBackupFile'])->name('backup.generate');
        Route::get('/backup/descargar-directo', [\App\Http\Controllers\AdminController::class, 'downloadDirectBackup'])->name('backup.download.direct');
        Route::get('/backup/descargar/{filename}', [\App\Http\Controllers\AdminController::class, 'downloadBackupFile'])->name('backup.download');
        Route::post('/backup/importar', [\App\Http\Controllers\AdminController::class, 'importBackupUpload'])->name('backup.import');
        Route::post('/backup/restaurar/{filename}', [\App\Http\Controllers\AdminController::class, 'restoreServerBackup'])->name('backup.restore');
    });
});
