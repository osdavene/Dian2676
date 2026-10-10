<?php

namespace App\Http\Controllers;

use App\Models\JobProfile;
use App\Models\ThematicAxis;
use App\Models\Question;
use App\Models\ExamSession;
use App\Models\SessionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Iniciar un Simulacro Oficial Completo o retomar uno en curso
     */
    public function startSimulacro(Request $request)
    {
        $user = Auth::user();
        $jobId = $user->job_profile_id ?? 1;

        // 1. Verificar si el usuario ya tiene un simulacro oficial en curso
        $activeSession = ExamSession::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->where('mode', 'simulacro_oficial')
            ->latest('id')
            ->first();

        if ($activeSession) {
            $timeLimit = $activeSession->time_limit_seconds ?? 18000;
            $elapsed = $activeSession->created_at->diffInSeconds(now());

            if ($elapsed < $timeLimit) {
                return redirect()->route('simulacro.view', $activeSession->id)
                    ->with('info', 'Retomando tu simulacro en curso. Tu tiempo transcurrido y respuestas se mantienen guardados.');
            } else {
                $this->autoFinalizeExpiredSession($activeSession);
            }
        }

        // 2. Seleccionar preguntas del cargo (exclusivas + compartidas/transversales) de forma aleatoria
        $questions = Question::forCargo($jobId)
            ->where('is_active', true)
            ->with('thematicAxis')
            ->inRandomOrder()
            ->take(100)
            ->get();

        if ($questions->isEmpty()) {
            return back()->with('error', 'No hay preguntas disponibles para el cargo seleccionado.');
        }

        $questionIds = $questions->pluck('id')->toArray();

        $session = ExamSession::create([
            'user_id' => $user->id,
            'job_profile_id' => $jobId,
            'mode' => 'simulacro_oficial',
            'question_ids' => $questionIds,
            'total_questions' => count($questionIds),
            'time_limit_seconds' => 18000, // 5 Horas oficiales
        ]);

        return redirect()->route('simulacro.view', $session->id);
    }

    /**
     * Ver interfaz interactiva del examen con tiempo persistente y respuestas previas
     */
    public function showSimulacro($sessionId)
    {
        $user = Auth::user();
        $query = ExamSession::with(['jobProfile']);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $session = $query->findOrFail($sessionId);

        // Si ya está completado, redirigir a resultados
        if ($session->completed_at) {
            return redirect()->route('simulacro.resultado', $session->id);
        }

        // Cálculo del tiempo continuo y real transcurrido
        $timeLimitSeconds = $session->time_limit_seconds ?? 18000;
        $elapsedSeconds = $session->created_at->diffInSeconds(now());
        $remainingSeconds = max(0, $timeLimitSeconds - $elapsedSeconds);

        // Si el tiempo límite ya expiró en el servidor
        if ($remainingSeconds <= 0) {
            $this->autoFinalizeExpiredSession($session);
            return redirect()->route('simulacro.resultado', $session->id)
                ->with('info', 'El tiempo límite de 5 horas ha concluido para esta sesión.');
        }

        // Obtener preguntas fijas y aleatorizadas asignadas a esta sesión
        $questionIds = $session->question_ids;
        if (!empty($questionIds) && is_array($questionIds)) {
            $questions = Question::whereIn('id', $questionIds)
                ->where('is_active', true)
                ->with('thematicAxis')
                ->get()
                ->sortBy(function ($q) use ($questionIds) {
                    return array_search($q->id, $questionIds);
                })
                ->values();
        } else {
            $questions = Question::forCargo($session->job_profile_id)
                ->where('is_active', true)
                ->with('thematicAxis')
                ->take($session->total_questions)
                ->get();
        }

        // Respuestas guardadas previamente en la sesión
        $savedAnswers = SessionAnswer::where('exam_session_id', $session->id)
            ->pluck('selected_option', 'question_id')
            ->toArray();

        return view('exams.simulator', compact(
            'session',
            'questions',
            'user',
            'remainingSeconds',
            'elapsedSeconds',
            'savedAnswers'
        ));
    }

    /**
     * Guardar una respuesta individual en tiempo real (Autoguardado AJAX)
     */
    public function saveSingleAnswer(Request $request, $sessionId)
    {
        $user = Auth::user();
        $session = ExamSession::where('user_id', $user->id)->findOrFail($sessionId);

        if ($session->completed_at) {
            return response()->json(['error' => 'Examen ya finalizado.'], 400);
        }

        $qId = $request->input('question_id');
        $selected = $request->input('selected_option');

        $q = Question::where('id', $qId)->where('is_active', true)->first();
        if (!$q) {
            return response()->json(['error' => 'Pregunta no encontrada.'], 404);
        }

        $isCorrect = (trim(strtoupper($selected)) === trim(strtoupper($q->correct_option)));

        SessionAnswer::updateOrCreate(
            [
                'exam_session_id' => $session->id,
                'question_id' => $q->id,
            ],
            [
                'selected_option' => $selected,
                'is_correct' => $isCorrect,
                'is_omitted' => empty($selected),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Calificar y finalizar el examen
     */
    public function submitSimulacro(Request $request, $sessionId)
    {
        $user = Auth::user();
        $session = ExamSession::where('user_id', $user->id)->findOrFail($sessionId);

        if ($session->completed_at) {
            return redirect()->route('simulacro.resultado', $session->id);
        }

        $answers = $request->input('answers', []);
        $timeSpent = (int) $request->input('time_spent_seconds', $session->created_at->diffInSeconds(now()));

        $questionIds = $session->question_ids;
        if (!empty($questionIds) && is_array($questionIds)) {
            $questions = Question::whereIn('id', $questionIds)->where('is_active', true)->get();
        } else {
            $questions = Question::forCargo($session->job_profile_id)
                ->where('is_active', true)
                ->take($session->total_questions)
                ->get();
        }

        $correctCount = 0;
        $incorrectCount = 0;
        $omittedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($questions as $q) {
                // Prioridad a las respuestas enviadas en el formulario, luego a las autoguardadas
                $selected = $answers[$q->id] ?? null;

                if (empty($selected)) {
                    $existing = SessionAnswer::where('exam_session_id', $session->id)
                        ->where('question_id', $q->id)
                        ->first();
                    $selected = $existing->selected_option ?? null;
                }

                if (empty($selected)) {
                    $isOmitted = true;
                    $isCorrect = false;
                    $omittedCount++;
                } else {
                    $isOmitted = false;
                    $isCorrect = (trim(strtoupper($selected)) === trim(strtoupper($q->correct_option)));
                    if ($isCorrect) {
                        $correctCount++;
                    } else {
                        $incorrectCount++;
                    }
                }

                SessionAnswer::updateOrCreate(
                    [
                        'exam_session_id' => $session->id,
                        'question_id' => $q->id,
                    ],
                    [
                        'selected_option' => $selected,
                        'is_correct' => $isCorrect,
                        'is_omitted' => $isOmitted,
                    ]
                );
            }

            // Regla oficial DIAN / CNSC: 0 a 100 puntos truncado a 2 decimales sin redondeo al alza
            $total = $questions->count();
            $rawScore = $total > 0 ? ($correctCount / $total) * 100 : 0.00;
            $truncatedScore = floor($rawScore * 100) / 100;
            $isPassed = ($truncatedScore >= 70.00);

            $session->update([
                'correct_answers' => $correctCount,
                'incorrect_answers' => $incorrectCount,
                'omitted_answers' => $omittedCount,
                'score' => $truncatedScore,
                'is_passed' => $isPassed,
                'time_spent_seconds' => min($session->time_limit_seconds, $timeSpent),
                'completed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('simulacro.resultado', $session->id)
                ->with('success', '¡Simulacro finalizado y calificado con éxito!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al calificar el examen: ' . $e->getMessage());
        }
    }

    /**
     * Auto-finalizar sesión expirada
     */
    private function autoFinalizeExpiredSession(ExamSession $session): void
    {
        $questionIds = $session->question_ids;
        if (!empty($questionIds) && is_array($questionIds)) {
            $questions = Question::whereIn('id', $questionIds)->where('is_active', true)->get();
        } else {
            $questions = Question::forCargo($session->job_profile_id)
                ->where('is_active', true)
                ->take($session->total_questions)
                ->get();
        }

        $savedAnswers = SessionAnswer::where('exam_session_id', $session->id)
            ->get()
            ->keyBy('question_id');

        $correctCount = 0;
        $incorrectCount = 0;
        $omittedCount = 0;

        foreach ($questions as $q) {
            $ans = $savedAnswers->get($q->id);
            if ($ans && !empty($ans->selected_option)) {
                if ($ans->is_correct) {
                    $correctCount++;
                } else {
                    $incorrectCount++;
                }
            } else {
                $omittedCount++;
                SessionAnswer::updateOrCreate(
                    [
                        'exam_session_id' => $session->id,
                        'question_id' => $q->id,
                    ],
                    [
                        'selected_option' => null,
                        'is_correct' => false,
                        'is_omitted' => true,
                    ]
                );
            }
        }

        $total = $questions->count();
        $rawScore = $total > 0 ? ($correctCount / $total) * 100 : 0.00;
        $truncatedScore = floor($rawScore * 100) / 100;

        $session->update([
            'correct_answers' => $correctCount,
            'incorrect_answers' => $incorrectCount,
            'omitted_answers' => $omittedCount,
            'score' => $truncatedScore,
            'is_passed' => ($truncatedScore >= 70.00),
            'time_spent_seconds' => $session->time_limit_seconds,
            'completed_at' => now(),
        ]);
    }

    /**
     * Ver resultados del examen
     */
    public function showResult($sessionId)
    {
        $user = Auth::user();
        $query = ExamSession::with(['jobProfile', 'thematicAxis', 'user']);
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $session = $query->findOrFail($sessionId);

        $answers = SessionAnswer::where('exam_session_id', $session->id)
            ->with(['question.thematicAxis'])
            ->get();

        // 1. Desglose detallado por Eje Temático y Plan Pedagógico de Mejora
        $axisStats = [];
        $competencyStats = [
            'BASICA' => ['total' => 0, 'correct' => 0, 'name' => 'Competencias Básicas / Transversales', 'percent' => 0],
            'FUNCIONAL' => ['total' => 0, 'correct' => 0, 'name' => 'Competencias Funcionales', 'percent' => 0],
            'CONDUCTUAL' => ['total' => 0, 'correct' => 0, 'name' => 'Competencias Conductuales / Blandas', 'percent' => 0],
        ];

        $improvementPlan = [];

        foreach ($answers as $a) {
            $q = $a->question;
            if (!$q) continue;

            $axis = $q->thematicAxis;
            $axisId = $axis->id ?? 0;
            $axisName = $axis->name ?? 'General / Transversal';

            if (!isset($axisStats[$axisName])) {
                $axisStats[$axisName] = [
                    'id' => $axisId,
                    'name' => $axisName,
                    'category' => $axis->category ?? 'FUNCIONAL',
                    'total' => 0,
                    'correct' => 0,
                    'incorrect' => 0,
                    'legal_references' => [],
                ];
            }
            $axisStats[$axisName]['total']++;
            if ($a->is_correct) {
                $axisStats[$axisName]['correct']++;
            } else {
                $axisStats[$axisName]['incorrect']++;
                if (!empty($q->legal_basis) && !in_array($q->legal_basis, $axisStats[$axisName]['legal_references'])) {
                    $axisStats[$axisName]['legal_references'][] = $q->legal_basis;
                }
            }

            // Agrupación por tipo de competencia
            $compType = strtoupper($q->question_type ?? 'FUNCIONAL');
            if (str_contains($compType, 'BAS') || str_contains($compType, 'INTEG') || str_contains($compType, 'CONST')) {
                $compKey = 'BASICA';
            } elseif (str_contains($compType, 'COND') || str_contains($compType, 'COMPORT')) {
                $compKey = 'CONDUCTUAL';
            } else {
                $compKey = 'FUNCIONAL';
            }
            $competencyStats[$compKey]['total']++;
            if ($a->is_correct) {
                $competencyStats[$compKey]['correct']++;
            }
        }

        // Calcular porcentajes de competencias
        foreach ($competencyStats as &$cSt) {
            $cSt['percent'] = $cSt['total'] > 0 ? round(($cSt['correct'] / $cSt['total']) * 100, 1) : 0;
        }
        unset($cSt);

        // Generar diagnóstico de áreas a mejorar (Plan de Mejora Focalizado)
        foreach ($axisStats as $axisName => &$st) {
            $st['percent'] = $st['total'] > 0 ? round(($st['correct'] / $st['total']) * 100, 1) : 0;
            
            // Si la efectividad es menor al umbral eliminatorio CNSC (70.00 pts)
            if ($st['percent'] < 70.00 && $st['total'] > 0) {
                $priority = $st['percent'] < 50.00 ? 'CRÍTICA' : 'MEDIA';
                $improvementPlan[] = [
                    'axis_id' => $st['id'],
                    'axis_name' => $axisName,
                    'category' => $st['category'],
                    'percent' => $st['percent'],
                    'correct' => $st['correct'],
                    'incorrect' => $st['incorrect'],
                    'total' => $st['total'],
                    'priority' => $priority,
                    'legal_references' => array_slice($st['legal_references'], 0, 3),
                ];
            }
        }
        unset($st);

        // Ordenar plan de mejora por menor porcentaje (más crítico primero)
        usort($improvementPlan, function ($a, $b) {
            return $a['percent'] <=> $b['percent'];
        });

        return view('exams.results', compact('session', 'answers', 'axisStats', 'competencyStats', 'improvementPlan', 'user'));
    }

    /**
     * Vista de selección para entrenamiento focalizado
     */
    public function practiceSelection()
    {
        $user = Auth::user();
        $jobId = $user->job_profile_id ?? 1;

        $axes = ThematicAxis::where('job_profile_id', $jobId)
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        foreach ($axes as $axis) {
            $axis->questions_count = Question::where('is_active', true)
                ->where(function ($q) use ($axis) {
                    $q->where('thematic_axis_id', $axis->id)
                      ->orWhere(function ($sub) use ($axis) {
                          $sub->where(function ($s) {
                              $s->where('is_shared', true)
                                ->orWhere('scope_level', 'profesional')
                                ->orWhere('scope_level', 'transversal');
                          })->whereHas('thematicAxis', function ($tax) use ($axis) {
                              $tax->where('name', $axis->name);
                          });
                      });
                })->count();
        }

        return view('exams.practice_selection', compact('axes', 'user'));
    }

    /**
     * Modo Práctica por Eje Temático con retroalimentación inmediata
     */
    public function practiceAxis($axisId)
    {
        $user = Auth::user();
        $axis = ThematicAxis::with('jobProfile')->findOrFail($axisId);

        $questions = Question::where('is_active', true)
            ->where(function ($q) use ($axis) {
                $q->where('thematic_axis_id', $axis->id)
                  ->orWhere(function ($sub) use ($axis) {
                      $sub->where(function ($s) {
                          $s->where('is_shared', true)
                            ->orWhere('scope_level', 'profesional')
                            ->orWhere('scope_level', 'transversal');
                      })->whereHas('thematicAxis', function ($tax) use ($axis) {
                          $tax->where('name', $axis->name);
                      });
                  });
            })
            ->orderBy('id')
            ->get();

        if ($questions->isEmpty()) {
            return redirect()->route('practica.index')
                ->with('error', 'No hay preguntas disponibles en este eje temático.');
        }

        return view('exams.practice', compact('axis', 'questions', 'user'));
    }

    /**
     * Modo Repaso de Fallos Personal
     */
    public function reviewMistakes()
    {
        $user = Auth::user();

        // Preguntas falladas por el usuario en sus sesiones
        $failedQuestions = Question::join('session_answers', 'session_answers.question_id', '=', 'questions.id')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'session_answers.exam_session_id')
            ->where('exam_sessions.user_id', $user->id)
            ->where('session_answers.is_correct', false)
            ->where('questions.is_active', true)
            ->select('questions.*')
            ->distinct()
            ->with('thematicAxis')
            ->get();

        if ($failedQuestions->isEmpty()) {
            return redirect()->route('dashboard')
                ->with('info', '¡Felicitaciones! No tienes preguntas falladas pendientes de repasar.');
        }

        return view('exams.mistakes', compact('failedQuestions', 'user'));
    }
}
