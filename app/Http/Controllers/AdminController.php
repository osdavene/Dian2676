<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JobProfile;
use App\Models\ThematicAxis;
use App\Models\Question;
use App\Models\ExamSession;
use App\Models\SessionAnswer;
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

        $allCompletedSessions = ExamSession::whereNotNull('completed_at')->get();
        $totalExamsCompleted = $allCompletedSessions->count();
        $passedExamsCount = $allCompletedSessions->where('is_passed', true)->count();
        $globalPassRate = $totalExamsCompleted > 0 ? round(($passedExamsCount / $totalExamsCompleted) * 100, 1) : 0;
        $globalAvgScore = $totalExamsCompleted > 0 ? round($allCompletedSessions->avg('score'), 2) : 0.00;

        // Simulacros actualmente en curso (activos en vivo)
        $liveSessions = ExamSession::whereNull('completed_at')
            ->where('mode', 'simulacro_oficial')
            ->where('created_at', '>=', now()->subHours(5))
            ->with(['user', 'jobProfile'])
            ->orderBy('id', 'desc')
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

        $query = User::with(['jobProfile'])
            ->withCount([
                'examSessions as exams_count' => function ($q) {
                    $q->whereNotNull('completed_at');
                },
                'examSessions as passed_exams_count' => function ($q) {
                    $q->whereNotNull('completed_at')->where('is_passed', true);
                }
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $aspirante->id],
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

        if (!empty($validated['password'])) {
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
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $files = glob($backupDir . '/*.json');
        $backups = [];
        if ($files) {
            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $sizeBytes = filesize($filePath);
                $sizeFormatted = $sizeBytes > 1048576 
                    ? number_format($sizeBytes / 1048576, 2) . ' MB' 
                    : number_format($sizeBytes / 1024, 1) . ' KB';

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
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'dian2676_backup_total_' . date('Y-m-d_His') . '.json';
        $fullPath = $backupDir . '/' . $filename;

        file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return back()->with('success', "Copia de seguridad total generada con éxito ({$filename}). Contiene {$data['metadata']['total_preguntas']} preguntas y todos los ejes temáticos.");
    }

    /**
     * Descargar backup directamente en el navegador sin guardarlo en disco
     */
    public function downloadDirectBackup()
    {
        $data = $this->buildFullBackupPayload();
        $filename = 'dian2676_backup_total_' . date('Y-m-d_His') . '.json';

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
        $fullPath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($fullPath)) {
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
            'backup_file.max' => 'El archivo supera el tamaño máximo permitido (50 MB).'
        ]);

        $file = $request->file('backup_file');
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
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
        $fullPath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($fullPath)) {
            return back()->with('error', 'El archivo de backup seleccionado no existe en el servidor.');
        }

        $content = file_get_contents($fullPath);
        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
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

        if (!empty($data['job_profiles'])) {
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

        if (!empty($data['thematic_axes'])) {
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
            if (!$stem) continue;

            $axisId = null;
            if (!empty($q['thematic_axis_name']) && isset($axisMapByName[$q['thematic_axis_name']])) {
                $axisId = $axisMapByName[$q['thematic_axis_name']];
            } elseif (!empty($q['thematic_axis_id']) && isset($axisMapById[$q['thematic_axis_id']])) {
                $axisId = $axisMapById[$q['thematic_axis_id']];
            } elseif (!empty($q['thematic_axis_id'])) {
                $axisId = $q['thematic_axis_id'];
            }

            $options = $q['options'] ?? null;
            if (!$options && isset($q['option_a'])) {
                $options = [
                    ['key' => 'A', 'text' => $q['option_a'], 'is_correct' => ($q['correct_option'] ?? '') === 'A'],
                    ['key' => 'B', 'text' => $q['option_b'], 'is_correct' => ($q['correct_option'] ?? '') === 'B'],
                    ['key' => 'C', 'text' => $q['option_c'], 'is_correct' => ($q['correct_option'] ?? '') === 'C'],
                ];
                if (!empty($q['option_d'])) {
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
                file_put_contents($axesDir . '/thematic_axes.json', json_encode($allAxes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $allQuestions = Question::all()->toArray();
                file_put_contents($axesDir . '/questions.json', json_encode($allQuestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
}
