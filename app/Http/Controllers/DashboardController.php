<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\JobProfile;
use App\Models\Question;
use App\Models\SessionAnswer;
use App\Models\ThematicAxis;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Si el usuario no tiene cargo asignado, asignar Analista III por defecto
        if (! $user->job_profile_id) {
            $defaultJob = JobProfile::where('code', 'AT-FL-2011')->first() ?? JobProfile::first();
            if ($defaultJob) {
                $user->update([
                    'job_profile_id' => $defaultJob->id,
                    'target_cargo' => $defaultJob->name,
                ]);
            }
        }

        $activeJob = $user->jobProfile ?? JobProfile::find($user->job_profile_id);
        $allJobs = JobProfile::where('is_active', true)->orderBy('id')->get();

        // 1. Estadísticas personales del usuario
        $userSessions = ExamSession::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderBy('id', 'desc')
            ->get();

        $totalExams = $userSessions->count();
        $passedExams = $userSessions->where('is_passed', true)->count();
        $passRate = $totalExams > 0 ? round(($passedExams / $totalExams) * 100, 1) : 0;
        $avgScore = $totalExams > 0 ? floor(($userSessions->avg('score')) * 100) / 100 : 0.00;

        // Conteo de preguntas falladas pendientes de repasar
        $failedQuestionsCount = SessionAnswer::join('exam_sessions', 'exam_sessions.id', '=', 'session_answers.exam_session_id')
            ->where('exam_sessions.user_id', $user->id)
            ->where('session_answers.is_correct', false)
            ->distinct('session_answers.question_id')
            ->count('session_answers.question_id');

        // 2. Ejes temáticos del cargo activo con conteo de preguntas (incluyendo transversales y compartidas)
        $thematicAxes = ThematicAxis::where('job_profile_id', $activeJob->id)
            ->orderBy('category')
            ->orderBy('id')
            ->get()
            ->map(function ($axis) {
                $axis->questions_count = Question::where('is_active', true)
                    ->where(function ($q) use ($axis) {
                        $q->where('thematic_axis_id', $axis->id)
                            ->orWhere(function ($sub) use ($axis) {
                                $sub->where(function ($s) {
                                    $s->where('is_shared', true)
                                        ->orWhere('scope_level', 'transversal');
                                })->whereHas('thematicAxis', function ($tax) use ($axis) {
                                    $tax->where('name', $axis->name);
                                });
                            });
                    })
                    ->count();

                return $axis;
            });

        // Total de preguntas reales disponibles para este cargo
        $totalJobQuestions = Question::forCargo($activeJob->id)->where('is_active', true)->count();

        // 3. Últimas 5 sesiones del usuario
        $recentSessions = ExamSession::where('user_id', $user->id)
            ->with(['thematicAxis', 'jobProfile'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // 4. Simulacro activo en curso (si existe y no ha agotado su tiempo)
        $activeSession = ExamSession::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereIn('mode', ['simulacro_oficial', 'simulacro_express'])
            ->latest('id')
            ->first();

        if ($activeSession) {
            $timeLimit = $activeSession->time_limit_seconds ?? 18000;
            $spent = (int) ($activeSession->time_spent_seconds ?? 0);
            if ($spent >= $timeLimit) {
                $activeSession = null;
            }
        }

        return view('dashboard', compact(
            'user',
            'activeJob',
            'allJobs',
            'totalExams',
            'passedExams',
            'passRate',
            'avgScore',
            'failedQuestionsCount',
            'thematicAxes',
            'totalJobQuestions',
            'recentSessions',
            'activeSession'
        ));
    }
}
