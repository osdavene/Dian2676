<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\Question;
use App\Models\SessionAnswer;
use App\Models\ThematicAxis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Iniciar un Simulacro Oficial (100 preguntas / 5h) o Express (50 preguntas / 2.5h) o retomar uno en curso
     */
    public function startSimulacro(Request $request)
    {
        $user = Auth::user();
        $jobId = $user->job_profile_id ?? 1;

        $questionCount = (int) $request->input('question_count', 100);
        if ($questionCount !== 50 && $questionCount !== 100) {
            $questionCount = 100;
        }

        $forceNew = $request->boolean('force_new', false);

        // 1. Verificar si el usuario ya tiene un simulacro en curso
        $activeSession = ExamSession::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereIn('mode', ['simulacro_oficial', 'simulacro_express'])
            ->latest('id')
            ->first();

        if ($activeSession) {
            $timeLimit = $activeSession->time_limit_seconds ?? 18000;
            $spent = (int) ($activeSession->time_spent_seconds ?? 0);

            // Si el tiempo consumido ya agotó el límite, auto-finalizar
            if ($spent >= $timeLimit) {
                $this->autoFinalizeExpiredSession($activeSession);
            } elseif (! $forceNew) {
                // Reanudar el simulacro pausado existente
                return redirect()->route('simulacro.view', $activeSession->id)
                    ->with('info', 'Retomando tu simulacro en curso. Tu tiempo transcurrido (pausado) y respuestas se mantienen guardados.');
            } else {
                // El usuario decidió iniciar uno nuevo, auto-finalizar el anterior
                $this->autoFinalizeExpiredSession($activeSession);
            }
        }

        // 2. Parámetros del nuevo simulacro: 50 preguntas (9000 s = 2h 30m) o 100 preguntas (18000 s = 5h)
        $timeLimitSeconds = ($questionCount === 50) ? 9000 : 18000;
        $mode = ($questionCount === 50) ? 'simulacro_express' : 'simulacro_oficial';

        // 3. Obtener preguntas activas aplicables al cargo
        $rawPool = Question::forCargo($jobId)
            ->where('is_active', true)
            ->with('thematicAxis')
            ->get();

        if ($rawPool->isEmpty()) {
            return back()->with('error', 'No hay preguntas disponibles para el cargo seleccionado.');
        }

        // 4. Deduplicar estrictamente por enunciado normalizado (0% preguntas repetidas en el examen)
        $uniquePool = $rawPool->unique(function ($q) {
            $cleaned = preg_replace('/^\d+[\.\)]\s*/', '', trim($q->stem));

            return mb_strtolower($cleaned);
        })->values();

        $targetCount = min($questionCount, $uniquePool->count());

        // 5. Rotación anti-repetición entre exámenes por usuario:
        // Identificar preguntas ya vistas por este usuario en simulacros previos
        $seenQuestionIds = ExamSession::where('user_id', $user->id)
            ->pluck('question_ids')
            ->filter()
            ->flatten()
            ->unique()
            ->flip()
            ->toArray();

        // Agrupación por Caso Situacional: preguntas que comparten el mismo caso se seleccionan juntas y consecutivas
        $groupedByCase = $uniquePool->groupBy(function ($q) {
            $c = trim($q->case_text ?? '');

            return (strlen($c) > 50) ? $c : '__SOLO_'.$q->id;
        });

        $unseenCaseBlocks = [];
        $seenCaseBlocks = [];
        $unseenSoloItems = [];
        $seenSoloItems = [];

        foreach ($groupedByCase as $key => $items) {
            $isCaseBlock = (! str_starts_with($key, '__SOLO_') && $items->count() > 1);

            $hasUnseen = $items->contains(function ($q) use ($seenQuestionIds) {
                return ! isset($seenQuestionIds[$q->id]);
            });

            if ($isCaseBlock) {
                if ($hasUnseen) {
                    $unseenCaseBlocks[] = $items;
                } else {
                    $seenCaseBlocks[] = $items;
                }
            } else {
                foreach ($items as $item) {
                    if (! isset($seenQuestionIds[$item->id])) {
                        $unseenSoloItems[] = $item;
                    } else {
                        $seenSoloItems[] = $item;
                    }
                }
            }
        }

        shuffle($unseenCaseBlocks);
        shuffle($seenCaseBlocks);
        shuffle($unseenSoloItems);
        shuffle($seenSoloItems);

        $selected = collect();

        // Fase A: Priorizar bloques de casos situacionales con preguntas no vistas
        foreach ($unseenCaseBlocks as $block) {
            if ($selected->count() + $block->count() <= $targetCount) {
                $selected = $selected->merge($block);
            }
            if ($selected->count() >= $targetCount) {
                break;
            }
        }

        // Fase B: Completar con preguntas individuales no vistas
        foreach ($unseenSoloItems as $solo) {
            if ($selected->count() >= $targetCount) {
                break;
            }
            if (! $selected->contains('id', $solo->id)) {
                $selected->push($solo);
            }
        }

        // Fase C: Si falta cupo (se agotaron las preguntas no vistas), rellenar con bloques de casos ya vistos
        if ($selected->count() < $targetCount) {
            foreach ($seenCaseBlocks as $block) {
                if ($selected->count() + $block->count() <= $targetCount) {
                    $selected = $selected->merge($block);
                }
                if ($selected->count() >= $targetCount) {
                    break;
                }
            }
        }

        // Fase D: Completar con preguntas individuales ya vistas
        if ($selected->count() < $targetCount) {
            foreach ($seenSoloItems as $solo) {
                if ($selected->count() >= $targetCount) {
                    break;
                }
                if (! $selected->contains('id', $solo->id)) {
                    $selected->push($solo);
                }
            }
        }

        // Fase E: En caso extremo de que aún falte cupo, rellenar con cualquier pregunta pendiente del pool único
        if ($selected->count() < $targetCount) {
            foreach ($uniquePool->shuffle() as $extra) {
                if ($selected->count() >= $targetCount) {
                    break;
                }
                if (! $selected->contains('id', $extra->id)) {
                    $selected->push($extra);
                }
            }
        }

        // 6. Ordenar para que las preguntas del mismo caso queden ESTRICTAMENTE CONSECUTIVAS
        $finalCaseGroups = $selected->groupBy(function ($q) {
            $c = trim($q->case_text ?? '');

            return (strlen($c) > 50) ? $c : '__SOLO_'.$q->id;
        })->shuffle();

        $orderedQuestions = collect();
        foreach ($finalCaseGroups as $group) {
            foreach ($group as $q) {
                $orderedQuestions->push($q);
            }
        }

        $questionIds = $orderedQuestions->pluck('id')->toArray();

        // 7. Barajado dinámico de opciones por pregunta (determinista por sesión)
        $optionsOrder = [];
        foreach ($orderedQuestions as $q) {
            $origMap = $q->options_map;
            $origKeys = array_keys($origMap);
            if (count($origKeys) > 1) {
                $displayLetters = $origKeys;
                $shuffledKeys = $origKeys;
                shuffle($shuffledKeys);
                $map = [];
                foreach ($displayLetters as $i => $dispLetter) {
                    $map[$dispLetter] = $shuffledKeys[$i];
                }
                $optionsOrder[$q->id] = $map;
            }
        }

        $session = ExamSession::create([
            'user_id' => $user->id,
            'job_profile_id' => $jobId,
            'mode' => $mode,
            'question_ids' => $questionIds,
            'options_order' => $optionsOrder,
            'total_questions' => count($questionIds),
            'time_limit_seconds' => $timeLimitSeconds,
            'time_spent_seconds' => 0,
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
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $session = $query->findOrFail($sessionId);

        // Si ya está completado, redirigir a resultados
        if ($session->completed_at) {
            return redirect()->route('simulacro.resultado', $session->id);
        }

        // Cálculo del tiempo continuo y real transcurrido (pausable)
        $timeLimitSeconds = $session->time_limit_seconds ?? 18000;
        $elapsedSeconds = (int) ($session->time_spent_seconds ?? 0);
        $remainingSeconds = max(0, $timeLimitSeconds - $elapsedSeconds);

        // Si el tiempo límite ya expiró en el servidor
        if ($remainingSeconds <= 0) {
            $this->autoFinalizeExpiredSession($session);

            return redirect()->route('simulacro.resultado', $session->id)
                ->with('info', 'El tiempo límite oficial ha concluido para esta sesión.');
        }

        // Obtener preguntas fijas y aleatorizadas asignadas a esta sesión
        $questionIds = $session->question_ids;
        if (! empty($questionIds) && is_array($questionIds)) {
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

        // Asignar opciones en el orden barajado persistente de esta sesión
        foreach ($questions as $q) {
            $q->display_options = $session->getDisplayOptionsFor($q);
        }

        // Respuestas guardadas previamente en la sesión
        $savedAnswers = SessionAnswer::where('exam_session_id', $session->id)
            ->pluck('selected_option', 'question_id')
            ->toArray();

        // Encontrar la primera pregunta no respondida para ubicar al aspirante directamente
        $initialQuestionNumber = 1;
        foreach ($questions as $index => $q) {
            if (! isset($savedAnswers[$q->id]) || $savedAnswers[$q->id] === '' || $savedAnswers[$q->id] === null) {
                $initialQuestionNumber = $index + 1;
                break;
            }
        }

        return view('exams.simulator', compact(
            'session',
            'questions',
            'user',
            'remainingSeconds',
            'elapsedSeconds',
            'savedAnswers',
            'initialQuestionNumber'
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
        if (! $q) {
            return response()->json(['error' => 'Pregunta no encontrada.'], 404);
        }

        $origSelected = $session->getOriginalOptionFor($q, $selected);
        $isCorrect = (trim(strtoupper($origSelected ?? '')) === trim(strtoupper($q->correct_option)));

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

        // Si se envió el tiempo consumido acumulado, actualizarlo
        if ($request->has('time_spent_seconds')) {
            $spent = (int) $request->input('time_spent_seconds');
            if ($spent > 0) {
                $session->update([
                    'time_spent_seconds' => min($session->time_limit_seconds, max($session->time_spent_seconds ?? 0, $spent)),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Sincronización periódica o al salir del tiempo consumido (Heartbeat AJAX)
     */
    public function syncHeartbeat(Request $request, $sessionId)
    {
        $user = Auth::user();
        $session = ExamSession::where('user_id', $user->id)->findOrFail($sessionId);

        if ($session->completed_at) {
            return response()->json(['completed' => true]);
        }

        $spent = (int) $request->input('time_spent_seconds', $session->time_spent_seconds ?? 0);
        $newSpent = min($session->time_limit_seconds, max($session->time_spent_seconds ?? 0, $spent));

        $session->update([
            'time_spent_seconds' => $newSpent,
        ]);

        return response()->json([
            'success' => true,
            'time_spent_seconds' => $newSpent,
            'remaining_seconds' => max(0, $session->time_limit_seconds - $newSpent),
        ]);
    }

    /**
     * Pausar el simulacro y volver al Inicio
     */
    public function pauseSimulacro(Request $request, $sessionId)
    {
        $user = Auth::user();
        $session = ExamSession::where('user_id', $user->id)->findOrFail($sessionId);

        if (! $session->completed_at) {
            $spent = (int) $request->input('time_spent_seconds', $session->time_spent_seconds ?? 0);
            $newSpent = min($session->time_limit_seconds, max($session->time_spent_seconds ?? 0, $spent));
            $session->update([
                'time_spent_seconds' => $newSpent,
            ]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Simulacro pausado exitosamente. Tu tiempo y tus respuestas quedaron guardados. Puedes reanudarlo cuando desees desde el Inicio.');
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
        $timeSpent = (int) $request->input('time_spent_seconds', $session->time_spent_seconds ?? 0);

        $questionIds = $session->question_ids;
        if (! empty($questionIds) && is_array($questionIds)) {
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
                    $origSelected = $session->getOriginalOptionFor($q, $selected);
                    $isCorrect = (trim(strtoupper($origSelected ?? '')) === trim(strtoupper($q->correct_option)));
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

            return back()->with('error', 'Error al calificar el examen: '.$e->getMessage());
        }
    }

    /**
     * Auto-finalizar sesión expirada
     */
    private function autoFinalizeExpiredSession(ExamSession $session): void
    {
        $questionIds = $session->question_ids;
        if (! empty($questionIds) && is_array($questionIds)) {
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
            if ($ans && ! empty($ans->selected_option)) {
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
        if (! $user->isAdmin()) {
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
            if (! $q) {
                continue;
            }

            $axis = $q->thematicAxis;
            $axisId = $axis->id ?? 0;
            $axisName = $axis->name ?? 'General / Transversal';

            if (! isset($axisStats[$axisName])) {
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
                if (! empty($q->legal_basis) && ! in_array($q->legal_basis, $axisStats[$axisName]['legal_references'])) {
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

    /**
     * Historial Completo de Simulacros y Diagnóstico Estadístico Personal
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        // 1. Listado paginado de todos los simulacros completados
        $sessions = ExamSession::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->with(['jobProfile', 'thematicAxis'])
            ->orderBy('completed_at', 'desc')
            ->paginate(12);

        // 2. Métricas consolidadas del aspirante
        $allCompleted = ExamSession::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->get();

        $totalCount = $allCompleted->count();
        $passedCount = $allCompleted->where('is_passed', true)->count();
        $passRate = $totalCount > 0 ? round(($passedCount / $totalCount) * 100, 1) : 0;
        $avgScore = $totalCount > 0 ? floor(($allCompleted->avg('score')) * 100) / 100 : 0.00;
        $bestScore = $totalCount > 0 ? $allCompleted->max('score') : 0.00;
        $totalTimeSpent = (int) $allCompleted->sum('time_spent_seconds');

        $officialCount = $allCompleted->where('mode', 'simulacro_oficial')->count();
        $expressCount = $allCompleted->where('mode', 'simulacro_express')->count();

        // 3. Diagnóstico acumulado de rendimiento por Eje Temático para identificar áreas de mejora
        $axisPerformance = DB::table('session_answers')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'session_answers.exam_session_id')
            ->join('questions', 'questions.id', '=', 'session_answers.question_id')
            ->leftJoin('thematic_axes', 'thematic_axes.id', '=', 'questions.thematic_axis_id')
            ->where('exam_sessions.user_id', $user->id)
            ->whereNotNull('exam_sessions.completed_at')
            ->select(
                'thematic_axes.id as axis_id',
                'thematic_axes.name as axis_name',
                'thematic_axes.category as category',
                DB::raw('COUNT(session_answers.id) as total_answers'),
                DB::raw('SUM(CASE WHEN session_answers.is_correct THEN 1 ELSE 0 END) as correct_answers')
            )
            ->groupBy('thematic_axes.id', 'thematic_axes.name', 'thematic_axes.category')
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_answers;
                $correct = (int) $row->correct_answers;
                $pct = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

                return (object) [
                    'axis_id' => $row->axis_id,
                    'name' => $row->axis_name ?? 'Eje Temático General',
                    'category' => $row->category ?? 'FUNCIONAL',
                    'total' => $total,
                    'correct' => $correct,
                    'incorrect' => $total - $correct,
                    'percent' => $pct,
                    'needs_improvement' => ($pct < 70.00),
                    'priority' => ($pct < 50.00) ? 'CRÍTICA' : (($pct < 70.00) ? 'MEDIA' : 'DOMINADA'),
                ];
            })
            ->sortBy('percent')
            ->values();

        $criticalAxes = $axisPerformance->where('needs_improvement', true);

        return view('exams.history', compact(
            'user',
            'sessions',
            'totalCount',
            'passedCount',
            'passRate',
            'avgScore',
            'bestScore',
            'totalTimeSpent',
            'officialCount',
            'expressCount',
            'axisPerformance',
            'criticalAxes'
        ));
    }
}
