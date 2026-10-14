<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\JobProfile;
use App\Models\Question;
use App\Models\SessionAnswer;
use App\Models\ThematicAxis;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Dashboard Ejecutivo de Control Central
     */
    public function dashboard()
    {
        // 1. Métricas Globales
        $totalUsers = User::count();
        $totalAspirantes = User::where('role', 'aspirante')->count();
        $totalAdmins = User::whereIn('role', ['admin', 'superadmin'])->count();

        $totalQuestions = Question::count();
        $activeQuestions = Question::where('is_active', true)->count();
        $transversalQuestions = Question::where(function ($q) {
            $q->where('is_shared', true)->orWhereNull('job_profile_id');
        })->where('is_active', true)->count();

        $allCompletedSessions = ExamSession::whereNotNull('completed_at')->get();
        $totalExamsCompleted = $allCompletedSessions->count();
        $passedExamsCount = $allCompletedSessions->where('is_passed', true)->count();
        $globalPassRate = $totalExamsCompleted > 0 ? round(($passedExamsCount / $totalExamsCompleted) * 100, 1) : 0;
        $globalAvgScore = $totalExamsCompleted > 0 ? round($allCompletedSessions->avg('score'), 2) : 0.00;

        // Simulacros actualmente en curso (activos en vivo o pausados con tiempo disponible)
        $liveSessions = ExamSession::whereNull('completed_at')
            ->whereIn('mode', ['simulacro_oficial', 'simulacro_express'])
            ->where(function ($query) {
                $query->where('updated_at', '>=', now()->subHours(24))
                    ->orWhere('created_at', '>=', now()->subHours(24));
            })
            ->whereColumn('time_spent_seconds', '<', 'time_limit_seconds')
            ->with(['user', 'jobProfile'])
            ->withCount('sessionAnswers')
            ->orderBy('updated_at', 'desc')
            ->get();

        // 2. Desglose comparativo por Cargo
        $jobs = JobProfile::where('is_active', true)->orderBy('id')->get();
        $cargoStats = [];
        foreach ($jobs as $j) {
            $jSessions = ExamSession::where('job_profile_id', $j->id)->whereNotNull('completed_at')->get();
            $jUsersCount = User::where('job_profile_id', $j->id)->count();
            $jQuestionsCount = Question::forCargo($j->id)->where('is_active', true)->count();
            $jPassed = $jSessions->where('is_passed', true)->count();
            $jTotal = $jSessions->count();

            $cargoStats[] = [
                'job' => $j,
                'users_count' => $jUsersCount,
                'questions_count' => $jQuestionsCount,
                'exams_count' => $jTotal,
                'avg_score' => $jTotal > 0 ? round($jSessions->avg('score'), 2) : 0.00,
                'pass_rate' => $jTotal > 0 ? round(($jPassed / $jTotal) * 100, 1) : 0,
            ];
        }

        // 3. Últimos 10 simulacros completados en el sistema
        $recentSessions = ExamSession::whereNotNull('completed_at')
            ->with(['user', 'jobProfile'])
            ->orderBy('completed_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalAspirantes',
            'totalAdmins',
            'totalQuestions',
            'activeQuestions',
            'transversalQuestions',
            'totalExamsCompleted',
            'globalPassRate',
            'globalAvgScore',
            'liveSessions',
            'cargoStats',
            'recentSessions'
        ));
    }

    /**
     * Gestión y Control de Usuarios / Aspirantes
     */
    public function users(Request $request)
    {
        $search = $request->input('search');
        $jobId = $request->input('job_id');
        $role = $request->input('role');

        $driver = DB::connection()->getDriverName();
        $like = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

        $query = User::with(['jobProfile'])
            ->withCount([
                'examSessions as exams_count' => function ($q) {
                    $q->whereNotNull('completed_at');
                },
                'examSessions as passed_exams_count' => function ($q) {
                    $q->whereNotNull('completed_at')->where('is_passed', true);
                },
            ]);

        if ($search) {
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%");
            });
        }

        if ($jobId) {
            $query->where('job_profile_id', $jobId);
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $jobs = JobProfile::where('is_active', true)->orderBy('id')->get();

        return view('admin.users', compact('users', 'jobs', 'search', 'jobId', 'role'));
    }

    /**
     * Ficha de Detalle de un Aspirante
     */
    public function userDetail($id)
    {
        $aspirante = User::with(['jobProfile'])->findOrFail($id);
        $jobs = JobProfile::where('is_active', true)->orderBy('id')->get();

        $sessions = ExamSession::where('user_id', $aspirante->id)
            ->with(['jobProfile'])
            ->orderBy('id', 'desc')
            ->get();

        $completedSessions = $sessions->whereNotNull('completed_at');
        $total = $completedSessions->count();
        $passed = $completedSessions->where('is_passed', true)->count();
        $avgScore = $total > 0 ? round($completedSessions->avg('score'), 2) : 0.00;
        $bestScore = $total > 0 ? $completedSessions->max('score') : 0.00;

        return view('admin.user_detail', compact(
            'aspirante',
            'jobs',
            'sessions',
            'total',
            'passed',
            'avgScore',
            'bestScore'
        ));
    }

    /**
     * Crear un nuevo Aspirante / Usuario desde el Control Central
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:aspirante,admin,superadmin'],
            'job_profile_id' => ['required', 'exists:job_profiles,id'],
        ]);

        $job = JobProfile::findOrFail($validated['job_profile_id']);

        $newUser = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => $validated['password'], // Se encripta por el cast 'hashed' de User
            'role' => $validated['role'],
            'job_profile_id' => $job->id,
            'target_cargo' => $job->name,
        ]);

        return redirect()->route('admin.users')
            ->with('success', "¡Usuario {$newUser->name} creado exitosamente! Correo: {$newUser->email}. Ya puede iniciar sesión en la plataforma.");
    }

    /**
     * Actualizar datos, credenciales, Rol o Cargo de un Aspirante desde el Control Central
     */
    public function updateUser(Request $request, $id)
    {
        $aspirante = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$aspirante->id],
            'role' => ['required', 'in:aspirante,admin,superadmin'],
            'job_profile_id' => ['required', 'exists:job_profiles,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $job = JobProfile::findOrFail($validated['job_profile_id']);

        $updateData = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'job_profile_id' => $job->id,
            'target_cargo' => $job->name,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $aspirante->update($updateData);

        return back()->with('success', "Usuario {$aspirante->name} actualizado con éxito.");
    }

    /**
     * Eliminar usuario y sus registros asociados
     */
    public function deleteUser($id)
    {
        $currentAdmin = auth()->user();
        if ($currentAdmin && $currentAdmin->id == $id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta de administrador.');
        }

        $userToDelete = User::findOrFail($id);
        $userName = $userToDelete->name;

        // Limpiar registros relacionados
        $sessions = ExamSession::where('user_id', $userToDelete->id)->get();
        foreach ($sessions as $s) {
            SessionAnswer::where('exam_session_id', $s->id)->delete();
            $s->delete();
        }

        $userToDelete->delete();

        return redirect()->route('admin.users')
            ->with('success', "El usuario {$userName} ha sido eliminado permanentemente del sistema.");
    }

    /**
     * Reiniciar intentos y simulacros de un aspirante
     */
    public function resetAttempts($id)
    {
        $aspirante = User::findOrFail($id);

        $sessions = ExamSession::where('user_id', $aspirante->id)->get();
        $count = $sessions->count();

        foreach ($sessions as $s) {
            SessionAnswer::where('exam_session_id', $s->id)->delete();
            $s->delete();
        }

        return back()->with('success', "Se han reiniciado los {$count} simulacros de {$aspirante->name}. El aspirante ahora puede comenzar pruebas desde cero.");
    }

    /**
     * Monitoreo General de Simulacros (En vivo y completados)
     */
    public function examSessions(Request $request)
    {
        $status = $request->input('status', 'all');
        $jobId = $request->input('job_id');

        $query = ExamSession::with(['user', 'jobProfile'])
            ->orderBy('id', 'desc');

        if ($status === 'in_progress') {
            $query->whereNull('completed_at')->where('created_at', '>=', now()->subHours(5));
        } elseif ($status === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif ($status === 'passed') {
            $query->whereNotNull('completed_at')->where('is_passed', true);
        } elseif ($status === 'failed') {
            $query->whereNotNull('completed_at')->where('is_passed', false);
        }

        if ($jobId) {
            $query->where('job_profile_id', $jobId);
        }

        $sessions = $query->paginate(20)->withQueryString();
        $jobs = JobProfile::where('is_active', true)->orderBy('id')->get();

        return view('admin.sessions', compact('sessions', 'jobs', 'status', 'jobId'));
    }

    /**
     * Analítica de Preguntas Más Falladas (Diagnóstico Institucional)
     */
    public function questionAnalytics()
    {
        // Top 25 preguntas con más respuestas incorrectas
        $failedQuestions = Question::join('session_answers', 'session_answers.question_id', '=', 'questions.id')
            ->select(
                'questions.id',
                'questions.stem',
                'questions.case_text',
                'questions.correct_option',
                'questions.legal_basis',
                'questions.thematic_axis_id',
                'questions.job_profile_id',
                DB::raw('COUNT(session_answers.id) as total_attempts'),
                DB::raw('SUM(CASE WHEN session_answers.is_correct = false THEN 1 ELSE 0 END) as fail_count'),
                DB::raw('SUM(CASE WHEN session_answers.is_correct = true THEN 1 ELSE 0 END) as success_count')
            )
            ->groupBy(
                'questions.id',
                'questions.stem',
                'questions.case_text',
                'questions.correct_option',
                'questions.legal_basis',
                'questions.thematic_axis_id',
                'questions.job_profile_id'
            )
            ->havingRaw('COUNT(session_answers.id) > 0')
            ->orderByDesc('fail_count')
            ->take(25)
            ->with(['thematicAxis', 'jobProfile'])
            ->get();

        return view('admin.question_analytics', compact('failedQuestions'));
    }

    /**
     * Centro de Copias de Seguridad (Backup Total)
     */
    public function backupCenter()
    {
        $analista2Job = JobProfile::where('name', 'Analista II')->first();
        $analista3Job = JobProfile::where('name', 'Analista III')->first();
        $gestorJob = JobProfile::where('name', 'Gestor I')->first();
        $gestor3Job = JobProfile::where('name', 'Gestor III')->first();

        $totalQuestions = Question::count();
        $analista2Questions = Question::forCargo($analista2Job?->id)->count();
        $analista3Questions = Question::forCargo($analista3Job?->id)->count();
        $gestor1Questions = Question::forCargo($gestorJob?->id)->count();
        $gestor3Questions = Question::forCargo($gestor3Job?->id)->count();
        $tecnicoQuestions = Question::where('scope_level', 'tecnico')->count();
        $profesionalQuestions = Question::where('scope_level', 'profesional')->count();
        $sharedQuestions = Question::where('is_shared', true)->orWhere('scope_level', 'transversal')->count();
        $totalAxes = ThematicAxis::count();
        $totalJobs = JobProfile::count();

        // Leer archivos de backup existentes en storage/app/backups
        $backupDir = storage_path('app/backups');
        if (! file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $files = glob($backupDir.'/*.json');
        $backups = [];
        if ($files) {
            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $sizeBytes = filesize($filePath);
                $sizeFormatted = $sizeBytes > 1048576
                    ? number_format($sizeBytes / 1048576, 2).' MB'
                    : number_format($sizeBytes / 1024, 1).' KB';

                $backups[] = [
                    'filename' => $filename,
                    'path' => $filePath,
                    'size' => $sizeFormatted,
                    'created_at' => date('d/m/Y H:i:s', filemtime($filePath)),
                    'timestamp' => filemtime($filePath),
                ];
            }

            usort($backups, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });
        }

        return view('admin.backup', compact(
            'totalQuestions',
            'analista2Questions',
            'analista3Questions',
            'gestor1Questions',
            'gestor3Questions',
            'tecnicoQuestions',
            'profesionalQuestions',
            'sharedQuestions',
            'totalAxes',
            'totalJobs',
            'backups'
        ));
    }

    /**
     * Generar archivo de backup total en disco
     */
    public function generateBackupFile()
    {
        $data = $this->buildFullBackupPayload();
        $backupDir = storage_path('app/backups');
        if (! file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'dian2676_backup_total_'.date('Y-m-d_His').'.json';
        $fullPath = $backupDir.'/'.$filename;

        file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return back()->with('success', "Copia de seguridad total generada con éxito ({$filename}). Contiene {$data['metadata']['total_preguntas']} preguntas y todos los ejes temáticos.");
    }

    /**
     * Descargar backup directamente en el navegador sin guardarlo en disco
     */
    public function downloadDirectBackup()
    {
        $data = $this->buildFullBackupPayload();
        $filename = 'dian2676_backup_total_'.date('Y-m-d_His').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Descargar un archivo de backup previamente generado
     */
    public function downloadBackupFile($filename)
    {
        $cleanFilename = basename($filename);
        $fullPath = storage_path('app/backups/'.$cleanFilename);

        if (! file_exists($fullPath)) {
            return back()->with('error', 'El archivo de backup solicitado no existe.');
        }

        return response()->download($fullPath, $cleanFilename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Importar y restaurar copia de seguridad desde archivo subido por el usuario (JSON)
     */
    public function importBackupUpload(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:51200',
        ], [
            'backup_file.required' => 'Debe seleccionar un archivo de copia de seguridad (.json).',
            'backup_file.file' => 'El archivo proporcionado no es válido.',
            'backup_file.max' => 'El archivo supera el tamaño máximo permitido (50 MB).',
        ]);

        $file = $request->file('backup_file');
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (! $data || ! is_array($data)) {
            return back()->with('error', 'El archivo no contiene un formato JSON válido o está dañado.');
        }

        $overwrite = $request->boolean('overwrite', false);
        $result = $this->executeBackupRestore($data, $overwrite);

        return back()->with('success', "Copia de seguridad importada con éxito. {$result['message']}");
    }

    /**
     * Restaurar copia de seguridad desde un archivo previamente generado en el servidor
     */
    public function restoreServerBackup($filename, Request $request)
    {
        $cleanFilename = basename($filename);
        $fullPath = storage_path('app/backups/'.$cleanFilename);

        if (! file_exists($fullPath)) {
            return back()->with('error', 'El archivo de backup seleccionado no existe en el servidor.');
        }

        $content = file_get_contents($fullPath);
        $data = json_decode($content, true);

        if (! $data || ! is_array($data)) {
            return back()->with('error', 'El archivo de backup está dañado o no es un JSON válido.');
        }

        $overwrite = $request->boolean('overwrite', false);
        $result = $this->executeBackupRestore($data, $overwrite);

        return back()->with('success', "Copia de seguridad '{$cleanFilename}' restaurada con éxito. {$result['message']}");
    }

    /**
     * Lógica central de restauración e importación de datos
     */
    private function executeBackupRestore(array $data, bool $overwrite = false): array
    {
        if (isset($data[0]['stem'])) {
            $data = ['questions' => $data];
        }

        if (! empty($data['job_profiles'])) {
            foreach ($data['job_profiles'] as $jp) {
                JobProfile::firstOrCreate(
                    ['name' => $jp['name']],
                    [
                        'code' => $jp['code'] ?? null,
                        'level' => $jp['level'] ?? null,
                        'grade' => $jp['grade'] ?? null,
                        'process' => $jp['process'] ?? null,
                        'subprocess' => $jp['subprocess'] ?? null,
                        'purpose' => $jp['purpose'] ?? null,
                        'is_active' => $jp['is_active'] ?? true,
                    ]
                );
            }
        }

        $axisMapByName = [];
        $axisMapById = [];
        $axesProcessed = 0;

        if (! empty($data['thematic_axes'])) {
            foreach ($data['thematic_axes'] as $ax) {
                $axis = ThematicAxis::firstOrCreate(
                    ['name' => $ax['name']],
                    [
                        'category' => $ax['category'] ?? 'funcional_especifica',
                        'description' => $ax['description'] ?? null,
                        'weight_pct' => $ax['weight_pct'] ?? 10.00,
                        'job_profile_id' => $ax['job_profile_id'] ?? 1,
                    ]
                );
                $axisMapByName[$ax['name']] = $axis->id;
                if (isset($ax['id'])) {
                    $axisMapById[$ax['id']] = $axis->id;
                }
                $axesProcessed++;
            }
        }

        $questionsList = $data['questions'] ?? [];
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($questionsList as $q) {
            $stem = trim($q['stem'] ?? '');
            if (! $stem) {
                continue;
            }

            $axisId = null;
            if (! empty($q['thematic_axis_name']) && isset($axisMapByName[$q['thematic_axis_name']])) {
                $axisId = $axisMapByName[$q['thematic_axis_name']];
            } elseif (! empty($q['thematic_axis_id']) && isset($axisMapById[$q['thematic_axis_id']])) {
                $axisId = $axisMapById[$q['thematic_axis_id']];
            } elseif (! empty($q['thematic_axis_id'])) {
                $axisId = $q['thematic_axis_id'];
            }

            $options = $q['options'] ?? null;
            if (! $options && isset($q['option_a'])) {
                $options = [
                    ['key' => 'A', 'text' => $q['option_a'], 'is_correct' => ($q['correct_option'] ?? '') === 'A'],
                    ['key' => 'B', 'text' => $q['option_b'], 'is_correct' => ($q['correct_option'] ?? '') === 'B'],
                    ['key' => 'C', 'text' => $q['option_c'], 'is_correct' => ($q['correct_option'] ?? '') === 'C'],
                ];
                if (! empty($q['option_d'])) {
                    $options[] = ['key' => 'D', 'text' => $q['option_d'], 'is_correct' => ($q['correct_option'] ?? '') === 'D'];
                }
            }

            $jobId = $q['job_profile_id'] ?? null;
            $existing = Question::where('stem', $stem)
                ->where(function ($query) use ($jobId, $axisId) {
                    if ($jobId) {
                        $query->where('job_profile_id', $jobId);
                    } else {
                        $query->whereNull('job_profile_id');
                    }
                    if ($axisId) {
                        $query->where('thematic_axis_id', $axisId);
                    } else {
                        $query->whereNull('thematic_axis_id');
                    }
                })
                ->first();
            if ($existing) {
                if ($overwrite) {
                    $existing->update([
                        'thematic_axis_id' => $axisId ?? $existing->thematic_axis_id,
                        'case_text' => $q['case_text'] ?? $existing->case_text,
                        'options' => $options ?? $existing->options,
                        'correct_option' => $q['correct_option'] ?? $existing->correct_option,
                        'legal_basis' => $q['legal_basis'] ?? $existing->legal_basis,
                        'explanation_summary' => $q['explanation_summary'] ?? ($q['rationale'] ?? $existing->explanation_summary),
                        'question_type' => $q['question_type'] ?? $existing->question_type,
                        'difficulty' => $q['difficulty'] ?? $existing->difficulty,
                        'is_shared' => $q['is_shared'] ?? $existing->is_shared,
                        'scope_level' => $q['scope_level'] ?? $existing->scope_level,
                        'is_active' => $q['is_active'] ?? $existing->is_active,
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                Question::create([
                    'job_profile_id' => $q['job_profile_id'] ?? null,
                    'thematic_axis_id' => $axisId,
                    'case_text' => $q['case_text'] ?? null,
                    'stem' => $stem,
                    'options' => $options,
                    'correct_option' => $q['correct_option'] ?? 'A',
                    'explanation_summary' => $q['explanation_summary'] ?? ($q['rationale'] ?? null),
                    'legal_basis' => $q['legal_basis'] ?? null,
                    'question_type' => $q['question_type'] ?? 'situacional',
                    'difficulty' => $q['difficulty'] ?? 'Media',
                    'is_active' => $q['is_active'] ?? true,
                    'is_shared' => $q['is_shared'] ?? true,
                    'scope_level' => $q['scope_level'] ?? 'transversal',
                ]);
                $inserted++;
            }
        }

        try {
            $axesDir = database_path('seeders/data');
            if (is_dir($axesDir)) {
                $allAxes = ThematicAxis::all()->toArray();
                file_put_contents($axesDir.'/thematic_axes.json', json_encode($allAxes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $allQuestions = Question::all()->toArray();
                file_put_contents($axesDir.'/questions.json', json_encode($allQuestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            // Ignorar errores menores de escritura en data
        }

        $totalInDb = Question::count();
        $message = "Se insertaron {$inserted} reactivos nuevos, {$updated} actualizados y {$skipped} se conservaron sin cambios. Total actual en el banco: {$totalInDb} preguntas (Ejes procesados: {$axesProcessed}).";

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $totalInDb,
            'message' => $message,
        ];
    }

    /**
     * Construye el paquete completo de datos para el backup total
     */
    private function buildFullBackupPayload(): array
    {
        $jobs = JobProfile::all();
        $axes = ThematicAxis::with('jobProfile')->get();
        $questions = Question::with(['jobProfile', 'thematicAxis'])->get();
        $users = User::select('id', 'name', 'email', 'role', 'target_cargo', 'job_profile_id', 'created_at')->get();

        $analista2Job = JobProfile::where('name', 'Analista II')->first();
        $analista3Job = JobProfile::where('name', 'Analista III')->first();
        $gestorJob = JobProfile::where('name', 'Gestor I')->first();
        $gestor3Job = JobProfile::where('name', 'Gestor III')->first();

        return [
            'metadata' => [
                'sistema' => 'Simulador Oficial Concurso DIAN 2676 — CNSC',
                'tipo_backup' => 'BACKUP_TOTAL_BANCO_DE_PREGUNTAS_Y_SISTEMA',
                'fecha_generacion' => now()->toIso8601String(),
                'version_motor' => '1.0.0-PRO',
                'total_cargos' => $jobs->count(),
                'total_ejes' => $axes->count(),
                'total_preguntas' => $questions->count(),
                'preguntas_analista_ii' => Question::forCargo($analista2Job?->id)->count(),
                'preguntas_analista_iii' => Question::forCargo($analista3Job?->id)->count(),
                'preguntas_gestor_i' => Question::forCargo($gestorJob?->id)->count(),
                'preguntas_gestor_iii' => Question::forCargo($gestor3Job?->id)->count(),
                'preguntas_nivel_tecnico' => $questions->where('scope_level', 'tecnico')->count(),
                'preguntas_compartidas_transversales' => $questions->filter(function ($q) {
                    return $q->is_shared || $q->scope_level === 'transversal';
                })->count(),
                'total_usuarios' => $users->count(),
            ],
            'job_profiles' => $jobs,
            'thematic_axes' => $axes,
            'questions' => $questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'job_profile_id' => $q->job_profile_id,
                    'job_profile_name' => $q->jobProfile->name ?? null,
                    'is_shared' => $q->is_shared,
                    'scope_level' => $q->scope_level,
                    'thematic_axis_id' => $q->thematic_axis_id,
                    'thematic_axis_name' => $q->thematicAxis->name ?? null,
                    'question_type' => $q->question_type,
                    'difficulty' => $q->difficulty,
                    'case_text' => $q->case_text,
                    'stem' => $q->stem,
                    'options' => $q->options,
                    'correct_option' => $q->correct_option,
                    'legal_basis' => $q->legal_basis,
                    'explanation_summary' => $q->explanation_summary,
                    'is_active' => $q->is_active,
                ];
            }),
            'users_metadata' => $users,
        ];
    }

    /**
     * Módulo de Gestión y Corrección de Preguntas del Super Administrador
     */
    public function questionsIndex(Request $request)
    {
        $search = $request->input('search');
        $jobId = $request->input('job_id');
        $axisId = $request->input('axis_id');
        $difficulty = $request->input('difficulty');
        $viewDuplicates = $request->boolean('duplicates', false);
        $onlyCases = $request->boolean('only_cases', false);

        $jobs = JobProfile::where('is_active', true)->orderBy('id')->get();
        $axes = ThematicAxis::orderBy('name')->get();

        $query = Question::with(['jobProfile', 'thematicAxis']);

        if ($viewDuplicates) {
            // Obtener preguntas cuyos enunciados estén repetidos
            $duplicateStems = Question::select('stem')
                ->where('is_active', true)
                ->groupBy('stem')
                ->havingRaw('count(*) > 1')
                ->pluck('stem');

            $query->whereIn('stem', $duplicateStems)->orderBy('stem')->orderBy('id');
        } else {
            if ($search) {
                $driver = DB::connection()->getDriverName();
                $like = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

                $query->where(function ($q) use ($search, $like) {
                    if (is_numeric($search)) {
                        $q->where('id', (int) $search);
                    }
                    $q->orWhere('stem', $like, "%{$search}%")
                        ->orWhere('case_text', $like, "%{$search}%")
                        ->orWhere('legal_basis', $like, "%{$search}%")
                        ->orWhere('explanation_summary', $like, "%{$search}%");
                });
            }

            if ($jobId) {
                if ($jobId === 'globales') {
                    $query->whereNull('job_profile_id');
                } else {
                    $query->where('job_profile_id', $jobId);
                }
            }

            if ($axisId) {
                $query->where('thematic_axis_id', $axisId);
            }

            if ($difficulty) {
                $query->where('difficulty', $difficulty);
            }

            if ($onlyCases) {
                $query->whereNotNull('case_text')->where('case_text', '!=', '');
            }

            $query->orderBy('id', 'desc');
        }

        $questions = $query->paginate(20)->withQueryString();

        // Conteo de preguntas duplicadas para badge
        $totalDuplicates = Question::select('stem', DB::raw('count(*) as c'))
            ->where('is_active', true)
            ->groupBy('stem')
            ->havingRaw('count(*) > 1')
            ->get()
            ->sum('c');

        return view('admin.questions.index', compact(
            'questions',
            'jobs',
            'axes',
            'search',
            'jobId',
            'axisId',
            'difficulty',
            'viewDuplicates',
            'onlyCases',
            'totalDuplicates'
        ));
    }

    /**
     * Obtener datos de una pregunta para el modal de edición AJAX
     */
    public function getQuestionData($id)
    {
        $q = Question::with(['thematicAxis', 'jobProfile'])->findOrFail($id);

        return response()->json([
            'id' => $q->id,
            'stem' => $q->stem,
            'case_text' => $q->case_text,
            'correct_option' => $q->correct_option,
            'option_a' => $q->option_a,
            'option_b' => $q->option_b,
            'option_c' => $q->option_c,
            'option_d' => $q->option_d,
            'legal_basis' => $q->legal_basis,
            'explanation_summary' => $q->explanation_summary,
            'thematic_axis_id' => $q->thematic_axis_id,
            'difficulty' => $q->difficulty,
            'is_active' => (bool) $q->is_active,
            'job_profile_id' => $q->job_profile_id,
            'job_name' => $q->jobProfile->name ?? 'Transversal / Todos',
            'axis_name' => $q->thematicAxis->name ?? 'General',
        ]);
    }

    /**
     * Guardar corrección de una pregunta y recalcular exámenes afectados
     */
    public function updateQuestion(Request $request, $id)
    {
        $question = Question::findOrFail($id);

        $request->validate([
            'correct_option' => 'required|in:A,B,C,D',
            'stem' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
        ]);

        $oldCorrect = strtoupper(trim($question->correct_option));
        $newCorrect = strtoupper(trim($request->input('correct_option')));

        // Reconstruir array de opciones con la nueva respuesta correcta
        $updatedOptions = [
            [
                'key' => 'A',
                'text' => trim($request->input('option_a')),
                'is_correct' => ($newCorrect === 'A'),
            ],
            [
                'key' => 'B',
                'text' => trim($request->input('option_b')),
                'is_correct' => ($newCorrect === 'B'),
            ],
            [
                'key' => 'C',
                'text' => trim($request->input('option_c')),
                'is_correct' => ($newCorrect === 'C'),
            ],
        ];

        if ($request->filled('option_d')) {
            $updatedOptions[] = [
                'key' => 'D',
                'text' => trim($request->input('option_d')),
                'is_correct' => ($newCorrect === 'D'),
            ];
        }

        $question->update([
            'stem' => trim($request->input('stem')),
            'case_text' => $request->filled('case_text') ? trim($request->input('case_text')) : null,
            'correct_option' => $newCorrect,
            'options' => $updatedOptions,
            'legal_basis' => $request->input('legal_basis'),
            'explanation_summary' => $request->input('explanation_summary'),
            'thematic_axis_id' => $request->input('thematic_axis_id') ?: $question->thematic_axis_id,
            'difficulty' => $request->input('difficulty') ?: $question->difficulty,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Si la respuesta correcta cambió, recalcular respuestas de aspirantes y puntajes de exámenes
        $recalcExamsCount = 0;
        $updatedAnswersCount = 0;

        if ($oldCorrect !== $newCorrect && $request->boolean('recalculate_scores', true)) {
            $answers = SessionAnswer::where('question_id', $question->id)->get();
            $affectedSessions = [];

            foreach ($answers as $ans) {
                $userPick = strtoupper(trim($ans->selected_option ?? ''));
                $isNowCorrect = (! empty($userPick) && $userPick === $newCorrect);

                if ($ans->is_correct !== $isNowCorrect) {
                    $ans->update([
                        'is_correct' => $isNowCorrect,
                    ]);
                    $affectedSessions[] = $ans->exam_session_id;
                    $updatedAnswersCount++;
                }
            }

            foreach (array_unique($affectedSessions) as $sId) {
                $session = ExamSession::find($sId);
                if ($session) {
                    $total = $session->total_questions > 0 ? $session->total_questions : count($session->question_ids ?? []);
                    if ($total > 0) {
                        $sAnswers = SessionAnswer::where('exam_session_id', $session->id)->get();
                        $cCount = $sAnswers->where('is_correct', true)->count();
                        $iCount = $sAnswers->where('is_correct', false)->where('is_omitted', false)->count();
                        $oCount = max(0, $total - $sAnswers->count());
                        $rawScore = ($cCount / $total) * 100;
                        $score = floor($rawScore * 100) / 100;

                        $session->update([
                            'correct_answers' => $cCount,
                            'incorrect_answers' => $iCount,
                            'omitted_answers' => $oCount,
                            'score' => $score,
                            'is_passed' => ($score >= 70.00),
                        ]);
                        $recalcExamsCount++;
                    }
                }
            }
        }

        $msg = "Pregunta #{$question->id} actualizada exitosamente.";
        if ($oldCorrect !== $newCorrect) {
            $msg .= " La opción correcta cambió de '{$oldCorrect}' a '{$newCorrect}'.";
            if ($recalcExamsCount > 0) {
                $msg .= " Se recalcularon automáticamente {$recalcExamsCount} exámenes de aspirantes ({$updatedAnswersCount} respuestas corregidas).";
            }
        }

        return back()->with('success', $msg);
    }

    /**
     * Herramienta Superadmin: Desactivar copias duplicadas de preguntas
     */
    public function deactivateDuplicates()
    {
        $duplicateStems = Question::select('stem')
            ->where('is_active', true)
            ->groupBy('stem')
            ->havingRaw('count(*) > 1')
            ->get();

        $deactivatedCount = 0;
        foreach ($duplicateStems as $dup) {
            $copies = Question::where('stem', $dup->stem)
                ->where('is_active', true)
                ->orderBy('id', 'asc')
                ->get();

            if ($copies->count() > 1) {
                // Conservar la primera pregunta ($copies->first()) y desactivar las copias redundantes
                $duplicatesToDeactivate = $copies->slice(1);
                foreach ($duplicatesToDeactivate as $q) {
                    $q->update(['is_active' => false]);
                    $deactivatedCount++;
                }
            }
        }

        return back()->with('success', "Se desactivaron {$deactivatedCount} preguntas redundantes. Ahora todas las preguntas activas son 100% únicas.");
    }

    /**
     * Crear una nueva pregunta desde el panel de Super Administrador
     */
    public function storeQuestion(Request $request)
    {
        $request->validate([
            'stem' => 'required|string',
            'case_text' => 'nullable|string',
            'thematic_axis_id' => 'required|exists:thematic_axes,id',
            'job_profile_id' => 'nullable|exists:job_profiles,id',
            'difficulty' => 'nullable|string',
            'question_type' => 'nullable|string',
            'correct_option' => 'required|in:A,B,C,D',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'nullable|string',
            'legal_basis' => 'nullable|string',
            'explanation_summary' => 'nullable|string',
        ], [
            'stem.required' => 'El enunciado de la pregunta es obligatorio.',
            'thematic_axis_id.required' => 'Debe seleccionar el eje temático.',
            'correct_option.required' => 'Debe seleccionar la clave correcta (A, B, C o D).',
            'option_a.required' => 'La opción A es obligatoria.',
            'option_b.required' => 'La opción B es obligatoria.',
            'option_c.required' => 'La opción C es obligatoria.',
        ]);

        $correct = strtoupper(trim($request->input('correct_option')));

        $options = [
            [
                'key' => 'A',
                'text' => trim($request->input('option_a')),
                'is_correct' => ($correct === 'A'),
                'justification' => $request->input('justification_a') ?: ($correct === 'A' ? $request->input('explanation_summary') : ''),
            ],
            [
                'key' => 'B',
                'text' => trim($request->input('option_b')),
                'is_correct' => ($correct === 'B'),
                'justification' => $request->input('justification_b') ?: ($correct === 'B' ? $request->input('explanation_summary') : ''),
            ],
            [
                'key' => 'C',
                'text' => trim($request->input('option_c')),
                'is_correct' => ($correct === 'C'),
                'justification' => $request->input('justification_c') ?: ($correct === 'C' ? $request->input('explanation_summary') : ''),
            ],
        ];

        if ($request->filled('option_d')) {
            $options[] = [
                'key' => 'D',
                'text' => trim($request->input('option_d')),
                'is_correct' => ($correct === 'D'),
                'justification' => $request->input('justification_d') ?: ($correct === 'D' ? $request->input('explanation_summary') : ''),
            ];
        }

        $isShared = $request->boolean('is_shared', true);
        $scopeLevel = $request->input('scope_level') ?: ($request->filled('job_profile_id') ? 'profesional' : 'transversal');

        $question = Question::create([
            'job_profile_id' => $request->input('job_profile_id') ?: null,
            'thematic_axis_id' => $request->input('thematic_axis_id'),
            'case_text' => $request->filled('case_text') ? trim($request->input('case_text')) : null,
            'stem' => trim($request->input('stem')),
            'options' => $options,
            'correct_option' => $correct,
            'explanation_summary' => $request->input('explanation_summary'),
            'legal_basis' => $request->input('legal_basis'),
            'question_type' => $request->input('question_type', 'situacional'),
            'difficulty' => $request->input('difficulty', 'medio'),
            'is_active' => $request->boolean('is_active', true),
            'is_shared' => $isShared,
            'scope_level' => $scopeLevel,
        ]);

        $this->syncQuestionsJson();

        return redirect()->route('admin.questions.index')
            ->with('success', "¡Pregunta #{$question->id} creada y agregada al banco con éxito!");
    }

    /**
     * Descarga plantilla CSV para importación masiva de preguntas
     */
    public function downloadQuestionTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_preguntas_dian2676.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'enunciado',
                'caso_estudio',
                'opcion_a',
                'opcion_b',
                'opcion_c',
                'opcion_d',
                'clave_correcta',
                'eje_tematico_id',
                'dificultad',
                'fundamento_legal',
                'explicacion_resumen',
            ]);

            fputcsv($handle, [
                '¿Cuál es el término legal para responder un derecho de petición de interés general?',
                'Un ciudadano radica una solicitud ante la DIAN requiriendo información sobre un trámite.',
                '15 días hábiles.',
                '10 días hábiles.',
                '30 días calendario.',
                '',
                'A',
                '6',
                'medio',
                'CPACA Ley 1437 de 2011 Artículo 14',
                'El término general para resolver peticiones de interés general es de 15 días hábiles siguientes a su recepción.',
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Importar preguntas masivamente desde archivo CSV o JSON
     */
    public function importQuestionsFile(Request $request)
    {
        $request->validate([
            'questions_file' => 'required|file|max:20480',
        ], [
            'questions_file.required' => 'Debe adjuntar un archivo para importar.',
            'questions_file.file' => 'El archivo no es válido.',
            'questions_file.max' => 'El archivo no debe exceder 20 MB.',
        ]);

        $file = $request->file('questions_file');
        $extension = strtolower($file->getClientOriginalExtension());

        $defaultAxisId = $request->input('default_axis_id') ?: ThematicAxis::first()?->id;
        $defaultJobId = $request->input('default_job_id') ?: null;

        $insertedCount = 0;
        $skippedCount = 0;

        if ($extension === 'json') {
            $data = json_decode(file_get_contents($file->getRealPath()), true);
            if (! is_array($data)) {
                return back()->with('error', 'El archivo JSON no tiene un formato válido.');
            }

            $list = isset($data['questions']) ? $data['questions'] : $data;
            foreach ($list as $row) {
                $stem = trim($row['stem'] ?? $row['enunciado'] ?? '');
                if (! $stem) {
                    continue;
                }

                if (Question::where('stem', $stem)->exists()) {
                    $skippedCount++;

                    continue;
                }

                $correct = strtoupper(trim($row['correct_option'] ?? $row['clave_correcta'] ?? 'A'));
                $options = $row['options'] ?? [
                    ['key' => 'A', 'text' => $row['option_a'] ?? $row['opcion_a'] ?? '', 'is_correct' => $correct === 'A'],
                    ['key' => 'B', 'text' => $row['option_b'] ?? $row['opcion_b'] ?? '', 'is_correct' => $correct === 'B'],
                    ['key' => 'C', 'text' => $row['option_c'] ?? $row['opcion_c'] ?? '', 'is_correct' => $correct === 'C'],
                ];
                if (! empty($row['option_d']) || ! empty($row['opcion_d'])) {
                    $options[] = ['key' => 'D', 'text' => $row['option_d'] ?? $row['opcion_d'], 'is_correct' => $correct === 'D'];
                }

                Question::create([
                    'job_profile_id' => $row['job_profile_id'] ?? $defaultJobId,
                    'thematic_axis_id' => $row['thematic_axis_id'] ?? $defaultAxisId,
                    'case_text' => $row['case_text'] ?? $row['caso_estudio'] ?? null,
                    'stem' => $stem,
                    'options' => $options,
                    'correct_option' => $correct,
                    'legal_basis' => $row['legal_basis'] ?? $row['fundamento_legal'] ?? null,
                    'explanation_summary' => $row['explanation_summary'] ?? $row['explicacion_resumen'] ?? null,
                    'question_type' => $row['question_type'] ?? 'situacional',
                    'difficulty' => $row['difficulty'] ?? 'medio',
                    'is_active' => true,
                    'is_shared' => true,
                    'scope_level' => 'transversal',
                ]);
                $insertedCount++;
            }
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return back()->with('error', 'No se pudo leer el archivo CSV.');
            }

            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $firstLine = fgets($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }

            $headers = fgetcsv($handle, 0, $delimiter);
            if (! $headers) {
                fclose($handle);

                return back()->with('error', 'El archivo CSV está vacío.');
            }

            $cleanHeaders = array_map(function ($h) {
                return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $h)));
            }, $headers);

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count($row) < 3) {
                    continue;
                }

                $dataRow = [];
                foreach ($cleanHeaders as $idx => $key) {
                    $dataRow[$key] = isset($row[$idx]) ? trim($row[$idx]) : '';
                }

                $stem = $dataRow['enunciado'] ?? $dataRow['stem'] ?? '';
                if (! $stem) {
                    continue;
                }

                if (Question::where('stem', $stem)->exists()) {
                    $skippedCount++;

                    continue;
                }

                $correct = strtoupper(trim($dataRow['clave_correcta'] ?? $dataRow['correct_option'] ?? 'A'));
                if (! in_array($correct, ['A', 'B', 'C', 'D'])) {
                    $correct = 'A';
                }

                $optA = $dataRow['opcion_a'] ?? $dataRow['option_a'] ?? '';
                $optB = $dataRow['opcion_b'] ?? $dataRow['option_b'] ?? '';
                $optC = $dataRow['opcion_c'] ?? $dataRow['option_c'] ?? '';
                $optD = $dataRow['opcion_d'] ?? $dataRow['option_d'] ?? '';

                if (! $optA || ! $optB) {
                    continue;
                }

                $options = [
                    ['key' => 'A', 'text' => $optA, 'is_correct' => ($correct === 'A')],
                    ['key' => 'B', 'text' => $optB, 'is_correct' => ($correct === 'B')],
                ];
                if ($optC) {
                    $options[] = ['key' => 'C', 'text' => $optC, 'is_correct' => ($correct === 'C')];
                }
                if ($optD) {
                    $options[] = ['key' => 'D', 'text' => $optD, 'is_correct' => ($correct === 'D')];
                }

                $axisId = ! empty($dataRow['eje_tematico_id']) ? (int) $dataRow['eje_tematico_id'] : $defaultAxisId;

                Question::create([
                    'job_profile_id' => $defaultJobId,
                    'thematic_axis_id' => $axisId,
                    'case_text' => $dataRow['caso_estudio'] ?? $dataRow['case_text'] ?? null,
                    'stem' => $stem,
                    'options' => $options,
                    'correct_option' => $correct,
                    'legal_basis' => $dataRow['fundamento_legal'] ?? $dataRow['legal_basis'] ?? null,
                    'explanation_summary' => $dataRow['explicacion_resumen'] ?? $dataRow['explanation_summary'] ?? null,
                    'question_type' => 'situacional',
                    'difficulty' => $dataRow['dificultad'] ?? 'medio',
                    'is_active' => true,
                    'is_shared' => true,
                    'scope_level' => 'transversal',
                ]);
                $insertedCount++;
            }
            fclose($handle);
        }

        $this->syncQuestionsJson();

        return redirect()->route('admin.questions.index')
            ->with('success', "¡Importación completada! Se insertaron {$insertedCount} preguntas nuevas ({$skippedCount} duplicadas omitidas).");
    }

    /**
     * Sincroniza las preguntas en database/seeders/data/questions.json
     */
    private function syncQuestionsJson(): void
    {
        try {
            $path = database_path('seeders/data/questions.json');
            if (file_exists($path)) {
                $all = Question::all()->toArray();
                file_put_contents($path, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            // Ignorar errores menores de sincronización en caliente
        }
    }
}
