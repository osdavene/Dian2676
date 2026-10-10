<?php

namespace App\Http\Controllers;

use App\Models\JobProfile;
use App\Models\ThematicAxis;
use App\Models\ExamSession;
use App\Models\SessionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Si el usuario no tiene cargo asignado, asignar Analista III por defecto
        if (!$user->job_profile_id) {
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

        // 2. Ejes temáticos del cargo activo con conteo de preguntas
        $thematicAxes = ThematicAxis::where('job_profile_id', $activeJob->id)
            ->withCount(['questions' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        // Total de preguntas disponibles para este cargo
        $totalJobQuestions = $thematicAxes->sum('questions_count');

        // 3. Últimas 5 sesiones del usuario
        $recentSessions = ExamSession::where('user_id', $user->id)
            ->with(['thematicAxis', 'jobProfile'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // 4. Simulacro activo en curso (si existe y no ha expirado)
        $activeSession = ExamSession::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->where('mode', 'simulacro_oficial')
            ->latest('id')
            ->first();

        if ($activeSession) {
            $timeLimit = $activeSession->time_limit_seconds ?? 18000;
            $elapsed = $activeSession->created_at->diffInSeconds(now());
            if ($elapsed >= $timeLimit) {
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
